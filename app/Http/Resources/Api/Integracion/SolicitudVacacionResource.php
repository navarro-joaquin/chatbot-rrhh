<?php

namespace App\Http\Resources\Api\Integracion;

use App\Models\SolicitudVacacion;
use App\Support\VacacionesTiempo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SolicitudVacacion
 */
class SolicitudVacacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $dias = (float) $this->dias_solicitados;
        $partes = VacacionesTiempo::aPartesDias($dias);

        return [
            'id' => $this->id,
            'empleado' => [
                'id' => $this->empleado_id,
                'nombre_completo' => $this->empleado?->nombre_completo,
            ],
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin' => $this->fecha_fin?->toDateString(),
            'dias_solicitados' => $dias,
            'dias' => $partes['dias'],
            'horas' => $partes['horas'],
            'minutos' => $partes['minutos'],
            'texto' => VacacionesTiempo::aTextoDias($dias),
            'estado' => $this->estado,
        ];
    }
}
