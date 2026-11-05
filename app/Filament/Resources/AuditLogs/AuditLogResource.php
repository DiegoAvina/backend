<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Bitácora de acciones administrativas (solo lectura).
 */
class AuditLogResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'registro de auditoría';

    protected static ?string $pluralModelLabel = 'auditoría';

    /** @var array<string, string> */
    public const EVENTS = [
        'created' => 'Creación',
        'updated' => 'Edición',
        'published' => 'Publicación',
        'unpublished' => 'Despublicación',
        'deleted' => 'Eliminación',
        'restored' => 'Restauración',
        'status_changed' => 'Cambio de estado',
        'assigned' => 'Asignación',
        'note_added' => 'Nota agregada',
        'settings_updated' => 'Configuración',
        'permissions_updated' => 'Permisos',
        'role_changed' => 'Cambio de rol',
        'user_created' => 'Alta de usuario',
        'user_updated' => 'Edición de usuario',
        'user_activated' => 'Usuario activado',
        'user_deactivated' => 'Usuario desactivado',
    ];

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(3)->schema([
                TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                TextEntry::make('user.name')->label('Usuario')->placeholder('Sistema / sitio web'),
                TextEntry::make('event')->label('Acción')->badge()->formatStateUsing(fn (string $state) => self::EVENTS[$state] ?? $state),
                TextEntry::make('auditable_type')->label('Registro')
                    ->formatStateUsing(fn (?string $state, AuditLog $record) => $state ? class_basename($state).' #'.$record->auditable_id : '—'),
                TextEntry::make('description')->label('Descripción')->placeholder('—')->columnSpan(2),
                KeyValueEntry::make('old_values')->label('Antes')->placeholder('—')->columnSpanFull(),
                KeyValueEntry::make('new_values')->label('Después')->placeholder('—')->columnSpanFull(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Usuario')->placeholder('Sistema')->searchable(),
                TextColumn::make('event')->label('Acción')->badge()->formatStateUsing(fn (string $state) => self::EVENTS[$state] ?? $state),
                TextColumn::make('auditable_type')->label('Registro')
                    ->formatStateUsing(fn (?string $state, AuditLog $record) => $state ? class_basename($state).' #'.$record->auditable_id : '—'),
                TextColumn::make('description')->label('Descripción')->limit(60)->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')->label('Acción')->options(self::EVENTS)->multiple(),
                SelectFilter::make('user')->label('Usuario')->relationship('user', 'name'),
                Filter::make('created_at')
                    ->label('Fecha')
                    ->schema([DatePicker::make('from')->label('Desde'), DatePicker::make('until')->label('Hasta')])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
