<?php

namespace App\Filament\Resources\Services;

use App\Enums\ServiceGroup;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Support\ContentFields;
use App\Filament\Support\PublicationActions;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\Service;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ServiceResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'servicio';

    protected static ?string $pluralModelLabel = 'servicios';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->columns(2)->schema([
                ContentFields::title(),
                ContentFields::slug(),
                Select::make('group')->label('Línea de servicio')->options(ServiceGroup::class)->required(),
                TextInput::make('headline')->label('Encabezado')->maxLength(255),
                Textarea::make('summary')->label('Resumen')->required()->maxLength(500)->rows(2)->columnSpanFull(),
                Textarea::make('description')->label('Descripción')->maxLength(2000)->rows(3)->columnSpanFull(),
                MarkdownEditor::make('body')->label('Descripción detallada')->maxLength(20000)->columnSpanFull(),
            ]),
            Section::make('Alcance')->schema([
                ContentFields::highlights('benefits', 'Beneficios'),
                ContentFields::textList('includes', 'Qué incluye'),
                ContentFields::textList('ideal_for', 'Ideal para'),
            ]),
            Section::make('Presentación')->columns(2)->schema([
                ContentFields::image('image', 'services'),
                Grid::make(1)->schema([ContentFields::icon(), ContentFields::sortOrder()]),
            ]),
            ContentFields::seo(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->description(fn (Service $record) => $record->slug),
                TextColumn::make('group')->label('Línea')->badge()->sortable(),
                IconColumn::make('is_published')->label('Publicado')->boolean()->sortable(),
                TextColumn::make('sort_order')->label('Orden')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Actualizado')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('group')->label('Línea')->options(ServiceGroup::class),
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
