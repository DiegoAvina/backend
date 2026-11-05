<?php

namespace App\Enums;

/**
 * Modalidad preferida para una reunión de diagnóstico.
 */
enum ContactMethod: string
{
    case VideoCall = 'videollamada';
    case PhoneCall = 'llamada';
    case InPerson = 'presencial';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::VideoCall => 'Videollamada',
            self::PhoneCall => 'Llamada telefónica',
            self::InPerson => 'Reunión presencial',
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
