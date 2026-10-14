<?php

namespace App\Http\Requests\Api\Integracion;

use Illuminate\Foundation\Http\FormRequest;

class ListarEmpleadosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'estado' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
