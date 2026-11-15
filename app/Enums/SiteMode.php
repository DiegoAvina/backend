<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Modo de publicación del sitio público. Cualquier modo distinto de `Live`
 * reemplaza todas las páginas del sitio por una vista de aviso.
 */
enum SiteMode: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Live = 'live';
    case ComingSoon = 'coming_soon';
    case Maintenance = 'maintenance';
    case TemporarilyClosed = 'temporarily_closed';

    public function label(): string
    {
        return match ($this) {
            self::Live => 'En línea',
            self::ComingSoon => 'Próximamente',
            self::Maintenance => 'En mantenimiento',
            self::TemporarilyClosed => 'Cerrado temporalmente',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Live => 'El sitio se muestra normalmente.',
            self::ComingSoon => 'Aviso de lanzamiento, con cuenta regresiva si hay fecha.',
            self::Maintenance => 'Aviso de mantenimiento con regreso estimado.',
            self::TemporarilyClosed => 'Pausa por vacaciones u otra razón.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Live => 'success',
            self::ComingSoon => 'info',
            self::Maintenance => 'warning',
            self::TemporarilyClosed => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Live => Heroicon::OutlinedGlobeAlt,
            self::ComingSoon => Heroicon::OutlinedRocketLaunch,
            self::Maintenance => Heroicon::OutlinedWrenchScrewdriver,
            self::TemporarilyClosed => Heroicon::OutlinedPauseCircle,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
