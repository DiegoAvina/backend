<?php

namespace App\Filament\Support;

use App\Jobs\RevalidateFrontendContent;
use App\Observers\ContentObserver;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Edición de contenido: acciones de publicación y eliminación controlada
 * (la policy impide eliminar lo publicado).
 *
 * Revalida el frontend tras guardar aunque solo hayan cambiado relaciones
 * (p. ej. servicios de una solución), que no disparan eventos del modelo.
 */
abstract class EditContentRecord extends EditRecord
{
    protected function getHeaderActions(): array
    {
        return [
            PublicationActions::publish(),
            PublicationActions::unpublish(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $tags = ContentObserver::tagsFor($this->getRecord());

        if ($tags !== []) {
            RevalidateFrontendContent::dispatch($tags);
        }
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
