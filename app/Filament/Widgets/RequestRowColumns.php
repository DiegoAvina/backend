<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;

/**
 * Columnas de las tablas de solicitudes del dashboard.
 */
final class RequestRowColumns
{
    /**
     * @return list<TextColumn>
     */
    public static function make(): array
    {
        return [
            TextColumn::make('type')->label('Tipo')->badge(),
            TextColumn::make('name')->label('Prospecto')->description(fn (array $record): ?string => $record['company']),
            TextColumn::make('status')->label('Estado')->badge(),
            TextColumn::make('created_at')->label('Recibida')->since(),
        ];
    }
}
