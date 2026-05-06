<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class HistorialVacacionTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'historial-vacacion-table';

    public string $primaryKey = 'registro_id';

    public string $sortField = 'fecha';

    public string $sortDirection = 'asc';

    public ?int $empleadoId = null;

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('historial_vacaciones')
                ->type(Exportable::TYPE_XLS),
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return DB::table('v_historial_vacaciones')
            ->select('*', 'registro_id as id')
            ->when($this->empleadoId, fn ($query) => $query->where('empleado_id', $this->empleadoId));
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('registro_id')
            ->add('evento')
            ->add('fecha_formatted', fn ($model) => Carbon::parse($model->fecha)->format('d/m/Y'))
            ->add('fecha_reconocida_ingreso_formatted', fn ($model) => $model->fecha_reconocida_ingreso ? Carbon::parse($model->fecha_reconocida_ingreso)->format('d/m/Y') : '-')
            ->add('dias', fn ($model) => number_format($model->dias, 1))
            ->add('saldo', fn ($model) => number_format($model->saldo, 1))
            ->add('desde_formatted', fn ($model) => $model->desde ? Carbon::parse($model->desde)->format('d/m/Y') : '-')
            ->add('hasta_formatted', fn ($model) => $model->hasta ? Carbon::parse($model->hasta)->format('d/m/Y') : '-')
            ->add('observacion');
    }

    public function columns(): array
    {
        return [
            Column::make('Evento', 'evento')
                ->sortable()
                ->searchable(),

            Column::make('Fecha', 'fecha_formatted', 'fecha')
                ->sortable(),

            Column::make('F. Rec. Ingreso', 'fecha_reconocida_ingreso_formatted', 'fecha_reconocida_ingreso')
                ->sortable(),

            Column::make('Días', 'dias')
                ->sortable(),

            Column::make('Saldo', 'saldo')
                ->sortable()
                ->contentClasses('font-bold text-blue-600'),

            Column::make('Desde', 'desde_formatted', 'desde')
                ->sortable(),

            Column::make('Hasta', 'hasta_formatted', 'hasta')
                ->sortable(),

            Column::make('Observación', 'observacion')
                ->searchable(),
        ];
    }
}
