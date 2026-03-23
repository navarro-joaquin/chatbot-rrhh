<?php

namespace App\Livewire\Forms;

use App\Models\Antiguedad;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AntiguedadForm extends Form
{
    public ?Antiguedad $antiguedad = null;

    #[Validate]
    public ?int $anios_desde = null;

    #[Validate]
    public ?int $anios_hasta = null;

    #[Validate]
    public ?int $dias_asignados = null;

    public function rules(): array
    {
        return [
            'anios_desde' => [
                'required',
                'integer',
                'min:0',
                'max:99',
            ],
            'anios_hasta' => [
                'required',
                'integer',
                'min:0',
                'max:99',
            ],
            'dias_asignados' => [
                'required',
                'integer',
                'min:1',
                'max:365',
            ],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'anios_desde' => 'desde',
            'anios_hasta' => 'hasta',
            'dias_asignados' => 'días asignados',
        ];
    }

    public function setAntiguedad(Antiguedad $antiguedad): void
    {
        $this->antiguedad = $antiguedad;
        $this->anios_desde = $antiguedad->anios_desde;
        $this->anios_hasta = $antiguedad->anios_hasta;
        $this->dias_asignados = $antiguedad->dias_asignados;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->antiguedad) {
            $this->antiguedad->update([
                'anios_desde' => $this->anios_desde,
                'anios_hasta' => $this->anios_hasta,
                'dias_asignados' => $this->dias_asignados,
            ]);
        } else {
            Antiguedad::create([
                'anios_desde' => $this->anios_desde,
                'anios_hasta' => $this->anios_hasta,
                'dias_asignados' => $this->dias_asignados,
            ]);
        }

        $this->reset();
    }
}
