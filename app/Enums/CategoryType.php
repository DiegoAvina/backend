<?php

namespace App\Enums;

/**
 * Ámbito de una categoría.
 */
enum CategoryType: string
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
