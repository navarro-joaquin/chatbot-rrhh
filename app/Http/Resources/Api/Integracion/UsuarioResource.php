<?php

namespace App\Http\Resources\Api\Integracion;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'correo_electronico' => $this->email,
            'rol' => $this->rol?->value,
            'rol_texto' => $this->rol?->label(),
            'correo_verificado' => $this->email_verified_at !== null,
            'doble_factor_habilitado' => $this->two_factor_confirmed_at !== null,
            'debe_cambiar_contrasena' => (bool) $this->debe_cambiar_contrasena,
            'fecha_registro' => $this->created_at,
        ];
    }
}
