<?php

namespace App\Http\Resources\Api\Integracion;

use App\Models\Feriado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Feriado
 */
class FeriadoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gestion' => $this->gestion?->anio,
            'nombre' => $this->nombre,
            'fecha' => $this->fecha?->toDateString(),
            'activo' => (bool) $this->estado,
        ];
    }
}
