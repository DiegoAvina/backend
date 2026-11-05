<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Estado editorial de un artículo.
 */
enum ArticleStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Etiqueta legible en español.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicado',
            self::Archived => 'Archivado',
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
