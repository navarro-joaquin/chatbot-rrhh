<?php

namespace App\Support;

/**
 * Clases Tailwind de los botones de acción de las tablas PowerGrid.
 *
 * Centraliza el color, el hover (en modo claro y oscuro) y el foco para que
 * todas las tablas se comporten igual.
 */
final class EstiloBoton
{
    private const BASE = 'inline-flex cursor-pointer items-center rounded-md px-3 py-1 text-xs font-medium text-white transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2';

    public const EDITAR = self::BASE.' bg-zinc-800 hover:bg-zinc-700 focus-visible:ring-zinc-500 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-zinc-300';

    public const DETALLE = self::BASE.' bg-blue-600 hover:bg-blue-700 focus-visible:ring-blue-500';

    public const ELIMINAR = self::BASE.' bg-red-600 hover:bg-red-700 focus-visible:ring-red-500';

    public const RESTAURAR = self::BASE.' bg-green-600 hover:bg-green-700 focus-visible:ring-green-500';
}
