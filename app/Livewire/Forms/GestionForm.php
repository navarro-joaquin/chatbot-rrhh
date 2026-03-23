<?php

namespace App\Livewire\Forms;

use App\Models\Gestion;
use Livewire\Attributes\Validate;
use Livewire\Form;

class GestionForm extends Form
{
    public ?Gestion $gestion = null;

    #[Validate]
    public ?int $anio = null;

    public function rules(): array
    {
        return [
            'anio' => [
                'required',
                'integer',
                'min:2000',
                'max:2099',
                'unique:gestiones,anio,'.($this->gestion?->id ?? 'NULL'),
            ],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'anio' => 'año',
        ];
    }

    public function setGestion(Gestion $gestion): void
    {
        $this->gestion = $gestion;
        $this->anio = $gestion->anio;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->gestion) {
            $this->gestion->update(['anio' => $this->anio]);
        } else {
            Gestion::create(['anio' => $this->anio]);
        }

        $this->reset();
    }
}
