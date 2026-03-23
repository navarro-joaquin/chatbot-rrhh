<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'unique:gestiones,anio,' . ($this->gestion?->id ?? 'NULL')],
        ];
    }

    public function messages(): array
    {
        return [
            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.unique' => 'Esta gestión ya existe.',
        ];
    }
}
