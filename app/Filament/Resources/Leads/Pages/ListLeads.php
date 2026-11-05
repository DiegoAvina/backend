<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Los registros comerciales solo llegan desde los formularios del sitio:
 * no hay acción de crear.
 */
class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;
}
