<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Crea un usuario del panel. La contraseña se captura de forma interactiva
 * (oculta), nunca como argumento, para que no quede en el historial.
 */
#[Signature('app:create-admin {--role=super_admin : Rol del usuario}')]
#[Description('Crea un usuario del panel administrativo')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        if (! Role::query()->exists()) {
            $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        }

        $role = UserRole::tryFrom((string) $this->option('role'))
            ?? UserRole::from(select('Rol', collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()])->all()));

        $name = text('Nombre', required: true, validate: ['name' => 'string|min:2|max:120']);
        $email = mb_strtolower(text('Correo electrónico', required: true, validate: ['email' => 'email:rfc|max:160|unique:users,email']));
        $password = password('Contraseña (mínimo 12 caracteres)', required: true);

        $validator = Validator::make(['password' => $password], ['password' => [Password::min(12)->letters()->mixedCase()->numbers()]]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }

        $user = User::query()->create(['name' => $name, 'email' => $email, 'password' => $password, 'is_active' => true]);
        $user->assignRole($role->value);

        $this->info("Usuario {$email} creado con el rol {$role->label()}.");

        return self::SUCCESS;
    }
}
