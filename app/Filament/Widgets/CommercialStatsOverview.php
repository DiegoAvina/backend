<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CommercialStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $heading = 'Indicadores comerciales';

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $kpis = app(DashboardMetrics::class)->commercialKpis();

        return [
            Stat::make('Total de prospectos', $kpis['leads'])->icon(Heroicon::OutlinedUsers),
            Stat::make('Prospectos nuevos', $kpis['new_leads'])->description('Sin contactar')->color('info')->icon(Heroicon::OutlinedSparkles),
            Stat::make('Prospectos convertidos', $kpis['converted'])->color('success')->icon(Heroicon::OutlinedCheckBadge),
            Stat::make('Cotizaciones recibidas', $kpis['quotes'])->icon(Heroicon::OutlinedCalculator),
            Stat::make('Solicitudes de demostración', $kpis['demos'])->icon(Heroicon::OutlinedPresentationChartBar),
            Stat::make('Diagnósticos solicitados', $kpis['diagnostics'])->icon(Heroicon::OutlinedClipboardDocumentCheck),
        ];
    }
}
