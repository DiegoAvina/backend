<?php

namespace App\Filament\Resources\QuoteRequests\Pages;

use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Los registros comerciales solo llegan desde los formularios del sitio:
 * no hay acción de crear.
 */
class ListQuoteRequests extends ListRecords
{
    protected static string $resource = QuoteRequestResource::class;
}
