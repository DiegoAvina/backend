<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Edición de permisos por rol. Solo con `roles.manage` (superadministrador).
 * Los roles son fijos: no se crean ni se eliminan desde el panel, y los
 * permisos del superadministrador no se pueden reducir.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.manage') && $role->name !== UserRole::SuperAdmin->value;
    }

    public function delete(User $user, Role $role): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
