<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Support\ViewCommercialRecord;

class ViewLead extends ViewCommercialRecord
{
    protected static string $resource = LeadResource::class;
}
