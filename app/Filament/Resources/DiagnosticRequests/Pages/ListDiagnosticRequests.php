<?php

namespace App\Filament\Resources\DiagnosticRequests\Pages;

use App\Filament\Resources\DiagnosticRequests\DiagnosticRequestResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Los registros comerciales solo llegan desde los formularios del sitio:
 * no hay acción de crear.
 */
class ListDiagnosticRequests extends ListRecords
{
    protected static string $resource = DiagnosticRequestResource::class;
}
