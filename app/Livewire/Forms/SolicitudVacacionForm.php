<?php

namespace App\Livewire\Forms;

use App\Models\SolicitudVacacion;
use App\Services\VacacionService;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Carbon\Carbon;

class SolicitudVacacionForm extends Form
{
    public ?SolicitudVacacion $solicitud = null;

    #[Validate]
    public ?int $empleado_id = null;

    #[Validate]
    public ?string $fecha_inicio = null;

    #[Validate]
    public ?string $fecha_fin = null;

    #[Validate]
    public ?float $dias_solicitados = 0;

    #[Validate]
    public ?string $motivo = null;

    public function calcularDias(): void
    {
        if (! $this->fecha_inicio || ! $this->fecha_fin) {
            return;
        }

        $inicio = Carbon::parse($this->fecha_inicio);
        $fin = Carbon::parse($this->fecha_fin);

        if ($inicio->gt($fin)) {
            $this->dias_solicitados = 0;

            return;
        }

        $service = app(VacacionService::class);
        $this->dias_solicitados = $service->calcularDiasSolicitados($this->fecha_inicio, $this->fecha_fin);
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'dias_solicitados' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
            'dias_solicitados' => 'dias solicitados',
            'motivo' => 'motivo',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $service = app(VacacionService::class);
        $disponibles = $service->obtenerTotalDiasDisponibles($this->empleado_id);

        if ($this->dias_solicitados > $disponibles) {
            $this->addError('dias_solicitados', "El empleado solo tiene {$disponibles} dias disponibles.");

            return;
        }

        $service->registrarSolicitud([
            'empleado_id' => $this->empleado_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'dias_solicitados' => $this->dias_solicitados,
            'motivo' => $this->motivo,
        ]);

        $this->reset();
    }
}
