<?php

namespace App\Livewire\Forms;

use App\Models\Feriado;
use Livewire\Attributes\Validate;
use Livewire\Form;

class FeriadoForm extends Form
{
    public ?Feriado $feriado = null;

    #[Validate]
    public ?string $nombre = '';

    #[Validate]
    public ?string $fecha = null;

    #[Validate]
    public ?int $gestion_id = null;

    #[Validate]
    public bool $estado = true;

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:255'],
            'fecha' => ['required', 'date'],
            'gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'estado' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'nombre' => 'nombre',
            'fecha' => 'fecha',
            'gestion_id' => 'gestion',
            'estado' => 'estado',
        ];
    }

    public function setFeriado(Feriado $feriado): void
    {
        $this->feriado = $feriado;
        $this->nombre = $feriado->nombre;
        $this->fecha = $feriado->fecha?->format('Y-m-d');
        $this->gestion_id = $feriado->gestion_id;
        $this->estado = (bool) $feriado->estado;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->except('feriado');

        if ($this->feriado) {
            $this->feriado->update($data);
        } else {
            Feriado::create($data);
        }

        $this->reset();
    }
}
