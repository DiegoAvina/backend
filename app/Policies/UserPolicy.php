<?php

namespace App\Policies;

use App\Models\User;

/**
 * Administración de usuarios del panel.
 *
 * - Requiere `users.manage` (comercial, editor y consulta no lo tienen).
 * - Solo un superadministrador puede crear, editar o asignar ese rol a otros.
 * - Los usuarios no se eliminan: se desactivan, para conservar la autoría de
 *   notas, cambios de estado y auditoría.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->can('users.manage')) {
            return false;
        }

        return ! $target->isSuperAdmin() || $user->isSuperAdmin();
    }

    /**
     * Desactivar a otro usuario (nunca a uno mismo, para no perder el acceso).
     */
    public function deactivate(User $user, User $target): bool
    {
        return $this->update($user, $target) && ! $user->is($target);
    }

    public function assignSuperAdmin(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
