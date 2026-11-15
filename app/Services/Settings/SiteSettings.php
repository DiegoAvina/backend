<?php

namespace App\Services\Settings;

use App\Enums\SettingKey;
use App\Enums\SiteMode;
use App\Jobs\RevalidateFrontendContent;
use App\Models\Setting;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Lectura y actualización de la configuración del sitio.
 */
class SiteSettings
{
    public function __construct(private AuditLogger $audit) {}

    public function get(SettingKey $key): ?string
    {
        $value = Setting::query()->where('key', $key->value)->value('value');

        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * Modo actual del sitio público. Sin valor capturado, el sitio está en línea.
     */
    public function siteMode(): SiteMode
    {
        return SiteMode::tryFrom((string) $this->get(SettingKey::SiteMode)) ?? SiteMode::Live;
    }

    /**
     * Valores actuales indexados por clave (todas las claves del catálogo).
     *
     * @return array<string, ?string>
     */
    public function all(): array
    {
        $stored = Setting::query()->pluck('value', 'key');

        $values = [];
        foreach (SettingKey::cases() as $key) {
            $value = $stored[$key->value] ?? null;
            $values[$key->value] = $value === null || $value === '' ? null : (string) $value;
        }

        return $values;
    }

    /**
     * Solo las claves públicas. Las imágenes se devuelven como URL absoluta.
     *
     * @return array<string, ?string>
     */
    public function publicValues(): array
    {
        $all = $this->all();
        $values = [];

        foreach (SettingKey::cases() as $key) {
            if (! $key->isPublic()) {
                continue;
            }

            $value = $all[$key->value];
            $values[$key->value] = match (true) {
                $key === SettingKey::SiteMode => $this->siteMode()->value,
                $value === null => null,
                $key->type() === 'image' => Storage::disk('public')->url($value),
                $key->type() === 'datetime' => Carbon::parse($value)->toIso8601String(),
                default => $value,
            };
        }

        return $values;
    }

    /**
     * Valida y guarda los valores. Solo acepta claves del catálogo; registra
     * en auditoría las claves modificadas (sin el valor de las privadas) y
     * pide al frontend revalidar la configuración pública.
     *
     * @param  array<string, mixed>  $input
     * @return list<string> claves modificadas
     */
    public function update(array $input): array
    {
        $rules = [];
        foreach (SettingKey::cases() as $key) {
            $rules[$key->value] = ['nullable', 'string', ...$key->rules()];
        }

        $validated = Validator::make(array_intersect_key($input, $rules), $rules)->validate();
        $current = $this->all();
        $changed = [];

        DB::transaction(function () use ($validated, $current, &$changed): void {
            foreach ($validated as $keyName => $value) {
                $value = is_string($value) ? trim($value) : null;
                $value = $value === '' ? null : $value;

                // "En línea" es el estado por omisión: se guarda como vacío.
                if ($keyName === SettingKey::SiteMode->value && $value === SiteMode::Live->value) {
                    $value = null;
                }

                if ($current[$keyName] === $value) {
                    continue;
                }

                $key = SettingKey::from($keyName);
                Setting::query()->updateOrCreate(['key' => $keyName], [
                    'value' => $value,
                    'group' => $key->group(),
                    'type' => $key->type(),
                    'is_public' => $key->isPublic(),
                ]);

                $changed[] = $keyName;

                // Imagen reemplazada o retirada: se elimina el archivo anterior.
                if ($key->type() === 'image' && $current[$keyName] !== null) {
                    Storage::disk('public')->delete($current[$keyName]);
                }
            }
        });

        if ($changed !== []) {
            $old = [];
            $new = [];
            foreach ($changed as $keyName) {
                $public = SettingKey::from($keyName)->isPublic();
                $old[$keyName] = $public ? $current[$keyName] : '[privado]';
                $new[$keyName] = $public ? ($validated[$keyName] ?? null) : '[privado]';
            }

            $this->audit->log('settings_updated', null, 'Configuración del sitio actualizada', $old, $new);

            if (collect($changed)->contains(fn (string $keyName): bool => SettingKey::from($keyName)->isPublic())) {
                RevalidateFrontendContent::dispatch(['settings']);
            }
        }

        return $changed;
    }
}
