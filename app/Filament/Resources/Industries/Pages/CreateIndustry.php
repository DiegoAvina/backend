<?php

namespace App\Filament\Resources\Industries\Pages;

use App\Filament\Resources\Industries\IndustryResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * El contenido nuevo nace sin publicar; se publica con la acción "Publicar".
 */
class CreateIndustry extends CreateRecord
{
    protected static string $resource = IndustryResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
