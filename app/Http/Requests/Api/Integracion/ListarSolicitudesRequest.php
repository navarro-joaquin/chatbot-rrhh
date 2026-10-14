<?php

namespace App\Http\Requests\Api\Integracion;

use Illuminate\Foundation\Http\FormRequest;

class ListarSolicitudesRequest extends FormRequest
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
            'empleado_id' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', 'string', 'max:30'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
