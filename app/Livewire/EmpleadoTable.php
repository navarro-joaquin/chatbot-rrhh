<?php

namespace App\Livewire;

use App\Models\Empleado;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class EmpleadoTable extends PowerGridComponent
{
    public string $tableName = 'empleados-table';

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
            PowerGrid::responsive()
                ->fixedColumns('estado', 'actions'),
        ];
    }

    public function datasource(): Builder
    {
        return Empleado::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
//            ->add('id')
            ->add('nombre_completo')
            ->add('carnet_identidad')
            ->add('telefono')
            ->add('tipo')
            ->add('nro_item')
            ->add('fecha_contratacion')
            ->add('estado', fn (Empleado $model) => $model->estado ? 'Activo' : 'Inactivo');
    }

    public function columns(): array
    {
        return [
//            Column::make('ID', 'id')
//                ->sortable(),

            Column::make('Nombre Completo', 'nombre_completo')
                ->searchable()
                ->sortable(),

            Column::make('C.I.', 'carnet_identidad')
                ->searchable()
                ->sortable(),

            Column::make('Teléfono', 'telefono')
                ->searchable(),

            Column::make('Tipo', 'tipo')
                ->sortable(),

            Column::make('Nro Item', 'nro_item')
                ->searchable(),

            Column::make('Fecha Contratacion', 'fecha_contratacion')
                ->searchable(),

            Column::make('Estado', 'estado'),

            Column::action('Acciones')
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('tipo', 'tipo')
                ->dataSource([
                    ['label' => 'Planta', 'value' => 'Planta'],
                    ['label' => 'Eventual', 'value' => 'Eventual'],
                ])
                ->optionValue('value')
                ->optionLabel('label'),

            Filter::boolean('estado')
                ->label('Activo', 'Inactivo'),
        ];
    }

    public function actions(Empleado $row): array
    {
        return [
            Button::add('view')
                ->slot('Ver Detalle')
                ->class('inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-md text-xs font-medium hover:bg-blue-700')
                ->route('empleados.show', ['id' => $row->id]),

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
