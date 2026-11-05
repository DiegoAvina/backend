<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tema de un mensaje de contacto.
 */
enum ContactSubject: string implements HasLabel
{
    case Project = 'proyecto';
    case Products = 'productos';
    case Support = 'soporte';
    case Partnerships = 'alianzas';
    case Other = 'otro';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Project => 'Quiero platicar sobre un proyecto',
            self::Products => 'Información sobre productos',
            self::Support => 'Soporte o servicio existente',
            self::Partnerships => 'Alianzas y proveedores',
            self::Other => 'Otro tema',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
