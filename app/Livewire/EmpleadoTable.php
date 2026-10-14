<?php

namespace App\Livewire;

use App\Models\Empleado;
use App\Support\EstiloBoton;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class EmpleadoTable extends PowerGridComponent
{
    public string $tableName = 'empleados-table';

    #[Locked]
    public bool $soloEliminados = false;

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
        return $this->mostrarEliminados()
            ? Empleado::onlyTrashed()
            : Empleado::query();
    }

    /**
     * Solo el administrador puede listar los empleados eliminados.
     */
    private function mostrarEliminados(): bool
    {
        return $this->soloEliminados && (bool) auth()->user()?->can('restaurar-empleados');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
//            ->add('id')
            ->add('nombre_completo')
            ->add('carnet_identidad')
            ->add('telefono')
            ->add('estado_texto', fn (Empleado $model) => $model->estado ? 'Activo' : 'Inactivo')
            ->add('deleted_at_formatted', fn (Empleado $model) => $model->deleted_at?->format('d/m/Y H:i'));
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

            Column::make('Estado', 'estado_texto', 'estado'),

            Column::make('Eliminado el', 'deleted_at_formatted', 'deleted_at')
                ->sortable()
                ->hidden(isHidden: ! $this->mostrarEliminados(), isForceHidden: ! $this->mostrarEliminados()),

            Column::action('Acciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::boolean('estado')
                ->label('Activo', 'Inactivo'),
        ];
    }

    public function actions(Empleado $row): array
    {
        if ($row->trashed()) {
            return [
                Button::add('restore')
                    ->slot('Restaurar')
                    ->class(EstiloBoton::RESTAURAR)
                    ->dispatch('restore', ['id' => $row->id]),
            ];
        }

        return [
            Button::add('view')
                ->slot('Ver Detalle')
                ->class(EstiloBoton::DETALLE)
                ->route('empleados.show', ['id' => $row->id]),

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
