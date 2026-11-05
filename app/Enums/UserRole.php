<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Roles del panel y su catálogo de permisos.
 *
 * Los permisos se guardan en BD (spatie/laravel-permission) para poder
 * ajustarlos desde el panel; `defaultPermissions()` define la asignación
 * inicial que siembra `RolesAndPermissionsSeeder`.
 */
enum UserRole: string implements HasLabel
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Sales = 'sales';
    case Editor = 'editor';
    case Viewer = 'viewer';

    /** Módulos de contenido público. */
    public const CONTENT_MODULES = ['products', 'services', 'solutions', 'industries', 'categories', 'articles'];

    /** Módulos con datos personales de prospectos. */
    public const COMMERCIAL_MODULES = ['leads', 'quote_requests', 'demo_requests', 'diagnostic_requests', 'contact_messages'];

    /** Acciones sobre contenido. `publish` no aplica a categorías. */
    public const CONTENT_ACTIONS = ['view', 'create', 'update', 'delete', 'publish'];

    /** Acciones comerciales: consultar, cambiar estado y asignar responsable. */
    public const COMMERCIAL_ACTIONS = ['view', 'update', 'assign'];

    /** Permisos globales. */
    public const GLOBAL_PERMISSIONS = [
        'notes.create',
        'dashboard.content',
        'dashboard.commercial',
        'users.manage',
        'roles.manage',
        'settings.manage',
        'audit.view',
    ];

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Superadministrador',
            self::Admin => 'Administrador',
            self::Sales => 'Comercial',
            self::Editor => 'Editor',
            self::Viewer => 'Consulta',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * Todos los permisos de la aplicación.
     *
     * @return list<string>
     */
    public static function allPermissions(): array
    {
        return [...self::contentPermissions(), ...self::commercialPermissions(), ...self::GLOBAL_PERMISSIONS];
    }

    /**
     * Permisos iniciales del rol.
     *
     * @return list<string>
     */
    public function defaultPermissions(): array
    {
        $viewOnly = fn (array $permissions): array => array_values(array_filter(
            $permissions,
            fn (string $permission): bool => str_ends_with($permission, '.view'),
        ));

        return match ($this) {
            self::SuperAdmin => self::allPermissions(),
            self::Admin => array_values(array_diff(self::allPermissions(), ['roles.manage'])),
            self::Sales => [
                ...self::commercialPermissions(),
                ...$viewOnly(self::contentPermissions()),
                'notes.create',
                'dashboard.commercial',
            ],
            self::Editor => [...self::contentPermissions(), 'dashboard.content'],
            self::Viewer => [
                ...$viewOnly(self::contentPermissions()),
                ...$viewOnly(self::commercialPermissions()),
                'dashboard.content',
                'dashboard.commercial',
            ],
        };
    }

    /**
     * @return list<string>
     */
    public static function contentPermissions(): array
    {
        $permissions = [];

        foreach (self::CONTENT_MODULES as $module) {
            foreach (self::CONTENT_ACTIONS as $action) {
                if ($module === 'categories' && $action === 'publish') {
                    continue;
                }

                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function commercialPermissions(): array
    {
        $permissions = [];

        foreach (self::COMMERCIAL_MODULES as $module) {
            foreach (self::COMMERCIAL_ACTIONS as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return $permissions;
    }

    /**
     * Etiqueta legible de un permiso, para el panel.
     */
    public static function permissionLabel(string $permission): string
    {
        [$module, $action] = explode('.', $permission, 2);

        $modules = [
            'products' => 'Productos', 'services' => 'Servicios', 'solutions' => 'Soluciones',
            'industries' => 'Industrias', 'categories' => 'Categorías', 'articles' => 'Artículos',
            'leads' => 'Prospectos', 'quote_requests' => 'Cotizaciones', 'demo_requests' => 'Demos',
            'diagnostic_requests' => 'Diagnósticos', 'contact_messages' => 'Mensajes de contacto',
            'notes' => 'Notas', 'dashboard' => 'Dashboard', 'users' => 'Usuarios', 'roles' => 'Roles y permisos',
            'settings' => 'Configuración', 'audit' => 'Auditoría',
        ];

        $actions = [
            'view' => 'consultar', 'create' => 'crear', 'update' => 'editar', 'delete' => 'eliminar',
            'publish' => 'publicar', 'assign' => 'asignar responsable', 'manage' => 'administrar',
            'content' => 'indicadores de contenido', 'commercial' => 'indicadores comerciales',
        ];

        if ($module === 'quote_requests' || $module === 'demo_requests' || $module === 'diagnostic_requests'
            || $module === 'contact_messages' || $module === 'leads') {
            $actions['update'] = 'cambiar estado';
        }

        return ($modules[$module] ?? $module).': '.($actions[$action] ?? $action);
    }
}
