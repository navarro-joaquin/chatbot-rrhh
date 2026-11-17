<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Búsqueda global de PowerGrid aplicada como un único grupo `AND (... OR ...)`.
 *
 * PowerGrid agrega `relationSearch()` como `OR EXISTS (...)` en la raíz de la consulta,
 * lo que anula las condiciones base del datasource (p. ej. `whereHas('empleado')` o el
 * empleado del detalle) y los filtros. Las tablas que usan este trait no marcan columnas
 * como `searchable()` ni definen `relationSearch()`: declaran sus campos en
 * `camposBusqueda()` y envuelven su datasource con `aplicarBusqueda()`.
 */
trait BusquedaAgrupada
{
    /**
     * Campos en los que busca la tabla: `columna` propia o `relacion.columna`.
     *
     * @return list<string>
     */
    abstract protected function camposBusqueda(): array;

    protected function aplicarBusqueda(Builder $query): Builder
    {
        $termino = trim($this->search);

        if ($termino === '') {
            return $query;
        }

        $patron = "%{$termino}%";

        return $query->where(function (Builder $grupo) use ($patron) {
            foreach ($this->camposBusqueda() as $campo) {
                if (! str_contains($campo, '.')) {
                    $grupo->orWhere($grupo->qualifyColumn($campo), 'like', $patron);

                    continue;
                }

                [$relacion, $columna] = explode('.', $campo, 2);

                $grupo->orWhereHas($relacion, fn (Builder $relacionada) => $relacionada->where(
                    $relacionada->qualifyColumn($columna),
                    'like',
                    $patron,
                ));
            }
        });
    }
}
