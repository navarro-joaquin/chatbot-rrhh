<?php

namespace App\Livewire;

use App\Models\Empleado;
use App\Models\Gestion;
use App\Models\Vacacion;
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
    public string $tableName = 'vacaciones-table';

    public ?int $empleadoId = null;

    public bool $isDetailView = false;

    public function boot(): void
    {
        config(['livewire-powergrid.filter' => 'outside']);
    }

    public function setUp(): array
    {
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
        return Vacacion::query()
            ->when($this->empleadoId, fn ($query) => $query->where('empleado_id', $this->empleadoId))
            ->with(['empleado', 'gestion']);
    }

    public function relationSearch(): array
    {
        return [
            'empleado' => 'nombre_completo',
            'gestion' => 'anio',
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
                ->searchable()
                ->sortable()
                ->sortUsing(fn ($query, $direction) => $query->orderBy(
                    Empleado::select('nombre_completo')->whereColumn('empleados.id', 'vacaciones.empleado_id'),
                    $direction
                ));
        }

        $columns[] = Column::make('Gestión', 'gestion_anio', 'gestiones.anio')
            ->searchable()
            ->sortable()
            ->sortUsing(fn ($query, $direction) => $query->orderBy(
                Gestion::select('anio')->whereColumn('gestiones.id', 'vacaciones.gestion_id'),
                $direction
            ));

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
                ->class('inline-flex items-center px-3 py-1 bg-zinc-800 text-white rounded-md text-xs font-medium hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900')
                ->dispatch('edit', ['id' => $row->id]),

            Button::add('delete')
                ->slot('Eliminar')
                ->class('inline-flex items-center px-3 py-1 bg-red-600 text-white rounded-md text-xs font-medium hover:bg-red-700')
                ->dispatch('confirmDelete', ['id' => $row->id]),
        ];
    }
}
