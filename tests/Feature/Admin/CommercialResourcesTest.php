<?php

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\QuoteRequestsRelationManager;
use App\Filament\Resources\QuoteRequests\Pages\ListQuoteRequests;
use App\Filament\Resources\QuoteRequests\Pages\ViewQuoteRequest;
use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\Filament\Support\CommercialActions;
use App\Filament\Support\RelationManagers\NotesRelationManager;
use App\Filament\Support\RelationManagers\StatusHistoryRelationManager;
use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

describe('consulting records', function () {
    it('searches leads by name and email', function () {
        actingAsRole(UserRole::Sales);
        $ana = Lead::factory()->create(['name' => 'Ana Pérez', 'email' => 'ana@empresa.mx']);
        $luis = Lead::factory()->create(['name' => 'Luis Gómez', 'email' => 'luis@otra.mx']);

        Livewire::test(ListLeads::class)
            ->searchTable('Pérez')->assertCanSeeTableRecords([$ana])->assertCanNotSeeTableRecords([$luis])
            ->searchTable('otra.mx')->assertCanSeeTableRecords([$luis])->assertCanNotSeeTableRecords([$ana]);
    });

    it('searches requests by the lead name and email', function () {
        actingAsRole(UserRole::Sales);
        $match = QuoteRequest::factory()->for(Lead::factory()->state(['email' => 'compras@acme.mx']))->create();
        $other = QuoteRequest::factory()->create();

        Livewire::test(ListQuoteRequests::class)
            ->searchTable('compras@acme.mx')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    });

    it('filters by status and date range', function () {
        actingAsRole(UserRole::Sales);
        $old = Lead::factory()->create(['created_at' => now()->subMonths(2)]);
        $recent = Lead::factory()->create();
        $recent->forceFill(['status' => RequestStatus::Contacted])->save();

        Livewire::test(ListLeads::class)
            ->filterTable('status', [RequestStatus::Contacted->value])
            ->assertCanSeeTableRecords([$recent])->assertCanNotSeeTableRecords([$old])
            ->resetTableFilters()
            ->filterTable('created_at', ['from' => now()->subWeek()->toDateString()])
            ->assertCanSeeTableRecords([$recent])->assertCanNotSeeTableRecords([$old]);
    });

    it('shows the lead profile with interests and related requests', function () {
        actingAsRole(UserRole::Sales);
        $lead = Lead::factory()->create(['company' => 'Acme']);
        $quote = QuoteRequest::factory()->for($lead)->for(Service::factory()->state(['name' => 'Desarrollo web']))->create();

        expect(LeadResource::interests($lead))->toBe(['Servicio: Desarrollo web']);

        Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('Acme')
            ->assertSee('Servicio: Desarrollo web');

        Livewire::test(QuoteRequestsRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => ViewLead::class])
            ->assertCanSeeTableRecords([$quote]);
    });

    it('has no routes to create, edit or delete commercial records', function () {
        expect(LeadResource::getPages())->toHaveKeys(['index', 'view'])->not->toHaveKeys(['create', 'edit'])
            ->and(QuoteRequestResource::getPages())->not->toHaveKeys(['create', 'edit']);
    });
});

