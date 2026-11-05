<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Services\Audit\AuditLogger;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Registra en auditoría qué permisos se agregaron y cuáles se retiraron.
 */
class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /** @var list<string> */
    private array $permissionsBefore = [];

    protected function beforeSave(): void
    {
        /** @var Role $role */
        $role = $this->getRecord();
        $this->permissionsBefore = $role->permissions()->pluck('name')->sort()->values()->all();
    }

    protected function afterSave(): void
    {
        /** @var Role $role */
        $role = $this->getRecord();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $after = $role->permissions()->pluck('name')->sort()->values()->all();
        $added = array_values(array_diff($after, $this->permissionsBefore));
        $removed = array_values(array_diff($this->permissionsBefore, $after));

        if ($added === [] && $removed === []) {
            return;
        }

        app(AuditLogger::class)->log(
            'permissions_updated',
            $role,
            'Permisos del rol '.(UserRole::tryFrom($role->name)?->label() ?? $role->name),
            ['removed' => $removed],
            ['added' => $added],
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
