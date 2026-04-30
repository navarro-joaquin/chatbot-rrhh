<?php

namespace App\Livewire;

use App\Models\Empleado;
use App\Models\SolicitudVacacion;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
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
        // ->add('estado');
    }

    public function columns(): array
    {
        $columns = [

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
        ];

        $columns[] = Column::action('Acciones')
            ->hidden(
                isHidden: $this->isDetailView,
                isForceHidden: $this->isDetailView
            );

        return $columns;
    }

    public function filters(): array
    {
        return [
            Filter::select('empleado_nombre', 'empleado_id')
                ->dataSource(Empleado::query()->whereHas('solicitudesVacaciones')->get())
                ->optionValue('id')
                ->optionLabel('nombre_completo'),
        ];
    }

    public function actions(SolicitudVacacion $row): array
    {
        if ($this->isDetailView) {
            return [];
        }

        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('inline-flex items-center px-3 py-1 bg-zinc-800 text-white rounded-md text-xs font-medium hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900')
                ->dispatch('edit', ['id' => $row->id]),
        ];
    }
}
