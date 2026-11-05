<?php

namespace App\Enums;

/**
 * Canal por el que llegó un prospecto.
 */
enum LeadSource: string
{
    case General = 'general';
    case Contact = 'contact';
    case Quote = 'quote';
    case Demo = 'demo';
    case Diagnostic = 'diagnostic';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::General => 'Captura general',
            self::Contact => 'Contacto',
            self::Quote => 'Cotización',
            self::Demo => 'Demo o acceso anticipado',
            self::Diagnostic => 'Diagnóstico',
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
