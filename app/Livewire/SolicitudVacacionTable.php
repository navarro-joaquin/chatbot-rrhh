<?php

namespace App\Livewire;

use App\Models\Empleado;
use App\Models\SolicitudVacacion;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class SolicitudVacacionTable extends PowerGridComponent
{
    public string $tableName = 'solicitudes-vacaciones-table';

    public ?int $empleadoId = null;

    public bool $isDetailView = false;

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
        return SolicitudVacacion::query()
            ->when($this->empleadoId, fn ($query) => $query->where('empleado_id', $this->empleadoId))
            ->with(['empleado']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
//            ->add('id')
            ->add('empleado_nombre', fn (SolicitudVacacion $model) => $model->empleado->nombre_completo)
            ->add('fecha_inicio_formatted', fn (SolicitudVacacion $model) => $model->fecha_inicio->format('d/m/Y'))
            ->add('fecha_fin_formatted', fn (SolicitudVacacion $model) => $model->fecha_fin->format('d/m/Y'))
            ->add('dias_solicitados')
            ->add('motivo');
//            ->add('estado');
    }

    public function columns(): array
    {
        return [
//            Column::make('ID', 'id')
//                ->sortable(),

            Column::make('Empleado', 'empleado_nombre', 'empleados.nombre_completo')
                ->searchable()
                ->sortable(),

            Column::make('Desde', 'fecha_inicio_formatted', 'fecha_inicio')
                ->sortable(),

            Column::make('Hasta', 'fecha_fin_formatted', 'fecha_fin')
                ->sortable(),

            Column::make('Días', 'dias_solicitados')
                ->sortable(),

            Column::make('Motivo', 'motivo')
                ->searchable(),

//            Column::make('Estado', 'estado')
//                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('empleado_nombre', 'empleado_id')
                ->dataSource(Empleado::all())
                ->optionValue('id')
                ->optionLabel('nombre_completo'),
        ];
    }
}
