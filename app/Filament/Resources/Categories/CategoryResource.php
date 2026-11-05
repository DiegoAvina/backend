<?php

namespace App\Filament\Resources\Categories;

use App\Enums\CategoryType;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Support\ContentFields;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class CategoryResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'categoría';

    protected static ?string $pluralModelLabel = 'categorías';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Select::make('type')->label('Tipo')->options(CategoryType::class)->required()->live(),
                ContentFields::title(maxLength: 120),
                ContentFields::slug(140)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('type', $get('type'))),
                ContentFields::sortOrder(),
                Textarea::make('description')->label('Descripción')->maxLength(500)->rows(2)->columnSpanFull(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->description(fn (Category $record) => $record->slug),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
                TextColumn::make('products_count')->label('Productos')->counts('products')->sortable(),
                TextColumn::make('articles_count')->label('Artículos')->counts('articles')->sortable(),
                TextColumn::make('sort_order')->label('Orden')->numeric()->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('type')->label('Tipo')->options(CategoryType::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
