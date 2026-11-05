<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Siembra roles y permisos, las claves de configuración (vacías) y el
     * contenido público inicial. No crea usuarios con contraseñas conocidas:
     * el primer superadministrador se crea con `php artisan app:create-admin`.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            ContentSeeder::class,
        ]);
    }
}
