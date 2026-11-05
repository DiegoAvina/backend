<?php

namespace App\Filament\Resources\Roles;

use App\Enums\UserRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Support\SentenceCaseLabels;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

/**
 * Permisos por rol. Los cinco roles son fijos (no se crean ni eliminan) y
 * el superadministrador siempre conserva todos los permisos.
 */
class RoleResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'rol';

    protected static ?string $pluralModelLabel = 'roles y permisos';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('name')->label('Rol')
                    ->state(fn (Role $record): string => UserRole::tryFrom($record->name)?->label() ?? $record->name),
                CheckboxList::make('permissions')
                    ->label('Permisos')
                    ->relationship('permissions', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Permission $permission): string => UserRole::permissionLabel($permission->name))
                    ->columns(3)
                    ->bulkToggleable()
                    ->searchable(),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Rol')
                    ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->label() ?? $state),
                TextColumn::make('permissions_count')->label('Permisos')->counts('permissions'),
                TextColumn::make('users_count')->label('Usuarios')->counts('users'),
            ])
            ->recordActions([EditAction::make()])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
