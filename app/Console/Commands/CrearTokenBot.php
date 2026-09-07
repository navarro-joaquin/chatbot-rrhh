<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CrearTokenBot extends Command
{
    protected $signature = 'app:crear-token-bot
                            {email : Correo del usuario propietario del token}
                            {--name=rrhh-bot : Nombre identificador del token}';

    protected $description = 'Crea un token de Sanctum de solo lectura para el bot de RR. HH.';

    public function handle(): int
    {
        $user = User::query()
            ->where('email', $this->argument('email'))
            ->first();

        if (! $user) {
            $this->error('No existe un usuario con el correo indicado.');

            return self::FAILURE;
        }

        $token = $user->createToken(
            (string) $this->option('name'),
            ['bot:read'],
        );

        $this->info('Token creado. Guárdalo ahora; no volverá a mostrarse.');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
