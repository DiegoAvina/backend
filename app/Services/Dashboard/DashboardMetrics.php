<?php

namespace App\Services\Dashboard;

use App\Enums\ArticleStatus;
use App\Enums\LeadSource;
use App\Enums\RequestStatus;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\DemoRequests\DemoRequestResource;
use App\Filament\Resources\DiagnosticRequests\DiagnosticRequestResource;
use App\Filament\Resources\QuoteRequests\QuoteRequestResource;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\DiagnosticRequest;
use App\Models\Lead;
use App\Models\Product;
use App\Models\QuoteRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Indicadores del dashboard, calculados siempre desde la base de datos.
 * Las agrupaciones por mes se hacen en PHP para no depender del motor SQL.
 */
class DashboardMetrics
{
    /** Días sin movimiento a partir de los cuales una solicitud abierta necesita seguimiento. */
    public const FOLLOW_UP_AFTER_DAYS = 3;

    /**
     * @var array<class-string, array{label: string, resource: class-string}>
     */
    public const REQUEST_TYPES = [
        QuoteRequest::class => ['label' => 'Cotización', 'resource' => QuoteRequestResource::class],
        DemoRequest::class => ['label' => 'Demo', 'resource' => DemoRequestResource::class],
        DiagnosticRequest::class => ['label' => 'Diagnóstico', 'resource' => DiagnosticRequestResource::class],
        ContactMessage::class => ['label' => 'Contacto', 'resource' => ContactMessageResource::class],
    ];

    /**
     * @return array<string, int>
     */
    public function commercialKpis(): array
    {
        return [
            'leads' => Lead::query()->count(),
            'new_leads' => Lead::query()->where('status', RequestStatus::New)->count(),
            'quotes' => QuoteRequest::query()->count(),
            'demos' => DemoRequest::query()->count(),
            'diagnostics' => DiagnosticRequest::query()->count(),
            'converted' => Lead::query()->where('status', RequestStatus::Converted)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function contentKpis(): array
    {
        return [
            'published_products' => Product::query()->published()->count(),
            'published_articles' => Article::query()->published()->count(),
            'draft_articles' => Article::query()->where('status', ArticleStatus::Draft)->count(),
        ];
    }

    /**
     * Prospectos nuevos por mes (últimos N meses, incluido el actual).
     *
     * @return array<string, int> 'YYYY-MM' => total
     */
    public function leadsPerMonth(int $months = 12): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $series[$start->addMonths($i)->format('Y-m')] = 0;
        }

        Lead::query()
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->each(function ($createdAt) use (&$series): void {
                $key = CarbonImmutable::parse($createdAt)->format('Y-m');
                if (array_key_exists($key, $series)) {
                    $series[$key]++;
                }
            });

        return $series;
    }

    /**
     * @return array<string, int> etiqueta => total
     */
    public function requestsByType(): array
    {
        $totals = [];
        foreach (self::REQUEST_TYPES as $model => $meta) {
            $totals[$meta['label']] = $model::query()->count();
        }

        return $totals;
    }

    /**
     * @return array<string, int> estado (etiqueta) => total de prospectos
     */
    public function leadStatusDistribution(): array
    {
        $counts = Lead::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $distribution = [];
        foreach (RequestStatus::cases() as $status) {
            $distribution[$status->label()] = (int) ($counts[$status->value] ?? 0);
        }

        return $distribution;
    }

    /**
     * @return array<string, int> fuente (etiqueta) => total de prospectos
     */
    public function leadSources(): array
    {
        $counts = Lead::query()->toBase()->selectRaw('source, count(*) as total')->groupBy('source')->pluck('total', 'source');

        $sources = [];
        foreach (LeadSource::cases() as $source) {
            $sources[$source->label()] = (int) ($counts[$source->value] ?? 0);
        }

        return $sources;
    }

    /**
     * Últimas solicitudes de cualquier tipo.
     *
     * @return array<string, array{type: string, name: string, company: ?string, status: RequestStatus, created_at: mixed, url: string}>
     */
    public function latestRequests(int $limit = 10): array
    {
        return $this->mergeRequests(fn ($query) => $query->latest()->limit($limit), $limit);
    }

    /**
     * Solicitudes abiertas sin movimiento reciente o sin responsable.
     *
     * @return array<string, array{type: string, name: string, company: ?string, status: RequestStatus, created_at: mixed, url: string}>
     */
    public function requestsNeedingFollowUp(int $limit = 10): array
    {
        $threshold = now()->subDays(self::FOLLOW_UP_AFTER_DAYS);

        return $this->mergeRequests(
            fn ($query) => $query
                ->whereIn('status', RequestStatus::open())
                ->where(fn ($query) => $query->whereNull('assigned_to')->orWhere('updated_at', '<', $threshold))
                ->where('created_at', '<', now()->subDay())
                ->oldest()
                ->limit($limit),
            $limit,
            newestFirst: false,
        );
    }

    /**
     * @param  callable(Builder<Model>): mixed  $scope
     * @return array<string, array{type: string, name: string, company: ?string, status: RequestStatus, created_at: mixed, url: string}>
     */
    private function mergeRequests(callable $scope, int $limit, bool $newestFirst = true): array
    {
        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = collect();

        foreach (self::REQUEST_TYPES as $model => $meta) {
            $query = $model::query()->with('lead');
            $scope($query);

            foreach ($query->get() as $record) {
                $rows->push([
                    'key' => class_basename($model).'-'.$record->getKey(),
                    'type' => $meta['label'],
                    'name' => $record->lead?->name ?? '—',
                    'company' => $record->lead?->company,
                    'status' => $record->status,
                    'created_at' => $record->created_at,
                    'url' => $meta['resource']::getUrl('view', ['record' => $record]),
                ]);
            }
        }

        $sorted = $newestFirst ? $rows->sortByDesc('created_at') : $rows->sortBy('created_at');

        return $sorted->take($limit)->keyBy('key')->all();
    }
}