describe('follow-up actions', function () {
    it('changes the status and records who did it in the history and audit log', function () {
        $user = actingAsRole(UserRole::Sales);
        $quote = QuoteRequest::factory()->create();

        Livewire::test(ViewQuoteRequest::class, ['record' => $quote->getRouteKey()])
            ->callAction('changeStatus', ['status' => RequestStatus::Negotiating->value])
            ->assertHasNoActionErrors();

        $change = $quote->statusChanges()->first();

        expect($quote->fresh()->status)->toBe(RequestStatus::Negotiating)
            ->and($change->from_status)->toBe(RequestStatus::New)
            ->and($change->to_status)->toBe(RequestStatus::Negotiating)
            ->and($change->user_id)->toBe($user->id)
            ->and(AuditLog::query()->where('event', 'status_changed')->where('user_id', $user->id)->exists())->toBeTrue();

        Livewire::test(StatusHistoryRelationManager::class, ['ownerRecord' => $quote, 'pageClass' => ViewQuoteRequest::class])
            ->assertCanSeeTableRecords([$change]);
    });

    it('records the initial status of submissions from the website', function () {
        $this->postJson('/api/v1/leads', leadPayload(), serviceHeaders())->assertCreated();

        $change = Lead::query()->sole()->statusChanges()->sole();

        expect($change->from_status)->toBeNull()
            ->and($change->to_status)->toBe(RequestStatus::New)
            ->and($change->user_id)->toBeNull();
    });

    it('assigns a responsible user', function () {
        actingAsRole(UserRole::Sales);
        $seller = User::factory()->create(['name' => 'Vendedora']);
        $seller->assignRole(UserRole::Sales->value);
        $lead = Lead::factory()->create();

        Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
            ->callAction('assign', ['assigned_to' => $seller->id])
            ->assertHasNoActionErrors();

        expect($lead->fresh()->assigned_to)->toBe($seller->id)
            ->and(AuditLog::query()->where('event', 'assigned')->exists())->toBeTrue();
    });

    it('offers only active commercial users as responsible', function () {
        actingAsRole(UserRole::Sales, ['name' => 'Yo']);
        User::factory()->create(['name' => 'Inactivo', 'is_active' => false])->assignRole(UserRole::Sales->value);
        User::factory()->create(['name' => 'Editor'])->assignRole(UserRole::Editor->value);

        expect(array_values(CommercialActions::assignableUsers()))->toBe(['Yo']);
    });

    it('adds internal notes with author and date', function () {
        $user = actingAsRole(UserRole::Sales);
        $lead = Lead::factory()->create();

        Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
            ->callAction('addNote', ['body' => 'Llamar el lunes para presentar propuesta.'])
            ->assertHasNoActionErrors();

        $note = $lead->notes()->sole();

        expect($note->body)->toBe('Llamar el lunes para presentar propuesta.')
            ->and($note->user_id)->toBe($user->id)
            ->and($note->created_at)->not->toBeNull();

        Livewire::test(NotesRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => ViewLead::class])
            ->assertCanSeeTableRecords([$note]);
    });

    it('rejects empty notes', function () {
        actingAsRole(UserRole::Sales);
        $lead = Lead::factory()->create();

        Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
            ->callAction('addNote', ['body' => ''])
            ->assertHasActionErrors(['body' => 'required']);

        expect($lead->notes()->count())->toBe(0);
    });
});

describe('access by role', function () {
    it('allows the commercial area only to roles with commercial permissions', function (UserRole $role, int $status) {
        actingAsRole($role);
        $lead = Lead::factory()->create();

        $this->get(LeadResource::getUrl('index'))->assertStatus($status);
        $this->get(LeadResource::getUrl('view', ['record' => $lead]))->assertStatus($status);
        $this->get(QuoteRequestResource::getUrl('index'))->assertStatus($status);
    })->with([
        'super admin' => [UserRole::SuperAdmin, 200],
        'admin' => [UserRole::Admin, 200],
        'sales' => [UserRole::Sales, 200],
        'viewer' => [UserRole::Viewer, 200],
        'editor' => [UserRole::Editor, 403],
    ]);

    it('lets the viewer role consult but not modify', function () {
        actingAsRole(UserRole::Viewer);
        $lead = Lead::factory()->create();

        Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
            ->assertActionHidden('changeStatus')
            ->assertActionHidden('assign')
            ->assertActionHidden('addNote');

        Livewire::test(NotesRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => ViewLead::class])
            ->assertActionHidden(TestAction::make('addNote')->table());
    });

    it('respects a revoked permission', function () {
        actingAsRole(UserRole::Sales);
        Role::findByName(UserRole::Sales->value)->revokePermissionTo('leads.assign');
        $lead = Lead::factory()->create();

        Livewire::test(ViewLead::class, ['record' => $lead->getRouteKey()])
            ->assertActionVisible('changeStatus')
            ->assertActionHidden('assign');
    });

    it('never allows deleting commercial records, not even for the super admin', function () {
        $admin = actingAsRole(UserRole::SuperAdmin);
        $lead = Lead::factory()->create();

        expect($admin->can('delete', $lead))->toBeFalse()
            ->and($admin->can('update', $lead))->toBeFalse()
            ->and($admin->can('create', Lead::class))->toBeFalse();
    });
});
