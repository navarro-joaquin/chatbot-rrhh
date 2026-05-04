<?php

namespace App\Livewire;

use App\Models\EmpleadoAntiguedad;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class EmpleadoAntiguedadTable extends PowerGridComponent
{
    public string $tableName = 'empleado-antiguedades-table';

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
        return EmpleadoAntiguedad::query()
            ->when($this->empleadoId, fn ($query) => $query->where('empleado_id', $this->empleadoId))
            ->with('contrato')
            ->orderByDesc('vigente')
            ->orderByDesc('fecha_reconocida');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('fecha_reconocida_formatted', fn (EmpleadoAntiguedad $model) => $model->fecha_reconocida?->format('d/m/Y'))
            ->add('vigencia_desde_formatted', fn (EmpleadoAntiguedad $model) => $model->vigencia_desde?->format('d/m/Y'))
            ->add('origen')
            ->add('contrato_ref', fn (EmpleadoAntiguedad $model) => $model->contrato?->numero_contrato ?: ($model->contrato?->nro_item ?? '-'))
            ->add('vigente_label', fn (EmpleadoAntiguedad $model) => $model->vigente ? 'Si' : 'No');
    }

    public function columns(): array
    {
        return [
            Column::make('Fecha reconocida', 'fecha_reconocida_formatted', 'fecha_reconocida')
                ->sortable(),

            Column::make('Vigencia desde', 'vigencia_desde_formatted', 'vigencia_desde')
                ->sortable(),

            Column::make('Contrato', 'contrato_ref')
                ->searchable(),

            Column::make('Origen', 'origen')
                ->searchable()
                ->sortable(),

            Column::make('Vigente', 'vigente_label', 'vigente')
                ->sortable(),

            Column::action('Acciones'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('origen', 'origen')
                ->dataSource([
                    ['label' => 'Contrato', 'value' => 'Contrato'],
                    ['label' => 'Regularizacion', 'value' => 'Regularizacion'],
                    ['label' => 'Resolucion Manual', 'value' => 'Resolucion Manual'],
                ])
                ->optionValue('value')
                ->optionLabel('label'),

            Filter::boolean('vigente')
                ->label('Si', 'No'),
        ];
    }

    public function actions(EmpleadoAntiguedad $row): array
    {
        return [
            Button::add('edit')
                ->slot('Editar')
                ->class('inline-flex items-center px-3 py-1 bg-zinc-800 text-white rounded-md text-xs font-medium hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900')
                ->dispatch('editAntiguedad', ['id' => $row->id]),

            Button::add('delete')
                ->slot('Eliminar')
                ->class('inline-flex items-center px-3 py-1 bg-red-600 text-white rounded-md text-xs font-medium hover:bg-red-700')
                ->dispatch('confirmDeleteAntiguedad', ['id' => $row->id]),
        ];
    }
}
