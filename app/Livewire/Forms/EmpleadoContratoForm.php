<?php

namespace App\Livewire\Forms;

use App\Models\EmpleadoContrato;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class EmpleadoContratoForm extends Form
{
    public ?EmpleadoContrato $contrato = null;

    #[Validate]
    public ?int $empleado_id = null;

    #[Validate]
    public ?string $tipo = 'Planta';

    #[Validate]
    public ?string $numero_contrato = null;

    #[Validate]
    public ?string $nro_item = null;

    #[Validate]
    public ?string $fecha_inicio = null;

    #[Validate]
    public ?string $fecha_fin = null;

    #[Validate]
    public ?string $estado = 'Vigente';

    #[Validate]
    public bool $es_vigente = true;

    #[Validate]
    public ?string $resolucion = null;

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'tipo' => ['required', Rule::in(['Planta', 'Eventual'])],
            'numero_contrato' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->tipo === 'Eventual'),
            ],
            'nro_item' => ['nullable', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', Rule::in(['Vigente', 'Finalizado', 'Anulado'])],
            'es_vigente' => ['boolean'],
            'resolucion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'tipo' => 'tipo',
            'numero_contrato' => 'numero de contrato',
            'nro_item' => 'nro. item',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'estado' => 'estado',
            'es_vigente' => 'vigente',
            'resolucion' => 'resolucion',
        ];
    }

    public function setEmpleadoId(int $empleadoId): void
    {
        $this->empleado_id = $empleadoId;
    }

    public function setContrato(EmpleadoContrato $contrato): void
    {
        $this->contrato = $contrato;
        $this->empleado_id = $contrato->empleado_id;
        $this->tipo = $contrato->tipo;
        $this->numero_contrato = $contrato->numero_contrato;
        $this->nro_item = $contrato->nro_item;
        $this->fecha_inicio = $contrato->fecha_inicio?->format('Y-m-d');
        $this->fecha_fin = $contrato->fecha_fin?->format('Y-m-d');
        $this->estado = $contrato->estado;
        $this->es_vigente = (bool) $contrato->es_vigente;
        $this->resolucion = $contrato->resolucion;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->except('contrato');

        if ($this->es_vigente) {
            $data['estado'] = 'Vigente';

            EmpleadoContrato::query()
                ->where('empleado_id', $this->empleado_id)
                ->when($this->contrato, fn ($query) => $query->whereKeyNot($this->contrato->id))
                ->update(['es_vigente' => false]);
        }

        if (! $this->es_vigente && $this->estado === 'Vigente') {
            $data['estado'] = 'Finalizado';
        }

        if ($this->contrato) {
            $this->contrato->update($data);
        } else {
            EmpleadoContrato::create($data);
        }

        $this->reset();
        $this->tipo = 'Planta';
        $this->estado = 'Vigente';
        $this->es_vigente = true;
    }
}
