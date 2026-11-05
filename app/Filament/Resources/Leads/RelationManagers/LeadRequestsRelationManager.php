<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Solicitudes de un prospecto (solo consulta, con enlace a su ficha).
 */
abstract class LeadRequestsRelationManager extends RelationManager
{
    /** @var class-string<\Filament\Resources\Resource> */
    protected static string $requestResource;

    /**
     * Columna que resume la solicitud (servicio, producto, tema…).
     */
    abstract protected function summaryColumn(): TextColumn;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                $this->summaryColumn(),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('assignee.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('created_at')->label('Recibida')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('open')
                    ->label('Ver')
                    ->url(fn (Model $record): string => static::$requestResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
