<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Tamaño de la empresa del prospecto.
 */
enum CompanySize: string implements HasLabel
{
    case Micro = '1-10';
    case Small = '11-50';
    case Medium = '51-250';
    case Large = 'mas-250';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Micro => '1 a 10 colaboradores',
            self::Small => '11 a 50 colaboradores',
            self::Medium => '51 a 250 colaboradores',
            self::Large => 'Más de 250 colaboradores',
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
