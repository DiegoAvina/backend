<?php

namespace App\Filament\Support;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Publicar y despublicar contenido. Requiere el permiso `{módulo}.publish`
 * (policy `publish`). El contenido nuevo siempre nace sin publicar.
 */
final class PublicationActions
{
    public static function publish(): Action
    {
        return Action::make('publish')
            ->label('Publicar')
            ->icon(Heroicon::OutlinedEye)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('El contenido será visible en el sitio público.')
            ->authorize('publish')
            ->visible(fn (Model $record): bool => ! self::isPublished($record))
            ->action(function (Model $record): void {
                if ($record instanceof Article) {
                    $record->update([
                        'status' => ArticleStatus::Published,
                        'published_at' => $record->published_at ?? now(),
                    ]);

                    return;
                }

                $record->update(['is_published' => true]);
            })
            ->successNotificationTitle('Publicado');
    }

    public static function unpublish(): Action
    {
        return Action::make('unpublish')
            ->label('Despublicar')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('El contenido dejará de mostrarse en el sitio público.')
            ->authorize('publish')
            ->visible(fn (Model $record): bool => self::isPublished($record))
            ->action(function (Model $record): void {
                $record instanceof Article
                    ? $record->update(['status' => ArticleStatus::Draft])
                    : $record->update(['is_published' => false]);
            })
            ->successNotificationTitle('Despublicado');
    }

    public static function isPublished(Model $record): bool
    {
        return $record instanceof Article
            ? $record->status === ArticleStatus::Published
            : (bool) $record->getAttribute('is_published');
    }
}
