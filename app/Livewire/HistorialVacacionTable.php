<?php

namespace App\Livewire;

use App\Models\Empleado;
use App\Support\HistorialVacacionExcel;
use App\Support\VacacionesTiempo;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class HistorialVacacionTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'historial-vacacion-table';

    public string $primaryKey = 'registro_id';

    public string $sortField = 'fecha';

    public string $sortDirection = 'asc';

    public ?int $empleadoId = null;

    /**
     * Caché del nombre del empleado durante la petición.
     */
    protected ?string $nombreEmpleado = null;

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        $nombreArchivo = collect(['historial_vacaciones', Str::slug((string) $this->nombreEmpleado(), '_')])
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

    /**
     * El historial es siempre de un empleado: sin empleado seleccionado la tabla queda vacía.
     */
    public function datasource(): Builder
    {
        return DB::table('v_historial_vacaciones')
            ->select('*', 'registro_id as id')
            ->whereIn('empleado_id', Empleado::query()->select('id'))
            ->where('empleado_id', $this->empleadoId ?? 0);
    }

    /**
     * Exporta con el nombre del empleado como encabezado del Excel.
     */
    public function exportToXLS(bool $selected = false): BinaryFileResponse|bool
    {
        if (! $this->empleadoId) {
            return false;
        }

        /** @var Exportable|array<string, mixed> $opciones */
        $opciones = $this->setUp['exportable'];

        return (new HistorialVacacionExcel((string) $this->nombreEmpleado()))
            ->fileName((string) data_get($opciones, 'fileName'))
            ->setData($this->columns(), $this->prepareToExport($selected))
            ->download($opciones);
    }

    /**
     * Nombre del empleado seleccionado, para el encabezado del Excel y el nombre del archivo.
     */
    private function nombreEmpleado(): ?string
    {
        if (! $this->empleadoId) {
            return null;
        }

        return $this->nombreEmpleado ??= Empleado::query()->whereKey($this->empleadoId)->value('nombre_completo');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('registro_id')
            ->add('evento')
            ->add('fecha_formatted', fn ($model) => Carbon::parse($model->fecha)->format('d/m/Y'))
            ->add('fecha_reconocida_ingreso_formatted', fn ($model) => $model->fecha_reconocida_ingreso ? Carbon::parse($model->fecha_reconocida_ingreso)->format('d/m/Y') : '-')
            ->add('dias', fn ($model) => VacacionesTiempo::aTextoDiasConSigno((float) $model->dias))
            ->add('saldo', fn ($model) => VacacionesTiempo::aTextoDiasConSigno((float) $model->saldo))
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
