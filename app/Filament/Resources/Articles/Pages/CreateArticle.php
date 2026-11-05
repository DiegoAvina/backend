<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * El contenido nuevo nace sin publicar; se publica con la acción "Publicar".
 */
class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
