<?php

namespace App\Filament\Support;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Leads\CommercialTracking;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Acciones y filtros de seguimiento comercial, compartidos por prospectos y
 * solicitudes. Cada acción se autoriza con la policy del registro.
 */
final class CommercialActions
{
    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('Cambiar estado')
            ->icon(Heroicon::OutlinedArrowPath)
            ->authorize('changeStatus')
            ->fillForm(fn (Model $record): array => ['status' => $record->getAttribute('status')])
            ->schema([
                Select::make('status')->label('Estado')->options(RequestStatus::class)->required(),
            ])
            ->action(fn (Model $record, array $data) => app(CommercialTracking::class)
                ->changeStatus($record, RequestStatus::from($data['status'] instanceof RequestStatus ? $data['status']->value : $data['status'])))
            ->successNotificationTitle('Estado actualizado');
    }

    public static function assign(): Action
    {
        return Action::make('assign')
            ->label('Asignar responsable')
            ->icon(Heroicon::OutlinedUserCircle)
            ->authorize('assign')
            ->fillForm(fn (Model $record): array => ['assigned_to' => $record->getAttribute('assigned_to')])
            ->schema([
                Select::make('assigned_to')
                    ->label('Responsable')
                    ->options(fn (): array => self::assignableUsers())
                    ->placeholder('Sin responsable')
                    ->searchable(),
            ])
            ->action(function (Model $record, array $data): void {
                $assignee = filled($data['assigned_to'] ?? null) ? User::query()->findOrFail($data['assigned_to']) : null;

                app(CommercialTracking::class)->assign($record, $assignee);
            })
            ->successNotificationTitle('Responsable actualizado');
    }

    public static function addNote(): Action
    {
        return Action::make('addNote')
            ->label('Agregar nota')
            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
            ->authorize('addNote')
            ->schema([
                Textarea::make('body')->label('Nota interna')->required()->maxLength(5000)->rows(4)
                    ->helperText('Visible solo para el equipo en el panel. Nunca se publica.'),
            ])
            ->action(fn (Model $record, array $data) => app(CommercialTracking::class)->addNote($record, $data['body'], Auth::user()))
            ->successNotificationTitle('Nota agregada');
    }

    /**
     * Usuarios activos que pueden ser responsables comerciales.
     *
     * @return array<int, string>
     */
    public static function assignableUsers(): array
    {
        return User::query()
            ->where('is_active', true)
            ->role([UserRole::SuperAdmin->value, UserRole::Admin->value, UserRole::Sales->value])
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Filtros comunes: estado, responsable, sin responsable y rango de fechas.
     *
     * @return list<BaseFilter>
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('status')->label('Estado')->options(RequestStatus::class)->multiple(),
            SelectFilter::make('assigned_to')->label('Responsable')->options(fn (): array => self::assignableUsers()),
            TernaryFilter::make('unassigned')
                ->label('Responsable asignado')
                ->trueLabel('Con responsable')
                ->falseLabel('Sin responsable')
                ->queries(
                    true: fn (Builder $query) => $query->whereNotNull('assigned_to'),
                    false: fn (Builder $query) => $query->whereNull('assigned_to'),
                ),
            Filter::make('created_at')
                ->label('Fecha')
                ->schema([
                    DatePicker::make('from')->label('Desde'),
                    DatePicker::make('until')->label('Hasta'),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date)))
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['from'] ?? null) {
                        $indicators[] = 'Desde '.$data['from'];
                    }
                    if ($data['until'] ?? null) {
                        $indicators[] = 'Hasta '.$data['until'];
                    }

                    return $indicators;
                }),
        ];
    }
}
