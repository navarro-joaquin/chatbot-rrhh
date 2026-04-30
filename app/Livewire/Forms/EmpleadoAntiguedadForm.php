<?php

namespace App\Livewire\Forms;

use App\Models\EmpleadoAntiguedad;
use App\Services\VacacionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
    public ?string $vigencia_desde = null;

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
            'vigencia_desde' => ['required', 'date'],
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
            'vigencia_desde' => 'vigencia desde',
            'origen' => 'origen',
            'observaciones' => 'observaciones',
            'vigente' => 'vigente',
        ];
    }

    public function setEmpleado(int $empleadoId, ?int $contratoId = null): void
    {
        $this->empleado_id = $empleadoId;
        $this->contrato_id = $contratoId;
        $this->vigencia_desde ??= now()->toDateString();
    }

    public function setAntiguedad(EmpleadoAntiguedad $antiguedad): void
    {
        $this->antiguedad = $antiguedad;
        $this->empleado_id = $antiguedad->empleado_id;
        $this->contrato_id = $antiguedad->contrato_id;
        $this->fecha_reconocida = $antiguedad->fecha_reconocida?->format('Y-m-d');
        $this->vigencia_desde = $antiguedad->vigencia_desde?->format('Y-m-d');
        $this->origen = $antiguedad->origen;
        $this->observaciones = $antiguedad->observaciones;
        $this->vigente = (bool) $antiguedad->vigente;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->except('antiguedad');

        DB::transaction(function () use ($data): void {
            if ($this->vigente) {
                EmpleadoAntiguedad::query()
                    ->where('empleado_id', $this->empleado_id)
                    ->when($this->antiguedad, fn ($query) => $query->whereKeyNot($this->antiguedad->id))
                    ->update(['vigente' => false]);
            }

            if ($this->antiguedad) {
                $this->antiguedad->update($data);
                $antiguedad = $this->antiguedad->fresh();
            } else {
                $antiguedad = EmpleadoAntiguedad::create($data);
            }

            if ($antiguedad->vigente) {
                app(VacacionService::class)->procesarVacacionesAutomaticasParaEmpleado(
                    $antiguedad->empleado_id,
                    Carbon::parse($antiguedad->vigencia_desde)
                );
            }
        });

        $this->reset();
        $this->vigencia_desde = now()->toDateString();
        $this->origen = 'Contrato';
        $this->vigente = true;
    }
}
