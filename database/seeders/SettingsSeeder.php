<?php

namespace Database\Seeders;

use App\Enums\SettingKey;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Crea las claves de configuración del catálogo con valor vacío.
 *
 * Nunca sobrescribe un valor existente: solo crea claves faltantes y
 * sincroniza sus metadatos (grupo, tipo, visibilidad). Los datos reales de
 * la empresa se capturan desde el panel.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SettingKey::cases() as $position => $key) {
            $setting = Setting::query()->firstOrNew(['key' => $key->value]);

            $setting->fill([
                'group' => $key->group(),
                'type' => $key->type(),
                'is_public' => $key->isPublic(),
                'sort_order' => $position,
            ]);

            $setting->save();
        }
    }
}
