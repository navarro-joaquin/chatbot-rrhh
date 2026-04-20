<?php

namespace App\Livewire\Forms;

use App\Models\EmpleadoAntiguedad;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class EmpleadoAntiguedadForm extends Form
{
    public ?EmpleadoAntiguedad $antiguedad = null;

    #[Validate]
    public ?int $empleado_id = null;

    #[Validate]
    public ?int $contrato_id = null;

    #[Validate]
    public ?string $fecha_reconocida = null;

    #[Validate]
    public ?string $origen = 'Contrato';

    #[Validate]
    public ?string $observaciones = null;

    #[Validate]
    public bool $vigente = true;

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'contrato_id' => ['nullable', 'exists:empleado_contratos,id'],
            'fecha_reconocida' => ['required', 'date'],
            'origen' => ['required', Rule::in(['Contrato', 'Regularizacion', 'Resolucion Manual'])],
            'observaciones' => ['nullable', 'string', 'max:255'],
            'vigente' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'contrato_id' => 'contrato',
            'fecha_reconocida' => 'fecha reconocida',
            'origen' => 'origen',
            'observaciones' => 'observaciones',
            'vigente' => 'vigente',
        ];
    }

    public function setEmpleado(int $empleadoId, ?int $contratoId = null): void
    {
        $this->empleado_id = $empleadoId;
        $this->contrato_id = $contratoId;
    }

    public function setAntiguedad(EmpleadoAntiguedad $antiguedad): void
    {
        $this->antiguedad = $antiguedad;
        $this->empleado_id = $antiguedad->empleado_id;
        $this->contrato_id = $antiguedad->contrato_id;
        $this->fecha_reconocida = $antiguedad->fecha_reconocida?->format('Y-m-d');
        $this->origen = $antiguedad->origen;
        $this->observaciones = $antiguedad->observaciones;
        $this->vigente = (bool) $antiguedad->vigente;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->except('antiguedad');

        if ($this->vigente) {
            EmpleadoAntiguedad::query()
                ->where('empleado_id', $this->empleado_id)
                ->when($this->antiguedad, fn ($query) => $query->whereKeyNot($this->antiguedad->id))
                ->update(['vigente' => false]);
        }

        if ($this->antiguedad) {
            $this->antiguedad->update($data);
        } else {
            EmpleadoAntiguedad::create($data);
        }

        $this->reset();
        $this->origen = 'Contrato';
        $this->vigente = true;
    }
}
