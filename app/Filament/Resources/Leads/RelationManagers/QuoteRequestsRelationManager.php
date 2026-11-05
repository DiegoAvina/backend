<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use Filament\Tables\Columns\TextColumn;

class QuoteRequestsRelationManager extends LeadRequestsRelationManager
{
    protected static string $relationship = 'quoteRequests';

    protected static ?string $title = 'Cotizaciones';

    protected static string $requestResource = QuoteRequestResource::class;

    protected function summaryColumn(): TextColumn
    {
        return TextColumn::make('service.name')->label('Servicio')->placeholder('No definido');
    }
}
