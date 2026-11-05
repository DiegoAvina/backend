<?php

namespace App\Enums;

/**
 * Estado de seguimiento de una solicitud comercial.
 */
enum RequestStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Closed = 'closed';
    case Spam = 'spam';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'Nueva',
            self::InProgress => 'En seguimiento',
            self::Closed => 'Cerrada',
            self::Spam => 'Spam',
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
