<?php

namespace App\Livewire\Forms;

use App\Models\SolicitudCompensacion;
use App\Services\CompensacionService;
use App\Support\VacacionesTiempo;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SolicitudCompensacionForm extends Form
{
    public ?SolicitudCompensacion $solicitud = null;

    #[Validate]
    public ?int $empleado_id = null;

    #[Validate]
    public ?string $fecha_compensacion = null;

    #[Validate]
    public ?float $horas_solicitadas = 0;

    #[Validate]
    public ?int $horas_solicitadas_horas = 0;

    #[Validate]
    public ?int $horas_solicitadas_minutos = 0;

    #[Validate]
    public ?string $motivo = null;

    public function sincronizarHorasSolicitadas(): void
    {
        $this->horas_solicitadas = round(
            ($this->horas_solicitadas_horas ?? 0) + ($this->horas_solicitadas_minutos ?? 0) / 60,
            4
        );
    }

    public function equivalenciaHoras(): ?string
    {
        return VacacionesTiempo::aTextoHoras(
            ($this->horas_solicitadas_horas ?? 0) + ($this->horas_solicitadas_minutos ?? 0) / 60
        );
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'fecha_compensacion' => ['required', 'date'],
            'horas_solicitadas_horas' => ['required', 'integer', 'min:0', 'max:24'],
            'horas_solicitadas_minutos' => ['required', 'integer', 'min:0', 'max:59'],
            'horas_solicitadas' => ['required', 'numeric', 'min:0.0001', 'max:24'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'fecha_compensacion' => 'fecha de compensacion',
            'horas_solicitadas' => 'horas solicitadas',
            'horas_solicitadas_horas' => 'horas',
            'horas_solicitadas_minutos' => 'minutos',
            'motivo' => 'motivo',
        ];
    }

    public function setSolicitud(SolicitudCompensacion $solicitud): void
    {
        $this->solicitud = $solicitud;
        $this->empleado_id = $solicitud->empleado_id;
        $this->fecha_compensacion = $solicitud->fecha_compensacion?->toDateString();
        $this->horas_solicitadas = (float) $solicitud->horas_solicitadas;

        $partes = VacacionesTiempo::aPartesHoras((float) $solicitud->horas_solicitadas);
        $this->horas_solicitadas_horas = $partes['horas'];
        $this->horas_solicitadas_minutos = $partes['minutos'];

        $this->motivo = $solicitud->motivo;
    }

    public function save(): void
    {
        $this->sincronizarHorasSolicitadas();
        $this->validate();

        $service = app(CompensacionService::class);
        $disponibles = $service->obtenerTotalHorasDisponibles($this->empleado_id);

        if ($this->solicitud && $this->solicitud->empleado_id === $this->empleado_id) {
            $disponibles += (float) $this->solicitud->horas_solicitadas;
        }

        if ($this->horas_solicitadas > $disponibles) {
            $this->addError('horas_solicitadas', "El empleado solo tiene {$disponibles} horas disponibles.");

            return;
        }

        $payload = [
            'empleado_id' => $this->empleado_id,
            'fecha_compensacion' => $this->fecha_compensacion,
            'horas_solicitadas' => $this->horas_solicitadas,
            'motivo' => $this->motivo,
        ];

        if ($this->solicitud) {
            $service->actualizarSolicitud($this->solicitud, $payload);
        } else {
            $service->registrarSolicitud($payload);
        }

        $this->reset();
    }
}
