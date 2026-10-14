<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\EstiloBoton;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class UserTable extends PowerGridComponent
{
    public string $tableName = 'users-table';

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
        return User::query();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
//            ->add('id')
            ->add('name')
            ->add('email')
            ->add('rol_label', fn (User $model) => $model->rol?->label())
            ->add('created_at_formatted', fn (User $model) => $model->created_at->format('d/m/Y H:i'));
    }

    public function columns(): array
    {
        return [
            //            Column::make('ID', 'id')
            //                ->sortable(),

            Column::make('Nombre', 'name')
                ->searchable()
                ->sortable(),

            Column::make('Email', 'email')
                ->searchable()
                ->sortable(),

            Column::make('Perfil', 'rol_label', 'rol')
                ->sortable(),

            Column::make('Fecha Creación', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Acciones'),
        ];
    }

    public function actions(User $row): array
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
