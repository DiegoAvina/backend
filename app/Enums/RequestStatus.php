<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Estado comercial de un prospecto o de una solicitud.
 */
enum RequestStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Contacted = 'contacted';
    case Negotiating = 'negotiating';
    case Converted = 'converted';
    case Discarded = 'discarded';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Contacted => 'Contactado',
            self::Negotiating => 'En negociación',
            self::Converted => 'Convertido',
            self::Discarded => 'Descartado',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Contacted => 'warning',
            self::Negotiating => 'primary',
            self::Converted => 'success',
            self::Discarded => 'gray',
        };
    }

    /**
     * Estados que todavía requieren seguimiento comercial.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::New, self::Contacted, self::Negotiating];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
