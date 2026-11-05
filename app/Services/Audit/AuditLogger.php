<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Services\Leads\SubmissionContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Registra acciones administrativas importantes en `audit_logs`.
 *
 * Los valores se guardan recortados y sin datos sensibles: nunca contraseñas,
 * tokens ni metadatos técnicos de los formularios.
 */
class AuditLogger
{
    /** Atributos que nunca se guardan en la auditoría. */
    private const REDACTED = ['password', 'remember_token', 'ip_hash', 'user_agent', 'idempotency_key'];

    /** Atributos sin valor de auditoría. */
    private const IGNORED = ['created_at', 'updated_at', 'last_login_at'];

    private const MAX_VALUE_LENGTH = 500;

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function log(string $event, ?Model $subject = null, ?string $description = null, ?array $old = null, ?array $new = null): AuditLog
    {
        $request = app()->runningInConsole() ? null : request();

        return AuditLog::query()->create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'description' => $description === null ? null : mb_substr($description, 0, 255),
            'old_values' => $old === null ? null : $this->clean($old),
            'new_values' => $new === null ? null : $this->clean($new),
            'ip_hash' => SubmissionContext::hashIp($request?->ip()),
        ]);
    }

    /**
     * Valores anteriores y nuevos de los atributos que cambiaron en un guardado.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function diff(Model $model): array
    {
        $changes = array_diff_key($model->getChanges(), array_flip(self::IGNORED));
        $old = array_intersect_key($model->getOriginal(), $changes);

        return [$this->raw($old), $changes];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function clean(array $values): array
    {
        $values = array_diff_key($values, array_flip(self::IGNORED));

        foreach ($values as $key => $value) {
            if (in_array($key, self::REDACTED, true)) {
                $values[$key] = '[oculto]';

                continue;
            }

            if (is_string($value) && mb_strlen($value) > self::MAX_VALUE_LENGTH) {
                $values[$key] = mb_substr($value, 0, self::MAX_VALUE_LENGTH).'…';
            }
        }

        return $values;
    }

    /**
     * Convierte enums y fechas a valores simples para JSON.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function raw(array $values): array
    {
        return array_map(fn (mixed $value): mixed => match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format(DATE_ATOM),
            default => $value,
        }, $values);
    }
}
