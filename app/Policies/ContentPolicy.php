<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Autorización del contenido público, por permisos `{módulo}.{acción}`.
 *
 * Eliminación controlada: solo se puede eliminar contenido que no está
 * publicado, para que nunca desaparezca una página visible por accidente.
 */
abstract class ContentPolicy
{
    abstract protected function module(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->permission('view'));
    }

    public function view(User $user, Model $record): bool
    {
        return $user->can($this->permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can($this->permission('create'));
    }

    public function update(User $user, Model $record): bool
    {
        return $user->can($this->permission('update'));
    }

    public function publish(User $user, Model $record): bool
    {
        return $user->can($this->permission('publish'));
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->can($this->permission('delete')) && ! $this->isPublished($record);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Model $record): bool
    {
        return $user->can($this->permission('delete'));
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return $user->can($this->permission('update'));
    }

    protected function isPublished(Model $record): bool
    {
        return (bool) $record->getAttribute('is_published');
    }

    protected function permission(string $action): string
    {
        return $this->module().'.'.$action;
    }
}
