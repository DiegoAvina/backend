<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea los cinco roles y el catálogo de permisos.
 *
 * Idempotente y conservador: crea lo que falta y, en un rol recién creado,
 * asigna sus permisos iniciales. En un rol existente solo agrega permisos
 * nuevos del catálogo, para no deshacer ajustes hechos desde el panel.
 * El superadministrador siempre conserva todos los permisos.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        foreach (UserRole::allPermissions() as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        // Con `WithoutModelEvents` la caché de permisos no se limpia sola.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (UserRole::cases() as $roleEnum) {
            $role = Role::query()->where('name', $roleEnum->value)->where('guard_name', $guard)->first();

            if ($role === null) {
                $role = Role::create(['name' => $roleEnum->value, 'guard_name' => $guard]);
                $role->syncPermissions($roleEnum->defaultPermissions());

                continue;
            }

            if ($roleEnum === UserRole::SuperAdmin) {
                $role->syncPermissions(UserRole::allPermissions());
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
