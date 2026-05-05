<?php

namespace App\Livewire;

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
    public string $tableName = 'consolidacionVacacionTable';

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
        return ConsolidacionVacacion::query()
            ->with(['empleado', 'gestion']);
    }

    public function relationSearch(): array
    {
        return [];
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
                ->searchable()
                ->sortable(),
            Column::make('Gestión', 'gestion_anio', 'gestiones.anio')
                ->searchable()
                ->sortable(),
            Column::make('Días añadidos', 'dias_anadidos')
                ->sortable()
                ->searchable(),

            Column::make('Días totales después', 'dias_totales_despues')
                ->sortable()
                ->searchable(),

            Column::make('Origen', 'origen')
                ->sortable()
                ->searchable(),

            Column::make('Acción', 'accion')
                ->sortable()
                ->searchable(),
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
