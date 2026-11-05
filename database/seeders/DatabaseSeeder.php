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
     * Solo siembra el contenido público inicial. No crea usuarios con
     * contraseñas conocidas: los administradores se crearán en la fase 5.
     */
    public function run(): void
    {
        $this->call(ContentSeeder::class);
    }
}
