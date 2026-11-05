<?php

namespace App\Enums;

/**
 * Estado comercial real de un producto. Nunca se presenta uno en desarrollo como disponible.
 */
enum ProductStatus: string
{
    case InDevelopment = 'in_development';
    case Beta = 'beta';
    case Available = 'available';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::InDevelopment => 'En desarrollo',
            self::Beta => 'Beta',
            self::Available => 'Disponible',
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
