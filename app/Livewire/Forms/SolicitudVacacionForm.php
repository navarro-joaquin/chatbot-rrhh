<?php

namespace App\Livewire\Forms;

use App\Models\SolicitudVacacion;
use App\Services\VacacionService;
use App\Support\VacacionesTiempo;
use Carbon\Carbon;
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
    public ?int $dias_solicitados_dias = 0;

    #[Validate]
    public ?int $dias_solicitados_horas = 0;

    #[Validate]
    public ?int $dias_solicitados_minutos = 0;

    #[Validate]
    public ?string $motivo = null;

    public function sincronizarDiasSolicitados(): void
    {
        $this->dias_solicitados = VacacionesTiempo::aDias(
            $this->dias_solicitados_dias ?? 0,
            $this->dias_solicitados_horas ?? 0,
            $this->dias_solicitados_minutos ?? 0
        );
    }

    public function calcularDias(): void
    {
        if (! $this->fecha_inicio || ! $this->fecha_fin) {
            return;
        }

        $inicio = Carbon::parse($this->fecha_inicio);
        $fin = Carbon::parse($this->fecha_fin);

        if ($inicio->gt($fin)) {
            $this->dias_solicitados_dias = 0;
            $this->sincronizarDiasSolicitados();

            return;
        }

        $service = app(VacacionService::class);
        $this->dias_solicitados_dias = (int) $service->calcularDiasSolicitados($this->fecha_inicio, $this->fecha_fin);
        $this->sincronizarDiasSolicitados();
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'dias_solicitados_dias' => ['required', 'integer', 'min:0', 'max:100'],
            'dias_solicitados_horas' => ['required', 'integer', 'min:0', 'max:7'],
            'dias_solicitados_minutos' => ['required', 'integer', 'min:0', 'max:59'],
            'dias_solicitados' => ['required', 'numeric', 'min:0.0001', 'max:100'],
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
            'dias_solicitados_dias' => 'días',
            'dias_solicitados_horas' => 'horas',
            'dias_solicitados_minutos' => 'minutos',
            'motivo' => 'motivo',
        ];
    }

    public function equivalenciaDias(): ?string
    {
        $total = VacacionesTiempo::aDias(
            $this->dias_solicitados_dias ?? 0,
            $this->dias_solicitados_horas ?? 0,
            $this->dias_solicitados_minutos ?? 0
        );

        return VacacionesTiempo::aTextoDias($total);
    }

    public function setSolicitud(SolicitudVacacion $solicitud): void
    {
        $this->solicitud = $solicitud;
        $this->empleado_id = $solicitud->empleado_id;
        $this->fecha_inicio = $solicitud->fecha_inicio?->toDateString();
        $this->fecha_fin = $solicitud->fecha_fin?->toDateString();
        $this->dias_solicitados = (float) $solicitud->dias_solicitados;

        $partes = VacacionesTiempo::aPartesDias((float) $solicitud->dias_solicitados);
        $this->dias_solicitados_dias = $partes['dias'];
        $this->dias_solicitados_horas = $partes['horas'];
        $this->dias_solicitados_minutos = $partes['minutos'];

        $this->motivo = $solicitud->motivo;
    }

    public function save(): void
    {
        $this->sincronizarDiasSolicitados();
        $this->validate();

        $service = app(VacacionService::class);
        $disponibles = $service->obtenerTotalDiasDisponibles($this->empleado_id);

        if ($this->solicitud && $this->solicitud->empleado_id === $this->empleado_id) {
            $disponibles += (float) $this->solicitud->dias_solicitados;
        }

        if ($this->dias_solicitados > $disponibles) {
            $this->addError('dias_solicitados', "El empleado solo tiene {$disponibles} dias disponibles.");

            return;
        }

        $payload = [
            'empleado_id' => $this->empleado_id,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'dias_solicitados' => $this->dias_solicitados,
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
