<?php

namespace App\Filament\Resources\Users\Pages\Concerns;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

/**
 * Asigna el rol elegido en el formulario y lo registra en auditoría.
 * Vuelve a validar en el servidor que el rol sea asignable por el usuario actual.
 */
trait SyncsUserRole
{
    /**
     * Se llama antes de guardar: un rol no permitido detiene la operación
     * sin crear ni modificar al usuario.
     */
    protected function ensureRoleIsAssignable(): void
    {
        if (! array_key_exists($this->selectedRole(), UserResource::assignableRoles())) {
            throw new AuthorizationException('No puedes asignar ese rol.');
        }
    }

    protected function selectedRole(): string
    {
        return (string) ($this->form->getRawState()['role'] ?? '');
    }

    protected function syncRole(User $user): void
    {
        $role = $this->selectedRole();
        $previous = $user->roles->first()?->name;

        if ($previous === $role) {
            return;
        }

        $user->syncRoles([$role]);

        app(AuditLogger::class)->log(
            'role_changed',
            $user,
            $user->email.': '.(UserRole::tryFrom($role)?->label() ?? $role),
            ['role' => $previous],
            ['role' => $role],
        );
    }

    /**
     * Nadie puede desactivarse a sí mismo (perdería el acceso).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function protectSelfDeactivation(array $data, ?User $user): array
    {
        if ($user !== null && $user->is(Auth::user())) {
            $data['is_active'] = true;
        }

        return $data;
    }
}
