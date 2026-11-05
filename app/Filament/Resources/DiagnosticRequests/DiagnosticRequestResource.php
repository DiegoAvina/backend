<?php

namespace App\Filament\Resources\DiagnosticRequests;

use App\Enums\ContactMethod;
use App\Filament\Resources\DiagnosticRequests\Pages\ListDiagnosticRequests;
use App\Filament\Resources\DiagnosticRequests\Pages\ViewDiagnosticRequest;
use App\Filament\Support\CommercialResource;
use App\Models\DiagnosticRequest;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiagnosticRequestResource extends CommercialResource
{
    protected static ?string $model = DiagnosticRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'diagnóstico';

    protected static ?string $pluralModelLabel = 'diagnósticos';

    protected static function specificColumns(): array
    {
        return [
            TextColumn::make('solution.name')->label('Tema')->placeholder('Orientación general')->toggleable(),
            TextColumn::make('contact_method')->label('Modalidad')->toggleable(),
        ];
    }

    protected static function specificEntries(): array
    {
        return [
            TextEntry::make('solution.name')->label('Tema')->placeholder('Orientación general'),
            TextEntry::make('company')->label('Empresa'),
            TextEntry::make('contact_method')->label('Modalidad de reunión'),
            TextEntry::make('availability')->label('Disponibilidad')->placeholder('—'),
            TextEntry::make('message')->label('Situación')->columnSpanFull()->prose(),
        ];
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)->pushFilters([
            SelectFilter::make('solution')->label('Tema')->relationship('solution', 'name'),
            SelectFilter::make('contact_method')->label('Modalidad')->options(ContactMethod::class),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiagnosticRequests::route('/'),
            'view' => ViewDiagnosticRequest::route('/{record}'),
        ];
    }
}
