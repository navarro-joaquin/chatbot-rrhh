<?php

namespace App\Livewire;

use App\Models\Feriado;
use App\Models\Gestion;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class FeriadoTable extends PowerGridComponent
{
    public string $tableName = 'feriados-table';

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
                ->fixedColumns('actions'),
        ];
    }

    public function datasource(): Builder
    {
        return Feriado::query()->with('gestion');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('nombre')
            ->add('fecha_formatted', fn (Feriado $model) => $model->fecha?->format('d/m/Y'))
            ->add('gestion_anio', fn (Feriado $model) => $model->gestion?->anio)
            ->add('estado_label', fn (Feriado $model) => $model->estado ? 'Activo' : 'Inactivo');
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'nombre')
                ->sortable()
                ->searchable(),

            Column::make('Fecha', 'fecha_formatted', 'fecha')
                ->sortable(),

            Column::make('Gestion', 'gestion_anio')
                ->sortable(),

            Column::make('Estado', 'estado_label', 'estado')
                ->sortable(),

            Column::action('Acciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('gestion_id', 'gestion_id')
                ->dataSource(
                    Gestion::query()
                        ->whereHas('feriados')
                        ->orderBy('anio', 'desc')
                        ->get()
                        ->map(fn (Gestion $gestion) => [
                            'id' => $gestion->id,
                            'label' => $gestion->anio,
                        ])
                )
                ->optionValue('id')
                ->optionLabel('label'),

            Filter::boolean('estado')
                ->label('Activo', 'Inactivo'),
        ];
    }

    public function actions(Feriado $row): array
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
