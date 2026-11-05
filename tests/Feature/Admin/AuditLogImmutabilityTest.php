<?php

use App\Enums\UserRole;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Product;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

/**
 * La auditoría no se puede alterar por las vías normales del panel ni del modelo.
 */
beforeEach(function () {
    $this->admin = actingAsRole(UserRole::SuperAdmin);
    Product::factory()->create();
    $this->log = AuditLog::query()->latest('id')->firstOrFail();
});

it('only exposes a read-only listing in the panel', function () {
    expect(AuditLogResource::getPages())->toHaveKeys(['index'])->not->toHaveKeys(['create', 'edit']);

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$this->log])
        ->assertActionVisible(TestAction::make('view')->table($this->log))
        ->assertActionDoesNotExist(TestAction::make('edit')->table($this->log))
        ->assertActionDoesNotExist(TestAction::make('delete')->table($this->log))
        ->assertActionDoesNotExist('create');
});

it('denies create, update and delete even to the super admin', function () {
    expect($this->admin->can('view', $this->log))->toBeTrue()
        ->and($this->admin->can('create', AuditLog::class))->toBeFalse()
        ->and($this->admin->can('update', $this->log))->toBeFalse()
        ->and($this->admin->can('delete', $this->log))->toBeFalse()
        ->and($this->admin->can('deleteAny', AuditLog::class))->toBeFalse();
});

it('rejects updates and deletes at the model level', function () {
    expect(fn () => $this->log->update(['description' => 'alterado']))->toThrow(LogicException::class)
        ->and(fn () => $this->log->delete())->toThrow(LogicException::class)
        ->and($this->log->fresh()->description)->not->toBe('alterado')
        ->and(AuditLog::query()->whereKey($this->log->id)->exists())->toBeTrue();
});

it('hides the audit log from roles without the audit permission', function (UserRole $role) {
    actingAsRole($role);

    $this->get(AuditLogResource::getUrl('index'))->assertForbidden();
})->with([UserRole::Sales, UserRole::Editor, UserRole::Viewer]);
