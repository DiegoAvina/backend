<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardMetrics;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class LeadsPerMonthChart extends ChartWidget
{
    protected static ?int $sort = 30;

    protected ?string $heading = 'Prospectos por mes';

    protected ?string $description = 'Últimos 12 meses';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public static function canView(): bool
    {
        return Auth::user()?->can('dashboard.commercial') ?? false;
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $series = app(DashboardMetrics::class)->leadsPerMonth();

        return [
            'datasets' => [[
                'label' => 'Prospectos',
                'data' => array_values($series),
                'fill' => true,
            ]],
            'labels' => array_map(
                fn (string $month): string => CarbonImmutable::createFromFormat('Y-m', $month)->locale('es')->isoFormat('MMM YY'),
                array_keys($series),
            ),
        ];
    }
}
