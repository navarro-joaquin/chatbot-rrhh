<?php

namespace App\Livewire;

use App\Models\EmpleadoContrato;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class EmpleadoContratoTable extends PowerGridComponent
{
    public string $tableName = 'empleado-contratos-table';

    public ?int $empleadoId = null;

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
        return EmpleadoContrato::query()
            ->when($this->empleadoId, fn ($query) => $query->where('empleado_id', $this->empleadoId))
            ->orderByDesc('es_vigente')
            ->orderByDesc('fecha_inicio');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('tipo')
            ->add('numero_contrato')
            ->add('nro_item')
            ->add('fecha_inicio_formatted', fn (EmpleadoContrato $model) => $model->fecha_inicio?->format('d/m/Y'))
            ->add('fecha_fin_formatted', fn (EmpleadoContrato $model) => $model->fecha_fin?->format('d/m/Y') ?? '-')
            ->add('estado')
            ->add('vigente_label', fn (EmpleadoContrato $model) => $model->es_vigente ? 'Si' : 'No');
    }

    public function columns(): array
    {
        return [
            Column::make('Tipo', 'tipo')
                ->searchable()
                ->sortable(),

            Column::make('Contrato', 'numero_contrato')
                ->searchable()
                ->sortable(),

            Column::make('Item', 'nro_item')
                ->searchable()
                ->sortable(),

            Column::make('Inicio', 'fecha_inicio_formatted', 'fecha_inicio')
                ->sortable(),

            Column::make('Fin', 'fecha_fin_formatted', 'fecha_fin')
                ->sortable(),

            Column::make('Estado', 'estado')
                ->sortable(),

            Column::make('Vigente', 'vigente_label', 'es_vigente')
                ->sortable(),

            Column::action('Acciones'),
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

            Filter::select('estado', 'estado')
                ->dataSource([
                    ['label' => 'Vigente', 'value' => 'Vigente'],
                    ['label' => 'Finalizado', 'value' => 'Finalizado'],
                    ['label' => 'Anulado', 'value' => 'Anulado'],
                ])
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }

    public function actions(EmpleadoContrato $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('inline-flex items-center px-3 py-1 bg-zinc-800 text-white rounded-md text-xs font-medium hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900')
                ->dispatch('editContrato', ['id' => $row->id]),

            Button::add('delete')
                ->slot('Eliminar')
                ->class('inline-flex items-center px-3 py-1 bg-red-600 text-white rounded-md text-xs font-medium hover:bg-red-700')
                ->dispatch('confirmDeleteContrato', ['id' => $row->id]),
        ];
    }
}
