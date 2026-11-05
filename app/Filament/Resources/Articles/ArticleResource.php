<?php

namespace App\Filament\Resources\Articles;

use App\Enums\ArticleStatus;
use App\Enums\CategoryType;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Support\ContentFields;
use App\Filament\Support\PublicationActions;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\Article;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Artículos del centro de recursos. El estado de publicación no se edita en
 * el formulario: se cambia con las acciones Publicar / Despublicar, que
 * requieren el permiso `articles.publish`.
 */
class ArticleResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'artículo';

    protected static ?string $pluralModelLabel = 'artículos';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contenido')->columns(2)->schema([
                ContentFields::title('title', 'Título', 200),
                ContentFields::slug(220),
                Textarea::make('excerpt')->label('Resumen')->required()->maxLength(500)->rows(2)->columnSpanFull(),
                MarkdownEditor::make('body')->label('Contenido')->required()->maxLength(100000)->columnSpanFull(),
            ]),
            Section::make('Publicación')->columns(2)->schema([
                Select::make('author_id')
                    ->label('Autor')
                    ->relationship('author', 'name')
                    ->default(fn () => Auth::id())
                    ->searchable()
                    ->preload(),
                Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name', fn (Builder $query) => $query->where('type', CategoryType::Article))
                    ->preload(),
                DateTimePicker::make('published_at')
                    ->label('Fecha de publicación')
                    ->helperText('Un artículo publicado con fecha futura se muestra a partir de esa fecha.'),
                ContentFields::image('cover_image', 'articles', 'Imagen destacada'),
            ]),
            ContentFields::seo(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título')->searchable()->sortable()->limit(60),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('category.name')->label('Categoría')->toggleable(),
                TextColumn::make('author.name')->label('Autor')->toggleable(),
                TextColumn::make('published_at')->label('Publicación')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(ArticleStatus::class),
                SelectFilter::make('category')->label('Categoría')->relationship('category', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                PublicationActions::publish(),
                PublicationActions::unpublish(),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    /**
     * @return Builder<Article>
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
