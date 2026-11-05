<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Support\ViewCommercialRecord;

class ViewContactMessage extends ViewCommercialRecord
{
    protected static string $resource = ContactMessageResource::class;
}
