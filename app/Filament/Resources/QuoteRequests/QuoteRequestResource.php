<?php

namespace App\Filament\Resources\QuoteRequests;

use App\Enums\BudgetRange;
use App\Enums\ProjectTimeline;
use App\Filament\Resources\QuoteRequests\Pages\ListQuoteRequests;
use App\Filament\Resources\QuoteRequests\Pages\ViewQuoteRequest;
use App\Filament\Support\CommercialResource;
use App\Models\QuoteRequest;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuoteRequestResource extends CommercialResource
{
    protected static ?string $model = QuoteRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'cotización';

    protected static ?string $pluralModelLabel = 'cotizaciones';

    protected static function specificColumns(): array
    {
        return [
            TextColumn::make('service.name')->label('Servicio')->placeholder('No definido')->toggleable(),
            TextColumn::make('budget_range')->label('Presupuesto')->badge()->toggleable(),
            TextColumn::make('timeline')->label('Plazo')->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function specificEntries(): array
    {
        return [
            TextEntry::make('service.name')->label('Servicio')->placeholder('No definido'),
            TextEntry::make('company')->label('Empresa'),
            TextEntry::make('budget_range')->label('Presupuesto')->badge(),
            TextEntry::make('timeline')->label('Plazo'),
            TextEntry::make('message')->label('Descripción del proyecto')->columnSpanFull()->prose(),
        ];
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)->pushFilters([
            SelectFilter::make('service')->label('Servicio')->relationship('service', 'name'),
            SelectFilter::make('budget_range')->label('Presupuesto')->options(BudgetRange::class),
            SelectFilter::make('timeline')->label('Plazo')->options(ProjectTimeline::class),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuoteRequests::route('/'),
            'view' => ViewQuoteRequest::route('/{record}'),
        ];
    }
}
