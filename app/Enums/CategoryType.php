<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Ámbito de una categoría.
 */
enum CategoryType: string implements HasLabel
{
    case Product = 'product';
    case Article = 'article';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Product => 'Producto',
            self::Article => 'Artículo',
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
