<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Pages\Concerns\SyncsUserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use SyncsUserRole;

    protected static string $resource = UserResource::class;

    protected function beforeCreate(): void
    {
        $this->ensureRoleIsAssignable();
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->getRecord();
        $this->syncRole($user);
    }
}
