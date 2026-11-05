<?php

namespace App\Filament\Support\RelationManagers;

use App\Services\Leads\CommercialTracking;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Notas internas de un prospecto o solicitud. Las notas no se editan ni se
 * eliminan: forman parte del historial de seguimiento.
 */
class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notas internas';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body')->label('Nota')->wrap()->searchable(),
                TextColumn::make('author.name')->label('Autor')->placeholder('Sistema'),
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('addNote')
                    ->label('Agregar nota')
                    ->icon(Heroicon::OutlinedPlus)
                    ->visible(fn (): bool => Gate::allows('addNote', $this->getOwnerRecord()))
                    ->schema([
                        Textarea::make('body')->label('Nota interna')->required()->maxLength(5000)->rows(4),
                    ])
                    ->action(function (array $data): void {
                        Gate::authorize('addNote', $this->getOwnerRecord());
                        app(CommercialTracking::class)->addNote($this->getOwnerRecord(), $data['body'], Auth::user());
                    })
                    ->successNotificationTitle('Nota agregada'),
            ])
            ->emptyStateHeading('Sin notas')
            ->emptyStateDescription('Registra aquí llamadas, acuerdos y próximos pasos.');
    }
}
