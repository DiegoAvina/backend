<?php

namespace App\Filament\Pages;

use App\Enums\SettingKey;
use App\Enums\SiteMode;
use App\Filament\Support\ContentFields;
use App\Services\Settings\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Configuración global del sitio. Solo con `settings.manage`.
 * Los campos vacíos no se muestran en el sitio (no se inventan datos).
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Configuración del sitio';

    protected static ?string $title = 'Configuración del sitio';

    protected static ?string $slug = 'configuracion';

    /**
     * Zona horaria en la que el equipo captura fechas; se guardan en UTC.
     */
    private const string ADMIN_TIMEZONE = 'America/Mexico_City';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->can('settings.manage') ?? false;
    }

    public function mount(SiteSettings $settings): void
    {
        $this->form->fill([
            ...$settings->all(),
            SettingKey::SiteMode->value => $settings->siteMode()->value,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (collect(SettingKey::cases())->groupBy(fn (SettingKey $key) => $key->group()) as $group => $keys) {
            $sections[] = Section::make(SettingKey::groupLabel($group))
                ->description(match ($group) {
                    'notifications' => 'Privado: nunca se publica en el sitio ni en la API.',
                    'status' => 'Fuera de "En línea", todas las páginas del sitio muestran solo el aviso elegido. El panel sigue disponible.',
                    default => null,
                })
                ->columns(2)
                ->collapsible()
                ->schema($keys->map(fn (SettingKey $key) => $this->field($key))->all());
        }

        return $schema->components($sections)->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Guardar cambios')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(SiteSettings $settings): void
    {
        abort_unless(static::canAccess(), 403);

        $state = array_map(
            fn (mixed $value): mixed => $value instanceof BackedEnum ? $value->value : $value,
            $this->form->getState(),
        );

        $changed = $settings->update($state);

        Notification::make()
            ->success()
            ->title($changed === [] ? 'Sin cambios' : 'Configuración guardada')
            ->send();
    }

    private function field(SettingKey $key): Field
    {
        $field = match ($key->type()) {
            'mode' => ToggleButtons::make($key->value)->options(SiteMode::class)->required()->live()->columns(['default' => 1, 'sm' => 2, 'xl' => 4])->columnSpanFull(),
            'datetime' => DateTimePicker::make($key->value)->seconds(false)->timezone(self::ADMIN_TIMEZONE)
                ->helperText('Hora del centro de México. En "Próximamente" muestra una cuenta regresiva; vacío para no mostrar fecha.'),
            'text' => Textarea::make($key->value)->rows(3)->maxLength(1000)->columnSpanFull(),
            'emails' => Textarea::make($key->value)->rows(2)->maxLength(1000)->columnSpanFull()
                ->helperText('Correos separados por coma. Solo se usan si las notificaciones están habilitadas en el servidor.'),
            'image' => ContentFields::image($key->value, 'branding', $key->label()),
            'email' => TextInput::make($key->value)->email()->maxLength(160),
            'url' => TextInput::make($key->value)->url()->maxLength(255)->placeholder('https://'),
            'phone' => TextInput::make($key->value)->tel()->maxLength(20),
            'whatsapp' => TextInput::make($key->value)->maxLength(15)
                ->helperText('Solo dígitos con lada internacional, p. ej. 52XXXXXXXXXX.'),
            default => TextInput::make($key->value)->maxLength(160),
        };

        if (in_array($key, [SettingKey::SiteModeMessage, SettingKey::SiteModeUntil], true)) {
            $field->visible(function (Get $get): bool {
                $mode = $get(SettingKey::SiteMode->value);

                return ($mode instanceof SiteMode ? $mode : SiteMode::tryFrom((string) $mode)) !== SiteMode::Live;
            });
        }

        if ($key === SettingKey::SiteModeMessage) {
            $field->placeholder('Opcional. Si se deja vacío se muestra un texto predeterminado para el modo elegido.');
        }

        return $field->label($key->label())->rules($key->rules());
    }
}
