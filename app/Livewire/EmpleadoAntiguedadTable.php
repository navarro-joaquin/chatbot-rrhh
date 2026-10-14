<?php

namespace App\Livewire;

use App\Models\EmpleadoAntiguedad;
use App\Support\EstiloBoton;
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

            Column::make('Contrato', 'contrato_ref'),

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
                ->class(EstiloBoton::EDITAR)
                ->dispatch('editAntiguedad', ['id' => $row->id]),

            Button::add('delete')
                ->slot('Eliminar')
                ->class(EstiloBoton::ELIMINAR)
                ->dispatch('confirmDeleteAntiguedad', ['id' => $row->id]),
        ];
    }
}
