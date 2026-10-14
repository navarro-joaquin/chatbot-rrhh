<?php

namespace App\Http\Resources\Api\Integracion;

use App\Models\SolicitudCompensacion;
use App\Support\VacacionesTiempo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SolicitudCompensacion
 */
class SolicitudCompensacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $horas = (float) $this->horas_solicitadas;
        $partes = VacacionesTiempo::aPartesHoras($horas);

        return [
            'id' => $this->id,
            'empleado' => [
                'id' => $this->empleado_id,
                'nombre_completo' => $this->empleado?->nombre_completo,
            ],
            'fecha_compensacion' => $this->fecha_compensacion?->toDateString(),
            'horas_solicitadas' => $horas,
            'horas' => $partes['horas'],
            'minutos' => $partes['minutos'],
            'texto' => VacacionesTiempo::aTextoHoras($horas),
            'estado' => $this->estado,
        ];
    }
}
