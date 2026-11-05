<?php

namespace App\Enums;

/**
 * Rango de presupuesto declarado en una cotización (MXN).
 */
enum BudgetRange: string
{
    case ToBeDefined = 'por-definir';
    case Under100k = 'menos-100k';
    case From100kTo300k = '100k-300k';
    case From300kTo750k = '300k-750k';
    case Over750k = 'mas-750k';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::ToBeDefined => 'Por definir',
            self::Under100k => 'Menos de $100,000 MXN',
            self::From100kTo300k => '$100,000 a $300,000 MXN',
            self::From300kTo750k => '$300,000 a $750,000 MXN',
            self::Over750k => 'Más de $750,000 MXN',
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
