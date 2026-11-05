<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetrics;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class LeadStatusChart extends ChartWidget
{
    protected static ?int $sort = 50;

    protected ?string $heading = 'Distribución de estados';

    protected ?string $maxHeight = '240px';

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $distribution = app(DashboardMetrics::class)->leadStatusDistribution();

        return [
            'datasets' => [[
                'label' => 'Prospectos',
                'data' => array_values($distribution),
                'backgroundColor' => ['#36A9E1', '#F59E0B', '#176B9B', '#16A34A', '#9CA3AF'],
            ]],
            'labels' => array_keys($distribution),
        ];
    }
}
