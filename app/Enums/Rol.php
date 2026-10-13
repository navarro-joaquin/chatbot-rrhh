<?php

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case RecursosHumanos = 'rrhh';

    /**
     * Nombre legible del perfil.
     */
    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::RecursosHumanos => 'Recursos Humanos',
        };
    }
}
