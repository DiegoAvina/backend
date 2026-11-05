<?php

namespace App\Filament\Resources\Solutions;

use App\Filament\Resources\Solutions\Pages\CreateSolution;
use App\Filament\Resources\Solutions\Pages\EditSolution;
use App\Filament\Resources\Solutions\Pages\ListSolutions;
use App\Filament\Support\ContentFields;
use App\Filament\Support\PublicationActions;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\Solution;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class SolutionResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Solution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'solución';

    protected static ?string $pluralModelLabel = 'soluciones';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->columns(2)->schema([
                ContentFields::title(),
                ContentFields::slug(),
                TextInput::make('headline')->label('Encabezado')->maxLength(255)->columnSpanFull(),
                Textarea::make('summary')->label('Resumen')->required()->maxLength(500)->rows(2)->columnSpanFull(),
                MarkdownEditor::make('body')->label('Descripción detallada')->maxLength(20000)->columnSpanFull(),
            ]),
            Section::make('Problema y enfoque')->schema([
                ContentFields::textList('signals', 'Señales de que la empresa tiene este problema'),
                ContentFields::highlights('approach', 'Cómo lo abordamos'),
                ContentFields::textList('outcomes', 'Resultados esperados'),
            ]),
            Section::make('Relacionados')->columns(2)->schema([
                Select::make('services')->label('Servicios')->relationship('services', 'name')->multiple()->preload(),
                Select::make('products')->label('Productos')->relationship('products', 'name')->multiple()->preload(),
            ]),
            Section::make('Presentación')->columns(2)->schema([
                ContentFields::image('image', 'solutions'),
                Grid::make(1)->schema([ContentFields::icon(), ContentFields::sortOrder()]),
            ]),
            ContentFields::seo(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->description(fn (Solution $record) => $record->slug),
                TextColumn::make('services_count')->label('Servicios')->counts('services')->sortable(),
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
            'index' => ListSolutions::route('/'),
            'create' => CreateSolution::route('/create'),
            'edit' => EditSolution::route('/{record}/edit'),
        ];
    }
}
