<?php

namespace App\Livewire\Forms;

use App\Models\Compensacion;
use App\Support\VacacionesTiempo;
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
    public ?int $cantidad_horas_horas = 0;

    #[Validate]
    public ?int $cantidad_horas_minutos = 0;

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
            'cantidad_horas_horas' => ['required', 'integer', 'min:0', 'max:999'],
            'cantidad_horas_minutos' => ['required', 'integer', 'min:0', 'max:59'],
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
            'cantidad_horas' => 'cantidad de horas',
            'cantidad_horas_horas' => 'horas',
            'cantidad_horas_minutos' => 'minutos',
            'descripcion' => 'descripcion',
            'fecha_registro' => 'fecha de registro',
            'estado' => 'estado',
        ];
    }

    public function sincronizarCantidadHoras(): void
    {
        $this->cantidad_horas = round(
            ($this->cantidad_horas_horas ?? 0) + ($this->cantidad_horas_minutos ?? 0) / 60,
            4
        );
    }

    public function equivalenciaHoras(): ?string
    {
        return VacacionesTiempo::aTextoHoras(
            ($this->cantidad_horas_horas ?? 0) + ($this->cantidad_horas_minutos ?? 0) / 60
        );
    }

    public function setCompensacion(Compensacion $compensacion): void
    {
        $this->compensacion = $compensacion;
        $this->empleado_id = $compensacion->empleado_id;
        $this->gestion_id = $compensacion->gestion_id;
        $this->cantidad_horas = (float) $compensacion->cantidad_horas;

        $partes = VacacionesTiempo::aPartesHoras((float) $compensacion->cantidad_horas);
        $this->cantidad_horas_horas = $partes['horas'];
        $this->cantidad_horas_minutos = $partes['minutos'];

        $this->descripcion = $compensacion->descripcion;
        $this->fecha_registro = $compensacion->fecha_registro;
        $this->estado = $compensacion->estado;
    }

    public function save(): void
    {
        $this->sincronizarCantidadHoras();
        $this->validate();

        $data = $this->except('compensacion', 'cantidad_horas_horas', 'cantidad_horas_minutos');

        if ($this->compensacion) {
            $this->compensacion->update($data);
        } else {
            Compensacion::create($data);
        }

        $this->reset();
    }
}
