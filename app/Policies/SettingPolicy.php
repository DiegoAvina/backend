<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

/**
 * Configuración global: solo con `settings.manage` (administradores).
 */
class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, ?Setting $setting = null): bool
    {
        return $user->can('settings.manage');
    }
}
