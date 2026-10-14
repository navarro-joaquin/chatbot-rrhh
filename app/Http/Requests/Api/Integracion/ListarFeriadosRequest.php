<?php

namespace App\Http\Requests\Api\Integracion;

use Illuminate\Foundation\Http\FormRequest;

class ListarFeriadosRequest extends FormRequest
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
            'gestion' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'estado' => ['nullable', 'boolean'],
        ];
    }
}
