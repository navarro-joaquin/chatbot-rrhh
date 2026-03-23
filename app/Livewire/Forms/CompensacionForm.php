<?php

namespace App\Livewire\Forms;

use App\Models\Compensacion;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CompensacionForm extends Form
{
    public ?Compensacion $compensacion = null;

    #[Validate]
    public ?int $empleado_id = null;

    #[Validate]
    public ?int $gestion_id = null;

    #[Validate]
    public ?float $cantidad_horas = 0;

    #[Validate]
    public ?string $descripcion = '';

    #[Validate]
    public ?string $fecha_registro = '';

    #[Validate]
    public ?string $estado = 'disponible';

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'gestion_id' => ['required', 'exists:gestiones,id'],
            'cantidad_horas' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'fecha_registro' => ['required', 'date'],
            'estado' => ['required', 'in:disponible,utilizado,vencido'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'gestion_id' => 'gestión',
            'cantidad_horas' => 'cantidad de horas',
            'descripcion' => 'descripción',
            'fecha_registro' => 'fecha de registro',
            'estado' => 'estado',
        ];
    }

    public function setCompensacion(Compensacion $compensacion): void
    {
        $this->compensacion = $compensacion;
        $this->empleado_id = $compensacion->empleado_id;
        $this->gestion_id = $compensacion->gestion_id;
        $this->cantidad_horas = (float) $compensacion->cantidad_horas;
        $this->descripcion = $compensacion->descripcion;
        $this->fecha_registro = $compensacion->fecha_registro;
        $this->estado = $compensacion->estado;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->except('compensacion');

        if ($this->compensacion) {
            $this->compensacion->update($data);
        } else {
            Compensacion::create($data);
        }

        $this->reset();
    }
}
