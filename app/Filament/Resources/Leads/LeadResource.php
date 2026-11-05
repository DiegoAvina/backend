<?php

namespace App\Filament\Resources\Leads;

use App\Enums\LeadSource;
use App\Enums\RequestStatus;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\ContactMessagesRelationManager;
use App\Filament\Resources\Leads\RelationManagers\DemoRequestsRelationManager;
use App\Filament\Resources\Leads\RelationManagers\DiagnosticRequestsRelationManager;
use App\Filament\Resources\Leads\RelationManagers\QuoteRequestsRelationManager;
use App\Filament\Support\CommercialActions;
use App\Filament\Support\RelationManagers\NotesRelationManager;
use App\Filament\Support\RelationManagers\StatusHistoryRelationManager;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * CRM: ficha del prospecto con contacto, interés, responsable, solicitudes,
 * notas internas e historial de estados.
 */
class LeadResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'prospecto';

    protected static ?string $pluralModelLabel = 'prospectos';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('assignee')->withCount([
                'quoteRequests', 'demoRequests', 'diagnosticRequests', 'contactMessages',
            ]))
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->description(fn (Lead $record) => $record->email),
                TextColumn::make('email')->label('Correo')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company')->label('Empresa')->searchable()->placeholder('—')->toggleable(),
                TextColumn::make('source')->label('Fuente')->badge()->sortable(),
                TextColumn::make('requests')->label('Solicitudes')
                    ->state(fn (Lead $record): int => $record->quote_requests_count + $record->demo_requests_count
                        + $record->diagnostic_requests_count + $record->contact_messages_count),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('assignee.name')->label('Responsable')->placeholder('Sin asignar')->toggleable(),
                TextColumn::make('created_at')->label('Alta')->dateTime('d/m/Y')->sortable(),
                TextColumn::make('last_submitted_at')->label('Última solicitud')->since()->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                ...CommercialActions::filters(),
                SelectFilter::make('source')->label('Fuente')->options(LeadSource::class),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contacto')->columns(3)->schema([
                TextEntry::make('name')->label('Nombre'),
                TextEntry::make('email')->label('Correo')->copyable(),
                TextEntry::make('phone')->label('Teléfono')->placeholder('—')->copyable(),
                TextEntry::make('company')->label('Empresa')->placeholder('—'),
                TextEntry::make('role')->label('Puesto')->placeholder('—'),
            ]),
            Section::make('Seguimiento')->columns(3)->schema([
                TextEntry::make('status')->label('Estado')->badge(),
                TextEntry::make('assignee.name')->label('Responsable')->placeholder('Sin asignar'),
                TextEntry::make('source')->label('Fuente de captación')->badge(),
                TextEntry::make('interest')->label('Interés')
                    ->state(fn (Lead $record): array => self::interests($record))
                    ->listWithLineBreaks()
                    ->placeholder('Sin servicio o producto definido'),
                TextEntry::make('created_at')->label('Alta')->dateTime('d/m/Y H:i'),
                TextEntry::make('last_submitted_at')->label('Última solicitud')->dateTime('d/m/Y H:i'),
            ]),
        ])->columns(1);
    }

    /**
     * Servicios, productos y temas por los que ha preguntado el prospecto.
     *
     * @return list<string>
     */
    public static function interests(Lead $lead): array
    {
        $lead->loadMissing(['quoteRequests.service', 'demoRequests.product', 'diagnosticRequests.solution']);

        return collect()
            ->merge($lead->quoteRequests->map(fn ($request) => $request->service ? 'Servicio: '.$request->service->name : null))
            ->merge($lead->demoRequests->map(fn ($request) => $request->product ? 'Producto: '.$request->product->name : null))
            ->merge($lead->diagnosticRequests->map(fn ($request) => $request->solution ? 'Diagnóstico: '.$request->solution->name : null))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function getRelations(): array
    {
        return [
            QuoteRequestsRelationManager::class,
            DemoRequestsRelationManager::class,
            DiagnosticRequestsRelationManager::class,
            ContactMessagesRelationManager::class,
            NotesRelationManager::class,
            StatusHistoryRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Lead::query()->where('status', RequestStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Prospectos nuevos';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'view' => ViewLead::route('/{record}'),
        ];
    }
}
