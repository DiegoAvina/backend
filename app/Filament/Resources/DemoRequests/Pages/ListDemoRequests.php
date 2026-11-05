<?php

namespace App\Filament\Resources\DemoRequests\Pages;

use App\Filament\Resources\DemoRequests\DemoRequestResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Los registros comerciales solo llegan desde los formularios del sitio:
 * no hay acción de crear.
 */
class ListDemoRequests extends ListRecords
{
    protected static string $resource = DemoRequestResource::class;
}
