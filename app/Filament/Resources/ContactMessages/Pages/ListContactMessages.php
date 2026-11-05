<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Los registros comerciales solo llegan desde los formularios del sitio:
 * no hay acción de crear.
 */
class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;
}
