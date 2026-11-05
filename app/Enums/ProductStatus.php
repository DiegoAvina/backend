<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado comercial real de un producto. Nunca se presenta uno en desarrollo
 * como disponible. Un producto descontinuado no aparece en los listados públicos.
 */
enum ProductStatus: string implements HasColor, HasLabel
{
    case InDevelopment = 'in_development';
    case Beta = 'beta';
    case Available = 'available';
    case Discontinued = 'discontinued';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::InDevelopment => 'En desarrollo',
            self::Beta => 'Beta',
            self::Available => 'Disponible',
            self::Discontinued => 'Descontinuado',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InDevelopment => 'warning',
            self::Beta => 'info',
            self::Available => 'success',
            self::Discontinued => 'gray',
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
