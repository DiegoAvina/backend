<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ContentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 20;

    protected ?string $heading = 'Contenido';

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.content') ?? false;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $kpis = app(DashboardMetrics::class)->contentKpis();

        return [
            Stat::make('Productos publicados', $kpis['published_products'])->icon(Heroicon::OutlinedCube),
            Stat::make('Artículos publicados', $kpis['published_articles'])->icon(Heroicon::OutlinedNewspaper),
            Stat::make('Artículos en borrador', $kpis['draft_articles'])->color('gray')->icon(Heroicon::OutlinedPencilSquare),
        ];
    }
}
