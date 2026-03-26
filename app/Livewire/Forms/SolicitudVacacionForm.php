<?php

namespace App\Livewire\Forms;

use App\Models\SolicitudVacacion;
use App\Services\VacacionService;
use Livewire\Attributes\Validate;
use Livewire\Form;

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

        $inicio = \Carbon\Carbon::parse($this->fecha_inicio);
        $fin = \Carbon\Carbon::parse($this->fecha_fin);

        if ($inicio->gt($fin)) {
            $this->dias_solicitados = 0;
            return;
        }

        // diffInWeekdays no incluye el día final en el conteo si son iguales, 
        // por lo que sumamos un día al final para que sea inclusivo (ej: Lunes a Viernes = 5 días)
        $this->dias_solicitados = (float) $inicio->diffInWeekdays($fin->copy()->addDay());
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
            'dias_solicitados' => 'días solicitados',
            'motivo' => 'motivo',
        ];
    }

    public function save(): void
    {
        $this->validate();

        // Validar que el empleado tenga suficientes días
        $service = app(VacacionService::class);
        $disponibles = $service->obtenerTotalDiasDisponibles($this->empleado_id);

        if ($this->dias_solicitados > $disponibles) {
            $this->addError('dias_solicitados', "El empleado solo tiene {$disponibles} días disponibles.");
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
