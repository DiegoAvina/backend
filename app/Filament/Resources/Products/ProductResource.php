<?php

namespace App\Filament\Resources\Products;

use App\Enums\CategoryType;
use App\Enums\ProductStatus;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\ContentFields;
use App\Filament\Support\PublicationActions;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'productos';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->columns(2)->schema([
                ContentFields::title(),
                ContentFields::slug(),
                Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name', fn (Builder $query) => $query->where('type', CategoryType::Product))
                    ->searchable()
                    ->preload(),
                Select::make('status')
                    ->label('Estado')
                    ->options(ProductStatus::class)
                    ->default(ProductStatus::InDevelopment)
                    ->required()
                    ->helperText('Nunca marques como disponible un producto que aún está en desarrollo.'),
                TextInput::make('tagline')->label('Frase corta')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('description')->label('Descripción')->required()->maxLength(2000)->rows(3)->columnSpanFull(),
                MarkdownEditor::make('body')->label('Descripción detallada')->maxLength(20000)->columnSpanFull(),
                Textarea::make('audience')->label('Para quién es')->maxLength(1000)->rows(2)->columnSpanFull(),
            ]),
            Section::make('Funcionalidades')->schema([
                ContentFields::textList('features', 'Funcionalidades', 120),
                ContentFields::textList('problems', 'Problemas que resuelve'),
                ContentFields::highlights('modules', 'Módulos previstos'),
            ]),
            Section::make('Presentación')->columns(2)->schema([
                ContentFields::image('image', 'products'),
                Grid::make(1)->schema([
                    ContentFields::icon(),
                    ContentFields::sortOrder(),
                    Toggle::make('is_featured')->label('Destacado en la página de inicio'),
                ]),
            ]),
            ContentFields::seo(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->square()->imageSize(40),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->description(fn (Product $record) => $record->slug),
                TextColumn::make('category.name')->label('Categoría')->sortable()->toggleable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                IconColumn::make('is_published')->label('Publicado')->boolean()->sortable(),
                IconColumn::make('is_featured')->label('Destacado')->boolean()->toggleable(),
                TextColumn::make('sort_order')->label('Orden')->numeric()->sortable(),
                TextColumn::make('updated_at')->label('Actualizado')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(ProductStatus::class),
                SelectFilter::make('category')->label('Categoría')->relationship('category', 'name'),
                TernaryFilter::make('is_published')->label('Publicado'),
                TernaryFilter::make('is_featured')->label('Destacado'),
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
