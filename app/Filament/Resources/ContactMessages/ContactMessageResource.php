<?php

namespace App\Filament\Resources\ContactMessages;

use App\Enums\ContactSubject;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Support\CommercialResource;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactMessageResource extends CommercialResource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'mensaje de contacto';

    protected static ?string $pluralModelLabel = 'mensajes de contacto';

    protected static function specificColumns(): array
    {
        return [
            TextColumn::make('subject')->label('Tema')->badge()->toggleable(),
        ];
    }

    protected static function specificEntries(): array
    {
        return [
            TextEntry::make('subject')->label('Tema')->badge(),
            TextEntry::make('message')->label('Mensaje')->columnSpanFull()->prose(),
        ];
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)->pushFilters([
            SelectFilter::make('subject')->label('Tema')->options(ContactSubject::class),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }
}
