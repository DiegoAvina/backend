<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetrics;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class RequestsByTypeChart extends ChartWidget
{
    protected static ?int $sort = 40;

    protected ?string $heading = 'Solicitudes por categoría';

    protected ?string $maxHeight = '240px';

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $totals = app(DashboardMetrics::class)->requestsByType();

        return [
            'datasets' => [['label' => 'Solicitudes', 'data' => array_values($totals)]],
            'labels' => array_keys($totals),
        ];
    }
}
