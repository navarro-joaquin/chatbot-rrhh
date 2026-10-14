<?php

namespace App\Livewire;

use App\Models\Antiguedad;
use App\Support\EstiloBoton;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AntiguedadTable extends PowerGridComponent
{
    public string $tableName = 'antiguedades-table';

    public function setUp(): array
    {
        //        $this->showCheckBox();

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
        return Antiguedad::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
//            ->add('id')
            ->add('anios_desde')
            ->add('anios_hasta')
            ->add('dias_asignados');
    }

    public function columns(): array
    {
        return [
            //            Column::make('ID', 'id')
            //                ->sortable(),

            Column::make('Desde (años)', 'anios_desde')
                ->searchable()
                ->sortable(),

            Column::make('Hasta (años)', 'anios_hasta')
                ->searchable()
                ->sortable(),

            Column::make('Días asignados', 'dias_asignados')
                ->searchable()
                ->sortable(),

            Column::action('Acciones'),
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }

    public function actions(Antiguedad $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class(EstiloBoton::EDITAR)
                ->dispatch('edit', ['id' => $row->id]),

            Button::add('delete')
                ->slot('Eliminar')
                ->class(EstiloBoton::ELIMINAR)
                ->dispatch('confirmDelete', ['id' => $row->id]),
        ];
    }
}
