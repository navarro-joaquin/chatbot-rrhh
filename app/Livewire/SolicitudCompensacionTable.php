<?php

namespace App\Livewire;

use App\Models\Empleado;
use App\Models\SolicitudCompensacion;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class SolicitudCompensacionTable extends PowerGridComponent
{
    public string $tableName = 'solicitudes-compensaciones-table';

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
        return SolicitudCompensacion::query()
            ->when($this->empleadoId, fn ($query) => $query->where('empleado_id', $this->empleadoId))
            ->with(['empleado']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('empleado_nombre', fn (SolicitudCompensacion $model) => $model->empleado->nombre_completo)
            ->add('fecha_compensacion_formatted', fn (SolicitudCompensacion $model) => $model->fecha_compensacion->format('d/m/Y'))
            ->add('horas_solicitadas')
            ->add('motivo')
            ->add('estado_label', fn (SolicitudCompensacion $model) => ucfirst($model->estado));
    }

    public function columns(): array
    {
        $columns = [
            Column::make('Empleado', 'empleado_nombre', 'empleados.nombre_completo')
                ->searchable()
                ->sortable(),

            Column::make('Fecha', 'fecha_compensacion_formatted', 'fecha_compensacion')
                ->sortable(),

            Column::make('Horas', 'horas_solicitadas')
                ->sortable(),

            Column::make('Motivo', 'motivo')
                ->searchable(),

            Column::make('Estado', 'estado_label', 'estado')
                ->sortable(),
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
                ->dataSource(Empleado::all())
                ->optionValue('id')
                ->optionLabel('nombre_completo'),
            Filter::select('estado', 'estado')
                ->dataSource([
                    ['label' => 'Aprobado', 'value' => 'aprobado'],
                    ['label' => 'Cancelado', 'value' => 'cancelado'],
                ])
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }

    public function actions(SolicitudCompensacion $row): array
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
