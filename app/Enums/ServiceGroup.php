<?php

namespace App\Enums;

/**
 * Línea de servicio a la que pertenece un servicio.
 */
enum ServiceGroup: string
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
