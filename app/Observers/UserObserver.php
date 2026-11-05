<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Audit\AuditLogger;

/**
 * Auditoría de altas y cambios de usuarios del panel (nunca guarda la
 * contraseña: el registrador la oculta).
 */
class UserObserver
{
    public function __construct(private AuditLogger $audit) {}

    public function created(User $user): void
    {
        $this->audit->log('user_created', $user, $user->email, null, $user->only(['name', 'email', 'is_active']));
    }

    public function updated(User $user): void
    {
        [$old, $new] = $this->audit->diff($user);

        if ($new === []) {
            return;
        }

        $event = $user->wasChanged('is_active') ? ($user->is_active ? 'user_activated' : 'user_deactivated') : 'user_updated';

        $this->audit->log($event, $user, $user->email, $old, $new);
    }
}
