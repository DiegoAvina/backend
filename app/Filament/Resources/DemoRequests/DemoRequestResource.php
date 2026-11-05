<?php

namespace App\Filament\Resources\DemoRequests;

use App\Enums\CompanySize;
use App\Filament\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Filament\Resources\DemoRequests\Pages\ViewDemoRequest;
use App\Filament\Support\CommercialResource;
use App\Models\DemoRequest;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DemoRequestResource extends CommercialResource
{
    protected static ?string $model = DemoRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'solicitud de demo';

    protected static ?string $pluralModelLabel = 'demos';

    protected static function specificColumns(): array
    {
        return [
            TextColumn::make('product.name')->label('Producto')->placeholder('—')->toggleable(),
            TextColumn::make('company_size')->label('Tamaño')->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function specificEntries(): array
    {
        return [
            TextEntry::make('product.name')->label('Producto')->placeholder('—'),
            TextEntry::make('company')->label('Empresa'),
            TextEntry::make('company_size')->label('Tamaño de la empresa'),
            TextEntry::make('role')->label('Puesto')->placeholder('—'),
            TextEntry::make('message')->label('Qué quiere resolver')->placeholder('—')->columnSpanFull()->prose(),
        ];
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)->pushFilters([
            SelectFilter::make('product')->label('Producto')->relationship('product', 'name'),
            SelectFilter::make('company_size')->label('Tamaño')->options(CompanySize::class),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDemoRequests::route('/'),
            'view' => ViewDemoRequest::route('/{record}'),
        ];
    }
}
