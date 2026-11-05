<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * El contenido nuevo nace sin publicar; se publica con la acción "Publicar".
 */
class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
