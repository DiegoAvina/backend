<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetrics;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Solicitudes abiertas sin responsable o sin movimiento en los últimos días.
 */
class FollowUpWidget extends TableWidget
{
    protected static ?int $sort = 80;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Necesitan seguimiento')
            ->description('Abiertas sin responsable o sin movimiento en '.DashboardMetrics::FOLLOW_UP_AFTER_DAYS.' días.')
            ->records(fn (): array => static::canView() ? app(DashboardMetrics::class)->requestsNeedingFollowUp() : [])
            ->columns(RequestRowColumns::make())
            ->recordActions([
                Action::make('open')->label('Atender')->url(fn (array $record): string => $record['url']),
            ])
            ->paginated(false)
            ->emptyStateHeading('Todo al día');
    }
}
