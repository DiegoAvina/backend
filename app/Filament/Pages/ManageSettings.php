<?php

namespace App\Filament\Pages;

use App\Enums\SettingKey;
use App\Filament\Support\ContentFields;
use App\Services\Settings\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
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
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->can('settings.manage') ?? false;
    }

    public function mount(SiteSettings $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (collect(SettingKey::cases())->groupBy(fn (SettingKey $key) => $key->group()) as $group => $keys) {
            $sections[] = Section::make(SettingKey::groupLabel($group))
                ->description($group === 'notifications' ? 'Privado: nunca se publica en el sitio ni en la API.' : null)
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

        $changed = $settings->update($this->form->getState());

        Notification::make()
            ->success()
            ->title($changed === [] ? 'Sin cambios' : 'Configuración guardada')
            ->send();
    }

    private function field(SettingKey $key): Field
    {
        $field = match ($key->type()) {
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

        return $field->label($key->label())->rules($key->rules());
    }
}
