<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\EditContentRecord;

class EditProduct extends EditContentRecord
{
    protected static string $resource = ProductResource::class;
}
