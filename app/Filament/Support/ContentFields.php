<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

/**
 * Campos reutilizados por los formularios de contenido.
 */
final class ContentFields
{
    /** Formatos de imagen permitidos (sin SVG: puede contener scripts). */
    public const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Tamaño máximo de imagen, en KB. */
    public const IMAGE_MAX_KB = 2048;

    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Campo de nombre/título que propone el slug al crear el registro.
     */
    public static function title(string $field = 'name', string $label = 'Nombre', int $maxLength = 160): TextInput
    {
        return TextInput::make($field)
            ->label($label)
            ->required()
            ->maxLength($maxLength)
            ->live(onBlur: true)
            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                if ($operation === 'create') {
                    $set('slug', Str::slug((string) $state));
                }
            });
    }

    public static function slug(int $maxLength = 180): TextInput
    {
        return TextInput::make('slug')
            ->label('Slug (URL)')
            ->required()
            ->maxLength($maxLength)
            ->regex(self::SLUG_PATTERN)
            ->unique(ignoreRecord: true)
            ->validationMessages(['regex' => 'Usa solo minúsculas, números y guiones (p. ej. gestion-de-inventarios).'])
            ->helperText('Cambiarlo modifica la URL pública de la página.');
    }

    public static function sortOrder(): TextInput
    {
        return TextInput::make('sort_order')
            ->label('Orden de aparición')
            ->numeric()
            ->integer()
            ->minValue(0)
            ->maxValue(9999)
            ->default(0)
            ->required();
    }

    /**
     * Imagen en el disco público, con nombre aleatorio, tipo y tamaño limitados.
     * Al reemplazarla, la anterior se elimina (ContentObserver).
     */
    public static function image(string $field, string $directory, string $label = 'Imagen'): FileUpload
    {
        return FileUpload::make($field)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->acceptedFileTypes(self::IMAGE_TYPES)
            ->maxSize(self::IMAGE_MAX_KB)
            ->imagePreviewHeight('160')
            ->helperText('JPG, PNG o WebP, máximo 2 MB.');
    }

    /**
     * SEO por página: se guarda en la columna JSON `seo`.
     */
    public static function seo(): Section
    {
        return Section::make('SEO')
            ->description('Si se dejan vacíos, el sitio usa el nombre y el resumen.')
            ->collapsible()
            ->schema([
                TextInput::make('seo.title')->label('SEO title')->maxLength(70),
                Textarea::make('seo.description')->label('SEO description')->maxLength(160)->rows(2),
            ]);
    }

    /**
     * Lista de textos (p. ej. funcionalidades, problemas que resuelve).
     */
    public static function textList(string $field, string $label, int $maxLength = 300): Repeater
    {
        return Repeater::make($field)
            ->label($label)
            ->simple(TextInput::make('value')->required()->maxLength($maxLength))
            ->reorderable()
            ->defaultItems(0);
    }

    /**
     * Lista de bloques título + descripción (beneficios, módulos, enfoque).
     */
    public static function highlights(string $field, string $label): Repeater
    {
        return Repeater::make($field)
            ->label($label)
            ->schema([
                TextInput::make('title')->label('Título')->required()->maxLength(120),
                Textarea::make('description')->label('Descripción')->required()->maxLength(500)->rows(2),
            ])
            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
            ->reorderable()
            ->collapsible()
            ->defaultItems(0);
    }

    /**
     * Ícono del frontend (nombre registrado en components/shared/icon.tsx).
     */
    public static function icon(): TextInput
    {
        return TextInput::make('icon')
            ->label('Ícono')
            ->maxLength(50)
            ->regex('/^[a-z0-9-]+$/')
            ->helperText('Nombre del ícono del sitio, p. ej. boxes, code, workflow.');
    }
}
