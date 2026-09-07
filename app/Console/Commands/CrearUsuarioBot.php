<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CrearUsuarioBot extends Command
{
    protected $signature = 'app:crear-usuario-bot
                            {email? : Correo del usuario técnico; por defecto usa RRHH_BOT_USER_EMAIL}
                            {--name=RRHH Bot : Nombre del usuario técnico}';

    protected $description = 'Crea el usuario técnico utilizado para emitir tokens al bot de RR. HH.';

    public function handle(): int
    {
        $email = (string) ($this->argument('email') ?: config('services.rrhh_bot.user_email', ''));

        if ($email === '') {
            $this->error('Indica un correo o configura RRHH_BOT_USER_EMAIL.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con el correo indicado.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => (string) $this->option('name'),
            'email' => $email,
            'password' => Str::random(64),
        ])->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $this->info("Usuario técnico creado: {$email}");
        $this->line('La contraseña es aleatoria y no se muestra; esta cuenta no requiere inicio de sesión interactivo.');

        return self::SUCCESS;
    }
}
