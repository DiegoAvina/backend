<?php

namespace App\Filament\Support;

use Filament\Resources\Pages\ViewRecord;

/**
 * Ficha de un prospecto o solicitud con las acciones de seguimiento.
 */
abstract class ViewCommercialRecord extends ViewRecord
{
    protected function getHeaderActions(): array
    {
        return [
            CommercialActions::changeStatus(),
            CommercialActions::assign(),
            CommercialActions::addNote(),
        ];
    }
}
