<?php

namespace App\Filament\Resources\Industries;

use App\Filament\Resources\Industries\Pages\CreateIndustry;
use App\Filament\Resources\Industries\Pages\EditIndustry;
use App\Filament\Resources\Industries\Pages\ListIndustries;
use App\Filament\Support\ContentFields;
use App\Filament\Support\PublicationActions;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\Industry;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class IndustryResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Industry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'industria';

    protected static ?string $pluralModelLabel = 'industrias';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->columns(2)->schema([
                ContentFields::title(),
                ContentFields::slug(),
                Textarea::make('summary')->label('Resumen')->required()->maxLength(500)->rows(2)->columnSpanFull(),
                ContentFields::textList('challenges', 'Retos de la industria')->columnSpanFull(),
                ContentFields::textList('applications', 'Cómo aplicamos tecnología')->columnSpanFull(),
            ]),
            Section::make('Relacionados')->columns(2)->schema([
                Select::make('services')->label('Servicios')->relationship('services', 'name')->multiple()->preload(),
                Select::make('products')->label('Productos')->relationship('products', 'name')->multiple()->preload(),
            ]),
            Section::make('Presentación')->columns(2)->schema([
                ContentFields::image('image', 'industries'),
                Grid::make(1)->schema([ContentFields::icon(), ContentFields::sortOrder()]),
            ]),
            ContentFields::seo(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->description(fn (Industry $record) => $record->slug),
                IconColumn::make('is_published')->label('Publicado')->boolean()->sortable(),
                TextColumn::make('sort_order')->label('Orden')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Actualizado')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_published')->label('Publicado'),
            ])
            ->recordActions([
                EditAction::make(),
                PublicationActions::publish(),
                PublicationActions::unpublish(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIndustries::route('/'),
            'create' => CreateIndustry::route('/create'),
            'edit' => EditIndustry::route('/{record}/edit'),
        ];
    }
}
