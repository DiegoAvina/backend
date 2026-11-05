<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Filament\Resources\DiagnosticRequests\DiagnosticRequestResource;
use Filament\Tables\Columns\TextColumn;

class DiagnosticRequestsRelationManager extends LeadRequestsRelationManager
{
    protected static string $relationship = 'diagnosticRequests';

    protected static ?string $title = 'Diagnósticos';

    protected static string $requestResource = DiagnosticRequestResource::class;

    protected function summaryColumn(): TextColumn
    {
        return TextColumn::make('solution.name')->label('Tema')->placeholder('Orientación general');
    }
}
