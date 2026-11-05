<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Support\SentenceCaseLabels;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Usuarios del panel. Cada usuario tiene un único rol. No se eliminan: se
 * desactivan, para conservar la autoría de notas, estados y auditoría.
 */
class UserResource extends Resource
{
    use SentenceCaseLabels;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->label('Nombre')->required()->maxLength(120),
                TextInput::make('email')->label('Correo')->email()->required()->maxLength(160)->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(12)->letters()->mixedCase()->numbers())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): string => $operation === 'edit'
                        ? 'Déjala vacía para conservar la actual. Mínimo 12 caracteres con mayúsculas, minúsculas y números.'
                        : 'Mínimo 12 caracteres con mayúsculas, minúsculas y números.'),
                Select::make('role')
                    ->label('Rol')
                    ->options(fn (): array => self::assignableRoles())
                    ->required()
                    ->dehydrated(false)
                    ->afterStateHydrated(fn (Select $component, ?User $record) => $component->state($record?->roles->first()?->name)),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record !== null && $record->is(Auth::user()))
                    ->helperText('Un usuario inactivo no puede entrar al panel.'),
            ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->description(fn (User $record) => $record->email),
                TextColumn::make('email')->label('Correo')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')->label('Rol')->badge()
                    ->state(fn (User $record): ?string => $record->primaryRole()?->label()),
                IconColumn::make('is_active')->label('Activo')->boolean()->sortable(),
                TextColumn::make('last_login_at')->label('Último acceso')->since()->placeholder('Nunca')->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('role')->label('Rol')->options(UserRole::class)
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->role($data['value'] instanceof UserRole ? $data['value']->value : $data['value'])
                        : $query),
                TernaryFilter::make('is_active')->label('Activo'),
            ])
            ->recordActions([EditAction::make()]);
    }

    /**
     * Roles que el usuario actual puede asignar.
     *
     * @return array<string, string>
     */
    public static function assignableRoles(): array
    {
        $canGrantSuperAdmin = Auth::user()?->can('assignSuperAdmin', User::class) ?? false;

        return collect(UserRole::cases())
            ->reject(fn (UserRole $role): bool => $role === UserRole::SuperAdmin && ! $canGrantSuperAdmin)
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
