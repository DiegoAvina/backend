<?php

namespace App\Enums;

/**
 * Tema de un mensaje de contacto.
 */
enum ContactSubject: string
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
