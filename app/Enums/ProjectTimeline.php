<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Plazo en que el prospecto quiere comenzar.
 */
enum ProjectTimeline: string implements HasLabel
{
    case Immediate = 'inmediato';
    case OneToThreeMonths = '1-3-meses';
    case ThreeToSixMonths = '3-6-meses';
    case Exploring = 'explorando';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Lo antes posible',
            self::OneToThreeMonths => 'En 1 a 3 meses',
            self::ThreeToSixMonths => 'En 3 a 6 meses',
            self::Exploring => 'Solo estoy explorando opciones',
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
