<?php

namespace App\Livewire\Forms;

use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use Closure;
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
    public ?int $contrato_id = null;

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
            'contrato_id' => [
                'required',
                'exists:empleado_contratos,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->empleado_id || ! $value) {
                        return;
                    }

                    $contrato = EmpleadoContrato::query()
                        ->whereKey($value)
                        ->where('empleado_id', $this->empleado_id)
                        ->where('es_vigente', true)
                        ->first();

                    if (! $contrato) {
                        $fail('Debe seleccionar un contrato vigente del empleado.');
                    }
                },
            ],
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
            'gestion_id' => 'gestion',
            'contrato_id' => 'contrato vigente',
            'cantidad_horas' => 'cantidad de horas',
            'descripcion' => 'descripcion',
            'fecha_registro' => 'fecha de registro',
            'estado' => 'estado',
        ];
    }

    public function setCompensacion(Compensacion $compensacion): void
    {
        $this->compensacion = $compensacion;
        $this->empleado_id = $compensacion->empleado_id;
        $this->gestion_id = $compensacion->gestion_id;
        $this->contrato_id = $compensacion->contrato_id;
        $this->cantidad_horas = (float) $compensacion->cantidad_horas;
        $this->descripcion = $compensacion->descripcion;
        $this->fecha_registro = $compensacion->fecha_registro;
        $this->estado = $compensacion->estado;
    }

    public function syncContratoVigente(): void
    {
        if (! $this->empleado_id) {
            $this->contrato_id = null;

            return;
        }

        $this->contrato_id = Empleado::query()
            ->with('contratoVigente')
            ->find($this->empleado_id)
            ?->contratoVigente
            ?->id;
    }

    public function save(): void
    {
        if (! $this->contrato_id && $this->empleado_id) {
            $this->syncContratoVigente();
        }

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
