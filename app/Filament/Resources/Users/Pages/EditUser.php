<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Pages\Concerns\SyncsUserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    use SyncsUserRole;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $user */
        $user = $this->getRecord();

        return $this->protectSelfDeactivation($data, $user);
    }

    protected function beforeSave(): void
    {
        $this->ensureRoleIsAssignable();
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->getRecord();
        $this->syncRole($user);
    }
}
