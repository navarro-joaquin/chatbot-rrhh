<?php

namespace App\Livewire;

use App\Models\Compensacion;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class CompensacionTable extends PowerGridComponent
{
    public string $tableName = 'compensaciones-table';

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
        return Compensacion::query()
            ->when($this->empleadoId, fn ($query) => $query->where('empleado_id', $this->empleadoId))
            ->with(['empleado', 'gestion']);
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
//            ->add('id')
            ->add('empleado_nombre', fn (Compensacion $model) => $model->empleado->nombre_completo)
            ->add('gestion_anio', fn (Compensacion $model) => $model->gestion->anio)
            ->add('cantidad_horas')
            ->add('descripcion')
            ->add('fecha_registro_formatted', fn (Compensacion $model) => $model->fecha_registro ? date('d/m/Y', strtotime($model->fecha_registro)) : '')
            ->add('estado_label', fn (Compensacion $model) => ucfirst($model->estado));
    }

    public function columns(): array
    {
        $columns = [
//            Column::make('ID', 'id')
//                ->sortable(),
        ];

        if (! $this->isDetailView) {
            $columns[] = Column::make('Empleado', 'empleado_nombre', 'empleados.nombre_completo')
                ->searchable()
                ->sortable();
        }

        $columns[] = Column::make('Gestión', 'gestion_anio', 'gestiones.anio')
            ->searchable()
            ->sortable();

        $columns[] = Column::make('Cant. Horas', 'cantidad_horas')
            ->sortable();

        $columns[] = Column::make('Fecha Reg.', 'fecha_registro_formatted', 'fecha_registro')
            ->sortable();

        $columns[] = Column::make('Estado', 'estado_label', 'estado')
            ->sortable();

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
            Filter::select('estado', 'estado')
                ->dataSource([
                    ['label' => 'Disponible', 'value' => 'disponible'],
                    ['label' => 'Utilizado', 'value' => 'utilizado'],
                    ['label' => 'Vencido', 'value' => 'vencido'],
                ])
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }

    public function actions(Compensacion $row): array
    {
        if ($this->isDetailView) {
            return [];
        }

        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('inline-flex items-center px-3 py-1 bg-zinc-800 text-white rounded-md text-xs font-medium hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900')
                ->dispatch('edit', ['id' => $row->id]),

            Button::add('delete')
                ->slot('Eliminar')
                ->class('inline-flex items-center px-3 py-1 bg-red-600 text-white rounded-md text-xs font-medium hover:bg-red-700')
                ->dispatch('confirmDelete', ['id' => $row->id]),
        ];
    }
}
