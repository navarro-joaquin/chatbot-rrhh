<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use Spatie\Activitylog\Models\Activity;

final class ActividadTable extends PowerGridComponent
{
    public string $tableName = 'actividad-table';

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
        return Activity::with('causer')
            ->latest();
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('created_at_formatted', fn (Activity $model) => $model->created_at->format('d/m/Y H:i'))
            ->add('causer', fn (Activity $model) => $model->causer?->name ?? 'Sistema')
            ->add('event')
            ->add('subject_type', fn (Activity $model) => class_basename($model->subject_type ?? '') . " (#{$model->subject_id})")
            ->add('cambios', function (Activity $model) {
                // Obtener el JSON crudo directamente de la BD
                $raw        = $model->getRawOriginal('attribute_changes');
                $properties = json_decode($raw, true) ?? [];

                \Log::info('Properties decoded', $properties); // temporal para verificar

                if ($model->event === 'updated') {
                    $attributes = $properties['attributes'] ?? [];
                    $old        = $properties['old'] ?? [];
                    $cambios    = [];

                    foreach ($attributes as $campo => $nuevo) {
                        if (in_array($campo, ['updated_at', 'created_at', 'id'])) {
                            continue;
                        }

                        if (array_key_exists($campo, $old) && $old[$campo] != $nuevo) {
                            $strAnterior = is_scalar($old[$campo]) ? (string) $old[$campo] : json_encode($old[$campo]);
                            $strNuevo    = is_scalar($nuevo) ? (string) $nuevo : json_encode($nuevo);
                            $cambios[]   = "{$campo}: {$strAnterior} → {$strNuevo}";
                        }
                    }

                    return !empty($cambios) ? implode(' | ', $cambios) : 'Sin cambios relevantes';
                }

                return match($model->event) {
                    'created' => 'Registro creado',
                    'deleted' => 'Registro eliminado',
                    default   => '-',
                };
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Fecha', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::make('Usuario', 'causer')
                ->searchable(),

            Column::make('Evento', 'event')
                ->sortable(),

            Column::make('Modelo', 'subject_type')
                ->sortable(),

            Column::make('Cambios', 'cambios'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::select('event', 'event')
                ->dataSource([
                    ['label' => 'Creación',       'value' => 'created'],
                    ['label' => 'Actualización',  'value' => 'updated'],
                    ['label' => 'Eliminación',    'value' => 'deleted'],
                ])
                ->optionValue('value')
                ->optionLabel('label'),
        ];
    }
}
