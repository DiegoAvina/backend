<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetrics;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class LeadSourcesChart extends ChartWidget
{
    protected static ?int $sort = 60;

    protected ?string $heading = 'Fuentes de captación';

    protected ?string $maxHeight = '240px';

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $sources = app(DashboardMetrics::class)->leadSources();

        return [
            'datasets' => [[
                'label' => 'Prospectos',
                'data' => array_values($sources),
                'backgroundColor' => ['#9CA3AF', '#36A9E1', '#176B9B', '#F59E0B', '#16A34A'],
            ]],
            'labels' => array_keys($sources),
        ];
    }
}
