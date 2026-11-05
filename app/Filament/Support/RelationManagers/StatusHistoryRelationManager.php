<?php

namespace App\Filament\Support\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historial inmutable de cambios de estado.
 */
class StatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'statusChanges';

    protected static ?string $title = 'Historial de estados';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_status')->label('De')->badge()->placeholder('—'),
                TextColumn::make('to_status')->label('A')->badge(),
                TextColumn::make('user.name')->label('Usuario')->placeholder('Sitio web'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25]);
    }
}
