<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Línea de servicio a la que pertenece un servicio.
 */
enum ServiceGroup: string implements HasLabel
{
    case Software = 'software';
    case AutomationAi = 'automation_ai';
    case Infrastructure = 'infrastructure';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Software => 'Software & Development',
            self::AutomationAi => 'Automation & AI',
            self::Infrastructure => 'IT Infrastructure',
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
