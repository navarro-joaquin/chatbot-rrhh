<?php

namespace App\Livewire\Forms;

use App\Models\Vacacion;
use Livewire\Attributes\Validate;
use Livewire\Form;

class VacacionForm extends Form
{
    public ?Vacacion $vacacion = null;

    #[Validate]
    public ?int $empleado_id = null;

    #[Validate]
    public ?int $gestion_id = null;

    #[Validate]
    public ?float $dias_disponibles = 0;

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'gestion_id' => ['required', 'exists:gestiones,id'],
            'dias_disponibles' => ['required', 'numeric', 'min:0', 'max:999.99'],
            // Regla para evitar duplicar gestión por empleado
            'gestion_id' => [
                'required',
                'unique:vacaciones,gestion_id,' . ($this->vacacion?->id ?? 'NULL') . ',id,empleado_id,' . $this->empleado_id
            ],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'gestion_id' => 'gestión',
            'dias_disponibles' => 'días disponibles',
        ];
    }

    public function messages(): array
    {
        return [
            'gestion_id.unique' => 'Ya existe una vacancia registrada para este empleado en esta gestión.',
        ];
    }

    public function setVacacion(Vacacion $vacacion): void
    {
        $this->vacacion = $vacacion;
        $this->empleado_id = $vacacion->empleado_id;
        $this->gestion_id = $vacacion->gestion_id;
        $this->dias_disponibles = (float) $vacacion->dias_disponibles;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'empleado_id' => $this->empleado_id,
            'gestion_id' => $this->gestion_id,
            'dias_disponibles' => $this->dias_disponibles,
        ];

        if ($this->vacacion) {
            $this->vacacion->update($data);
        } else {
            Vacacion::create($data);
        }

        $this->reset();
    }
}
