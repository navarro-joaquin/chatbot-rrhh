<?php

namespace App\Livewire\Forms;

use App\Models\Vacacion;
use App\Support\VacacionesTiempo;
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

    #[Validate]
    public ?int $dias_disponibles_dias = 0;

    #[Validate]
    public ?int $dias_disponibles_horas = 0;

    #[Validate]
    public ?int $dias_disponibles_minutos = 0;

    public function sincronizarDiasDisponibles(): void
    {
        $this->dias_disponibles = VacacionesTiempo::aDias(
            $this->dias_disponibles_dias ?? 0,
            $this->dias_disponibles_horas ?? 0,
            $this->dias_disponibles_minutos ?? 0
        );
    }

    public function equivalenciaDias(): ?string
    {
        $total = VacacionesTiempo::aDias(
            $this->dias_disponibles_dias ?? 0,
            $this->dias_disponibles_horas ?? 0,
            $this->dias_disponibles_minutos ?? 0
        );

        return VacacionesTiempo::aTextoDias($total);
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'gestion_id' => ['required', 'exists:gestiones,id'],
            'dias_disponibles_dias' => ['required', 'integer', 'min:0', 'max:999'],
            'dias_disponibles_horas' => ['required', 'integer', 'min:0', 'max:7'],
            'dias_disponibles_minutos' => ['required', 'integer', 'min:0', 'max:59'],
            'dias_disponibles' => ['required', 'numeric', 'min:0', 'max:999.99'],
            // Regla para evitar duplicar gestión por empleado
            'gestion_id' => [
                'required',
                'unique:vacaciones,gestion_id,'.($this->vacacion?->id ?? 'NULL').',id,empleado_id,'.$this->empleado_id,
            ],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'gestion_id' => 'gestión',
            'dias_disponibles' => 'días disponibles',
            'dias_disponibles_dias' => 'días',
            'dias_disponibles_horas' => 'horas',
            'dias_disponibles_minutos' => 'minutos',
        ];
    }

    public function messages(): array
    {
        return [
            'gestion_id.unique' => 'Ya existe un registro para este empleado en esta gestión.',
        ];
    }

    public function setVacacion(Vacacion $vacacion): void
    {
        $this->vacacion = $vacacion;
        $this->empleado_id = $vacacion->empleado_id;
        $this->gestion_id = $vacacion->gestion_id;
        $this->dias_disponibles = (float) $vacacion->dias_disponibles;

        $partes = VacacionesTiempo::aPartesDias((float) $vacacion->dias_disponibles);
        $this->dias_disponibles_dias = $partes['dias'];
        $this->dias_disponibles_horas = $partes['horas'];
        $this->dias_disponibles_minutos = $partes['minutos'];
    }

    public function save(): void
    {
        $this->sincronizarDiasDisponibles();
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
