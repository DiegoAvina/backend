<?php

namespace App\Filament\Resources\Solutions\Pages;

use App\Filament\Resources\Solutions\SolutionResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * El contenido nuevo nace sin publicar; se publica con la acción "Publicar".
 */
class CreateSolution extends CreateRecord
{
    protected static string $resource = SolutionResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
