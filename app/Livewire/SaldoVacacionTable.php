<?php

namespace App\Livewire;

use App\Livewire\Concerns\BusquedaAgrupada;
use App\Models\Empleado;
use App\Models\Vacacion;
use App\Support\EstiloBoton;
use App\Support\VacacionesTiempo;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

/**
 * Saldo de vacaciones acumulado por empleado (suma de todas sus gestiones), del mayor al menor.
 */
final class SaldoVacacionTable extends PowerGridComponent
{
    use BusquedaAgrupada;
    use WithExport;

    public string $tableName = 'saldos-vacaciones-table';

    public string $sortField = 'saldo_total';

    public string $sortDirection = 'desc';

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('saldos_vacaciones')
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
        return $this->aplicarBusqueda(Empleado::query()
            ->whereHas('vacaciones')
            ->withSum('vacaciones', 'dias_disponibles')
            ->withCount('vacaciones'));
    }

    /**
     * @return list<string>
     */
    protected function camposBusqueda(): array
    {
        return [
            'nombre_completo',
            'carnet_identidad',
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nombre_completo')
            ->add('carnet_identidad')
            ->add('estado_texto', fn (Empleado $model) => $model->estado ? 'Activo' : 'Inactivo')
            ->add('vacaciones_count')
            ->add('saldo_total', fn (Empleado $model) => VacacionesTiempo::aTextoDiasConSigno((float) $model->vacaciones_sum_dias_disponibles));
    }

    public function columns(): array
    {
        return [
            Column::make('Empleado', 'nombre_completo')
                ->sortable(),

            Column::make('C.I.', 'carnet_identidad'),

            Column::make('Estado', 'estado_texto'),

            Column::make('Gestiones', 'vacaciones_count'),

            Column::make('Saldo acumulado', 'saldo_total')
                ->sortable()
                ->sortUsing(fn ($query, $direction) => $query->orderBy($this->subconsultaSaldo(), $direction))
                ->contentClasses('font-bold text-blue-600'),

            Column::action('Acciones')
                ->visibleInExport(false),
        ];
    }

    public function actions(Empleado $row): array
    {
        return [
            Button::add('view')
                ->slot('Ver empleado')
                ->class(EstiloBoton::DETALLE)
                ->route('empleados.show', ['id' => $row->id]),
        ];
    }

    /**
     * Saldo total del empleado de cada fila, para ordenar sin depender del alias de `withSum`.
     */
    private function subconsultaSaldo(): Builder
    {
        return Vacacion::query()
            ->selectRaw('COALESCE(SUM(dias_disponibles), 0)')
            ->whereColumn('vacaciones.empleado_id', 'empleados.id');
    }
}
