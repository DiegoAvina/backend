<?php

namespace App\Filament\Support;

use App\Enums\RequestStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Support\RelationManagers\NotesRelationManager;
use App\Filament\Support\RelationManagers\StatusHistoryRelationManager;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Base de los recursos de solicitudes comerciales (solo consulta y
 * seguimiento: no se crean, editan ni eliminan desde el panel).
 */
abstract class CommercialResource extends Resource
{
    use SentenceCaseLabels;

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    /**
     * Columnas propias de cada tipo de solicitud.
     *
     * @return list<Column>
     */
    abstract protected static function specificColumns(): array;

    /**
     * Datos propios de la solicitud en la ficha.
     *
     * @return list<Component>
     */
    abstract protected static function specificEntries(): array;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['lead', 'assignee']))
            ->columns([
                TextColumn::make('lead.name')->label('Prospecto')->searchable()->sortable()
                    ->description(fn (Model $record) => $record->lead?->email),
                TextColumn::make('lead.email')->label('Correo')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lead.company')->label('Empresa')->searchable()->toggleable(),
                ...static::specificColumns(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('assignee.name')->label('Responsable')->placeholder('Sin asignar')->toggleable(),
                TextColumn::make('created_at')->label('Recibida')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters(CommercialActions::filters())
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Prospecto')->columns(2)->schema([
                TextEntry::make('lead.name')->label('Nombre')
                    ->url(fn (Model $record) => $record->lead ? LeadResource::getUrl('view', ['record' => $record->lead]) : null),
                TextEntry::make('lead.email')->label('Correo')->copyable(),
                TextEntry::make('lead.phone')->label('Teléfono')->placeholder('—')->copyable(),
                TextEntry::make('lead.company')->label('Empresa')->placeholder('—'),
            ]),
            Section::make('Solicitud')->columns(2)->schema(static::specificEntries()),
            Section::make('Seguimiento')->columns(3)->schema([
                TextEntry::make('status')->label('Estado')->badge(),
                TextEntry::make('assignee.name')->label('Responsable')->placeholder('Sin asignar'),
                TextEntry::make('created_at')->label('Recibida')->dateTime('d/m/Y H:i'),
                TextEntry::make('consent_at')->label('Aceptó el aviso de privacidad')->dateTime('d/m/Y H:i'),
                KeyValueEntry::make('utm')->label('Campaña (UTM)')->placeholder('—')->columnSpan(2),
            ]),
        ])->columns(1);
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
            StatusHistoryRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', RequestStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Nuevas sin atender';
    }
}
