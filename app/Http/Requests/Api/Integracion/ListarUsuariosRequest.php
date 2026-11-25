<?php

namespace App\Http\Requests\Api\Integracion;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarUsuariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rol' => ['nullable', Rule::enum(Rol::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
