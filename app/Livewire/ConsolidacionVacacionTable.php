<?php

namespace App\Livewire;

use App\Livewire\Concerns\BusquedaAgrupada;
use App\Models\ConsolidacionVacacion;
use App\Models\Empleado;
use App\Models\Gestion;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class ConsolidacionVacacionTable extends PowerGridComponent
{
    use BusquedaAgrupada;

    public string $tableName = 'consolidacionVacacionTable';

    /**
     * Las consolidaciones más recientes primero.
     */
    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return $this->aplicarBusqueda(ConsolidacionVacacion::query()
            ->whereHas('empleado')
            ->with(['empleado', 'gestion']));
    }

    /**
     * @return list<string>
     */
    protected function camposBusqueda(): array
    {
        return [
            'empleado.nombre_completo',
            'gestion.anio',
            'dias_anadidos',
            'dias_totales_despues',
            'origen',
            'accion',
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('empleado_nombre', fn (ConsolidacionVacacion $model) => $model->empleado->nombre_completo)
            ->add('gestion_anio', fn (ConsolidacionVacacion $model) => $model->gestion->anio)
            ->add('dias_anadidos')
            ->add('dias_totales_despues')
            ->add('origen')
            ->add('accion');
    }

    public function columns(): array
    {
        return [
            Column::make('Empleado', 'empleado_nombre', 'empleados.nombre_completo')
                ->sortable()
                ->sortUsing(fn ($query, $direction) => $query->orderBy(
                    Empleado::select('nombre_completo')->whereColumn('empleados.id', 'consolidacion_vacaciones.empleado_id'),
                    $direction
                )),
            Column::make('Gestión', 'gestion_anio', 'gestiones.anio')
                ->sortable()
                ->sortUsing(fn ($query, $direction) => $query->orderBy(
                    Gestion::select('anio')->whereColumn('gestiones.id', 'consolidacion_vacaciones.gestion_id'),
                    $direction
                )),
            Column::make('Días añadidos', 'dias_anadidos')
                ->sortable(),

            Column::make('Días totales después', 'dias_totales_despues')
                ->sortable(),

            Column::make('Origen', 'origen')
                ->sortable(),

            Column::make('Acción', 'accion')
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('empleado_nombre', 'empleado_id')
                ->dataSource(Empleado::query()->whereHas('consolidacionesVacaciones')->get())
                ->optionValue('id')
                ->optionLabel('nombre_completo'),

            Filter::select('gestion_anio', 'gestion_id')
                ->dataSource(Gestion::query()->whereHas('consolidacionesVacaciones')->get())
                ->optionValue('id')
                ->optionLabel('anio'),
        ];
    }
}
