<?php

namespace App\Filament\Resources\Leads\RelationManagers;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Tables\Columns\TextColumn;

class ContactMessagesRelationManager extends LeadRequestsRelationManager
{
    protected static string $relationship = 'contactMessages';

    protected static ?string $title = 'Mensajes de contacto';

    protected static string $requestResource = ContactMessageResource::class;

    protected function summaryColumn(): TextColumn
    {
        return TextColumn::make('subject')->label('Tema')->badge();
    }
}
