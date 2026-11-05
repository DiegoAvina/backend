<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetrics;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Últimas solicitudes de cualquier tipo. Los datos se filtran por permiso
 * antes de construir la tabla (los registros en arreglo no pasan por policies).
 */
class LatestRequestsWidget extends TableWidget
{
    protected static ?int $sort = 70;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Últimas solicitudes')
            ->records(fn (): array => static::canView() ? app(DashboardMetrics::class)->latestRequests() : [])
            ->columns(RequestRowColumns::make())
            ->recordActions([
                Action::make('open')->label('Ver')->url(fn (array $record): string => $record['url']),
            ])
            ->paginated(false)
            ->emptyStateHeading('Aún no hay solicitudes');
    }
}
