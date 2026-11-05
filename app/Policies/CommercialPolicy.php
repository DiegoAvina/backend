<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Autorización de prospectos y solicitudes (datos personales).
 *
 * - Los registros solo se crean desde los formularios del sitio.
 * - Nunca se eliminan desde el panel (ni siquiera el superadministrador):
 *   una cancelación ARCO se atiende con un procedimiento documentado.
 * - Los datos se editan solo vía acciones de seguimiento (estado,
 *   responsable, notas), no con un formulario libre.
 */
abstract class CommercialPolicy
{
    abstract protected function module(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->module().'.view');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->can($this->module().'.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }

    public function changeStatus(User $user, Model $record): bool
    {
        return $user->can($this->module().'.update');
    }

    public function assign(User $user, Model $record): bool
    {
        return $user->can($this->module().'.assign');
    }

    public function addNote(User $user, Model $record): bool
    {
        return $user->can($this->module().'.view') && $user->can('notes.create');
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Model $record): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }
}
