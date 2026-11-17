<?php

namespace App\Livewire;

use App\Livewire\Concerns\BusquedaAgrupada;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use App\Support\EstiloBoton;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

/**
 * Contratos vigentes que vencen en los próximos días, del más próximo al más lejano.
 */
final class ContratoPorVencerTable extends PowerGridComponent
{
    use BusquedaAgrupada;
    use WithExport;

    public const DIAS = 60;

    public string $tableName = 'contratos-por-vencer-table';

    public string $sortField = 'fecha_fin';

    public string $sortDirection = 'asc';

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        return [
            PowerGrid::exportable('contratos_por_vencer')
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
        return $this->aplicarBusqueda(EmpleadoContrato::query()
            ->porVencer(self::DIAS)
            ->whereHas('empleado')
            ->with('empleado'));
    }

    /**
     * @return list<string>
     */
    protected function camposBusqueda(): array
    {
        return [
            'empleado.nombre_completo',
            'empleado.carnet_identidad',
            'tipo',
            'numero_contrato',
            'nro_item',
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('empleado_nombre', fn (EmpleadoContrato $model) => $model->empleado->nombre_completo)
            ->add('carnet_identidad', fn (EmpleadoContrato $model) => $model->empleado->carnet_identidad)
            ->add('tipo')
            ->add('numero_contrato', fn (EmpleadoContrato $model) => $model->numero_contrato ?? '—')
            ->add('nro_item', fn (EmpleadoContrato $model) => $model->nro_item ?? '—')
            ->add('fecha_inicio_formatted', fn (EmpleadoContrato $model) => $model->fecha_inicio?->format('d/m/Y'))
            ->add('fecha_fin_formatted', fn (EmpleadoContrato $model) => $model->fecha_fin?->format('d/m/Y'))
            ->add('dias_restantes', fn (EmpleadoContrato $model) => $this->textoDiasRestantes($model));
    }

    public function columns(): array
    {
        return [
            Column::make('Empleado', 'empleado_nombre', 'empleados.nombre_completo')
                ->sortable()
                ->sortUsing(fn ($query, $direction) => $query->orderBy(
                    Empleado::select('nombre_completo')->whereColumn('empleados.id', 'empleado_contratos.empleado_id'),
                    $direction,
                )),

            Column::make('C.I.', 'carnet_identidad'),

            Column::make('Tipo', 'tipo')
                ->sortable(),

            Column::make('Nro. Contrato', 'numero_contrato'),

            Column::make('Nro. Ítem', 'nro_item'),

            Column::make('Inicio', 'fecha_inicio_formatted', 'fecha_inicio')
                ->sortable(),

            Column::make('Vence', 'fecha_fin_formatted', 'fecha_fin')
                ->sortable(),

            Column::make('Días restantes', 'dias_restantes'),

            Column::action('Acciones')
                ->visibleInExport(false),
        ];
    }

    public function actions(EmpleadoContrato $row): array
    {
        return [
            Button::add('view')
                ->slot('Ver empleado')
                ->class(EstiloBoton::DETALLE)
                ->route('empleados.show', ['id' => $row->empleado_id]),
        ];
    }

    private function textoDiasRestantes(EmpleadoContrato $contrato): string
    {
        $dias = (int) now()->startOfDay()->diffInDays($contrato->fecha_fin, false);

        return match ($dias) {
            0 => 'Vence hoy',
            1 => '1 día',
            default => "{$dias} días",
        };
    }
}
