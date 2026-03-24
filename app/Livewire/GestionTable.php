<?php

namespace App\Livewire;

use App\Models\Gestion;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class GestionTable extends PowerGridComponent
{
    public string $tableName = 'gestiones-table';

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
        return Gestion::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
//            ->add('id')
            ->add('anio');
    }

    public function columns(): array
    {
        return [
//            Column::make('ID', 'id')
//                ->sortable(),

            Column::make('Año', 'anio')
                ->searchable()
                ->sortable(),

            Column::action('Acciones'),
        ];
    }

    public function actions(Gestion $row): array
    {
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
