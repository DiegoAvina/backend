<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\EditContentRecord;

class EditCategory extends EditContentRecord
{
    protected static string $resource = CategoryResource::class;
}
