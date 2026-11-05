<?php

use App\Enums\UserRole;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

describe('panel authentication', function () {
    it('redirects guests to the login page', function () {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/leads')->assertRedirect('/admin/login');
    });

    it('lets active users with a role into the panel', function () {
        actingAsRole(UserRole::Viewer);

        $this->get('/admin')->assertOk();
    });

    it('rejects users without a role', function () {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertForbidden();
    });

    it('rejects deactivated users', function () {
        actingAsRole(UserRole::Admin, ['is_active' => false]);

        $this->get('/admin')->assertForbidden();
    });

    it('logs in with valid credentials and records the last login', function () {
        (new RolesAndPermissionsSeeder)->run();
        $user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'Secreta-Prueba-123']);
        $user->assignRole(UserRole::Admin->value);

        Livewire::test(Filament::getPanel('admin')->getLoginRouteAction())
            ->fillForm(['email' => 'admin@example.com', 'password' => 'Secreta-Prueba-123'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
        expect($user->fresh()->last_login_at)->not->toBeNull();
    });

    it('rejects invalid credentials', function () {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'Secreta-Prueba-123']);

        Livewire::test(Filament::getPanel('admin')->getLoginRouteAction())
            ->fillForm(['email' => 'admin@example.com', 'password' => 'incorrecta'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    });
});

describe('administrative areas by role', function () {
    it('restricts users, roles, settings and audit', function (UserRole $role, array $allowed) {
        actingAsRole($role);

        $areas = [
            'users' => UserResource::getUrl('index'),
            'roles' => RoleResource::getUrl('index'),
            'settings' => ManageSettings::getUrl(),
            'audit' => AuditLogResource::getUrl('index'),
        ];

        foreach ($areas as $area => $url) {
            $this->get($url)->assertStatus(in_array($area, $allowed, true) ? 200 : 403);
        }
    })->with([
        'super admin' => [UserRole::SuperAdmin, ['users', 'roles', 'settings', 'audit']],
        'admin' => [UserRole::Admin, ['users', 'settings', 'audit']],
        'sales' => [UserRole::Sales, []],
        'editor' => [UserRole::Editor, []],
        'viewer' => [UserRole::Viewer, []],
    ]);
});

describe('users', function () {
    it('creates a user with a role and audits it without the password', function () {
        actingAsRole(UserRole::Admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Nueva Comercial',
                'email' => 'comercial@example.com',
                'password' => 'Segura-Clave-2026',
                'role' => UserRole::Sales->value,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'comercial@example.com')->sole();

        expect($user->hasRole(UserRole::Sales->value))->toBeTrue()
            ->and(AuditLog::query()->where('event', 'role_changed')->exists())->toBeTrue()
            ->and(json_encode(AuditLog::query()->get()))->not->toContain('Segura-Clave-2026');
    });

    it('requires a strong password', function () {
        actingAsRole(UserRole::Admin);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'X', 'email' => 'x@example.com', 'password' => 'corta', 'role' => UserRole::Sales->value])
            ->call('create')
            ->assertHasFormErrors(['password']);
    });

    it('does not let an admin grant the super admin role', function () {
        actingAsRole(UserRole::Admin);

        expect(UserResource::assignableRoles())->not->toHaveKey(UserRole::SuperAdmin->value);

        // El valor no está entre las opciones: la validación lo rechaza antes de guardar.
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'X', 'email' => 'x@example.com', 'password' => 'Segura-Clave-2026', 'role' => UserRole::SuperAdmin->value])
            ->call('create')
            ->assertHasFormErrors(['role']);

        expect(User::query()->where('email', 'x@example.com')->exists())->toBeFalse();
    });

    it('does not let an admin edit a super admin', function () {
        actingAsRole(UserRole::Admin);
        $super = User::factory()->create();
        $super->assignRole(UserRole::SuperAdmin->value);

        $this->get(UserResource::getUrl('edit', ['record' => $super]))->assertForbidden();
    });

    it('does not let users deactivate themselves', function () {
        $admin = actingAsRole(UserRole::Admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->fillForm(['is_active' => false, 'role' => UserRole::Admin->value])
            ->call('save');

        expect($admin->fresh()->is_active)->toBeTrue();
    });

    it('deactivates another user and audits it', function () {
        actingAsRole(UserRole::Admin);
        $other = User::factory()->create();
        $other->assignRole(UserRole::Editor->value);

        Livewire::test(EditUser::class, ['record' => $other->getRouteKey()])
            ->fillForm(['is_active' => false, 'role' => UserRole::Editor->value])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($other->fresh()->is_active)->toBeFalse()
            ->and(AuditLog::query()->where('event', 'user_deactivated')->exists())->toBeTrue();
    });

    it('never deletes users', function () {
        $super = actingAsRole(UserRole::SuperAdmin);

        expect($super->can('delete', User::factory()->create()))->toBeFalse();
    });
});

describe('roles and permissions', function () {
    it('lets the super admin change permissions and audits the change', function () {
        actingAsRole(UserRole::SuperAdmin);
        $editor = Role::findByName(UserRole::Editor->value);
        $before = $editor->permissions()->pluck('id')->all();
        $withoutPublish = $editor->permissions()->where('name', '!=', 'articles.publish')->pluck('id')->all();

        Livewire::test(EditRole::class, ['record' => $editor->getRouteKey()])
            ->fillForm(['permissions' => $withoutPublish])
            ->call('save')
            ->assertHasNoFormErrors();

        $log = AuditLog::query()->where('event', 'permissions_updated')->sole();

        expect($editor->fresh()->hasPermissionTo('articles.publish'))->toBeFalse()
            ->and(count($before) - 1)->toBe($editor->permissions()->count())
            ->and($log->old_values['removed'])->toBe(['articles.publish']);
    });

    it('does not allow editing the super admin role', function () {
        actingAsRole(UserRole::SuperAdmin);
        $role = Role::findByName(UserRole::SuperAdmin->value);

        $this->get(RoleResource::getUrl('edit', ['record' => $role]))->assertForbidden();
    });

    it('defines the expected default permissions', function () {
        expect(UserRole::Sales->defaultPermissions())
            ->toContain('leads.update', 'notes.create', 'products.view')
            ->not->toContain('users.manage', 'settings.manage', 'products.update')
            ->and(UserRole::Editor->defaultPermissions())
            ->toContain('products.publish', 'articles.create')
            ->not->toContain('leads.view', 'quote_requests.view', 'users.manage')
            ->and(collect(UserRole::Viewer->defaultPermissions())->every(
                fn (string $permission): bool => str_ends_with($permission, '.view') || str_starts_with($permission, 'dashboard.'),
            ))->toBeTrue();
    });
});
