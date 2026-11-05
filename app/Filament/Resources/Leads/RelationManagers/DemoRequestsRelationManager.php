<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Filament\Resources\DemoRequests\DemoRequestResource;
use Filament\Tables\Columns\TextColumn;

class DemoRequestsRelationManager extends LeadRequestsRelationManager
{
    protected static string $relationship = 'demoRequests';

    protected static ?string $title = 'Demos';

    protected static string $requestResource = DemoRequestResource::class;

    protected function summaryColumn(): TextColumn
    {
        return TextColumn::make('product.name')->label('Producto')->placeholder('—');
    }
}
