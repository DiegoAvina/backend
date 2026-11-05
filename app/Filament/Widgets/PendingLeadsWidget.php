<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Prospectos nuevos que aún no se contactan, los más antiguos primero.
 */
class PendingLeadsWidget extends TableWidget
{
    protected static ?int $sort = 90;

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Prospectos pendientes')
            ->query(Lead::query()->where('status', RequestStatus::New)->with('assignee')->oldest())
            ->columns([
                TextColumn::make('name')->label('Nombre')->description(fn (Lead $record) => $record->company),
                TextColumn::make('assignee.name')->label('Responsable')->placeholder('Sin asignar'),
                TextColumn::make('created_at')->label('Alta')->since(),
            ])
            ->recordActions([
                Action::make('open')->label('Ver')->url(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Sin prospectos pendientes');
    }
}
