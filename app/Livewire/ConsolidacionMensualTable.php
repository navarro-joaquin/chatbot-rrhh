<?php

namespace App\Livewire;

use App\Services\VacacionService;
use App\Support\EstiloBoton;
use App\Support\VacacionesTiempo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

/**
 * Detalle de las consolidaciones de un año (o de un mes de ese año) calculadas con la
 * fecha de inicio del contrato vigente de cada empleado.
 */
final class ConsolidacionMensualTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'consolidaciones-mensuales-table';

    public string $sortField = 'aniversario';

    public string $sortDirection = 'asc';

    public int $anio;

    public ?int $mes = null;

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        $nombreArchivo = collect(['consolidaciones', $this->anio, $this->mes ? str_pad((string) $this->mes, 2, '0', STR_PAD_LEFT) : null])
            ->filter()
            ->implode('_');

        return [
            PowerGrid::exportable($nombreArchivo)
                ->type(Exportable::TYPE_XLS),
            PowerGrid::header()
                ->showSearchInput(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Collection
    {
        return app(VacacionService::class)
            ->consolidacionesPorContrato($this->anio)
            ->when($this->mes, fn (Collection $filas) => $filas->where('mes', $this->mes)->values());
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('empleado')
            ->add('carnet_identidad')
            ->add('fecha_inicio_formatted', fn ($fila) => Carbon::parse(data_get($fila, 'fecha_inicio'))->format('d/m/Y'))
            ->add('aniversario_formatted', fn ($fila) => Carbon::parse(data_get($fila, 'aniversario'))->format('d/m/Y'))
            ->add('anios')
            ->add('dias_texto', fn ($fila) => VacacionesTiempo::aTextoDiasConSigno((float) data_get($fila, 'dias')))
            ->add('situacion', fn ($fila) => Carbon::parse(data_get($fila, 'aniversario'))->lessThanOrEqualTo(today()) ? 'Cumplido' : 'Próximo');
    }

    public function columns(): array
    {
        return [
            Column::make('Aniversario', 'aniversario_formatted', 'aniversario')
                ->sortable(),

            Column::make('Empleado', 'empleado')
                ->sortable()
                ->searchable(),

            Column::make('C.I.', 'carnet_identidad')
                ->searchable(),

            Column::make('Inicio de contrato', 'fecha_inicio_formatted', 'fecha_inicio')
                ->sortable(),

            Column::make('Años de servicio', 'anios')
                ->sortable(),

            Column::make('Días que corresponden', 'dias_texto', 'dias')
                ->sortable(),

            Column::make('Situación', 'situacion'),

            Column::action('Acciones')
                ->visibleInExport(false),
        ];
    }

    public function actions($row): array
    {
        return [
            Button::add('view')
                ->slot('Ver empleado')
                ->class(EstiloBoton::DETALLE)
                ->route('empleados.show', ['id' => data_get($row, 'empleado_id')]),
        ];
    }
}
