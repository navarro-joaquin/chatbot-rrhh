<?php

namespace App\Livewire;

use App\Livewire\Concerns\BusquedaAgrupada;
use App\Models\Empleado;
use App\Models\Gestion;
use App\Models\Vacacion;
use App\Support\EstiloBoton;
use App\Support\VacacionesTiempo;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class VacacionTable extends PowerGridComponent
{
    use BusquedaAgrupada;

    public string $tableName = 'vacaciones-table';

    public ?int $empleadoId = null;

    public bool $isDetailView = false;

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
        // Orden inicial: por nombre del empleado y luego por gestión (en el detalle solo hay un empleado).
        if ($this->sortField === 'id') {
            $this->sortField = $this->isDetailView ? 'gestiones.anio' : 'empleados.nombre_completo';
        }

        return [
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return $this->aplicarBusqueda(Vacacion::query()
            ->when($this->empleadoId, fn ($query) => $query->where('empleado_id', $this->empleadoId))
            ->whereHas('empleado')
            ->with(['empleado', 'gestion']));
    }

    /**
     * @return list<string>
     */
    protected function camposBusqueda(): array
    {
        return [
            'empleado.nombre_completo',
            'gestion.anio',
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
//            ->add('id')
            ->add('empleado_nombre', fn (Vacacion $model) => $model->empleado->nombre_completo)
            ->add('gestion_anio', fn (Vacacion $model) => $model->gestion->anio)
            ->add('dias_disponibles')
            ->add('equivalencia_dias', fn (Vacacion $model) => VacacionesTiempo::aTextoDias((float) $model->dias_disponibles) ?? '—');
    }

    public function columns(): array
    {
        $columns = [
            //            Column::make('ID', 'id')
            //                ->sortable(),
        ];

        // Ocultar columna empleado si es vista de detalle
        if (! $this->isDetailView) {
            $columns[] = Column::make('Empleado', 'empleado_nombre', 'empleados.nombre_completo')
                ->sortable()
                ->sortUsing(fn ($query, $direction) => $query
                    ->orderBy($this->subconsultaNombreEmpleado(), $direction)
                    ->orderBy($this->subconsultaAnioGestion()));
        }

        $columns[] = Column::make('Gestión', 'gestion_anio', 'gestiones.anio')
            ->sortable()
            ->sortUsing(fn ($query, $direction) => $query
                ->orderBy($this->subconsultaAnioGestion(), $direction)
                ->orderBy($this->subconsultaNombreEmpleado()));

        $columns[] = Column::make('Días Disponibles', 'dias_disponibles')
            ->sortable();

        $columns[] = Column::make('Equivalencia', 'equivalencia_dias')
            ->hidden(isHidden: true, isForceHidden: false);

        // Solo mostrar acciones si NO es vista de detalle
        $columns[] = Column::action('Acciones')
            ->hidden(
                isHidden: $this->isDetailView,
                isForceHidden: $this->isDetailView
            );

        return $columns;
    }

    /**
     * Nombre del empleado de cada fila, para ordenar sin unir tablas.
     */
    private function subconsultaNombreEmpleado(): Builder
    {
        return Empleado::select('nombre_completo')->whereColumn('empleados.id', 'vacaciones.empleado_id');
    }

    /**
     * Año de la gestión de cada fila, para ordenar sin unir tablas.
     */
    private function subconsultaAnioGestion(): Builder
    {
        return Gestion::select('anio')->whereColumn('gestiones.id', 'vacaciones.gestion_id');
    }

    public function filters(): array
    {
        return [
            Filter::select('empleado_nombre', 'empleado_id')
                ->dataSource(Empleado::query()->whereHas('vacaciones')->get())
                ->optionValue('id')
                ->optionLabel('nombre_completo'),
        ];
    }

    public function actions(Vacacion $row): array
    {
        if ($this->isDetailView) {
            return [];
        }

        return [
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
