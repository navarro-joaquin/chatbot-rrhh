<?php

namespace App\Http\Resources\Api\Integracion;

use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Empleado
 */
class EmpleadoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_completo' => $this->nombre_completo,
            'correo_electronico' => $this->correo_electronico,
            'telefono' => $this->telefono,
            'nro_item' => $this->contratoVigente?->nro_item,
            'activo' => $this->estado,
        ];
    }
}
