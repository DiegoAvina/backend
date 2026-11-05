<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Modalidad preferida para una reunión de diagnóstico.
 */
enum ContactMethod: string implements HasLabel
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
