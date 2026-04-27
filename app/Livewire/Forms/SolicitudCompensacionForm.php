<?php

namespace App\Livewire\Forms;

use App\Services\CompensacionService;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SolicitudCompensacionForm extends Form
{
    #[Validate]
    public ?int $empleado_id = null;

    #[Validate]
    public ?string $fecha_compensacion = null;

    #[Validate]
    public ?float $horas_solicitadas = 0;

    #[Validate]
    public ?string $motivo = null;

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'fecha_compensacion' => ['required', 'date'],
            'horas_solicitadas' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'fecha_compensacion' => 'fecha de compensacion',
            'horas_solicitadas' => 'horas solicitadas',
            'motivo' => 'motivo',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $service = app(CompensacionService::class);
        $disponibles = $service->obtenerTotalHorasDisponibles($this->empleado_id);

        if ($this->horas_solicitadas > $disponibles) {
            $this->addError('horas_solicitadas', "El empleado solo tiene {$disponibles} horas disponibles.");

            return;
        }

        $service->registrarSolicitud([
            'empleado_id' => $this->empleado_id,
            'fecha_compensacion' => $this->fecha_compensacion,
            'horas_solicitadas' => $this->horas_solicitadas,
            'motivo' => $this->motivo,
        ]);

        $this->reset();
    }
}
