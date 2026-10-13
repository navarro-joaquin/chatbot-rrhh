<?php

namespace App\Livewire\Forms;

use App\Models\Empleado;
use Closure;
use Livewire\Attributes\Validate;
use Livewire\Form;

class EmpleadoForm extends Form
{
    public ?Empleado $empleado = null;

    #[Validate]
    public ?string $nombre_completo = '';

    #[Validate]
    public ?string $carnet_identidad = '';

    #[Validate]
    public ?string $telefono = '';

    #[Validate]
    public ?string $correo_electronico = '';

    #[Validate]
    public bool $estado = true;

    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'min:3', 'max:255'],
            'carnet_identidad' => ['bail', 'required', 'string', 'max:20', $this->reglaNoEliminado('carnet_identidad'), 'unique:empleados,carnet_identidad,'.($this->empleado?->id ?? 'NULL')],
            'telefono' => ['bail', 'required', 'string', 'max:20', $this->reglaNoEliminado('telefono'), 'unique:empleados,telefono,'.($this->empleado?->id ?? 'NULL')],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
            'estado' => ['boolean'],
        ];
    }

    /**
     * Avisa cuando el dato pertenece a un empleado eliminado, que solo DTIC puede restaurar.
     */
    private function reglaNoEliminado(string $columna): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($columna): void {
            $eliminado = Empleado::onlyTrashed()->where($columna, $value)->first();

            if ($eliminado) {
                $fail("El empleado fue eliminado en fecha {$eliminado->deleted_at->format('d/m/Y')}, contáctate con DTIC para restaurarlo.");
            }
        };
    }

    public function validationAttributes(): array
    {
        return [
            'nombre_completo' => 'nombre completo',
            'carnet_identidad' => 'carnet de identidad',
            'telefono' => 'teléfono',
            'correo_electronico' => 'correo electrónico',
            'estado' => 'estado',
        ];
    }

    public function setEmpleado(Empleado $empleado): void
    {
        $this->empleado = $empleado;
        $this->nombre_completo = $empleado->nombre_completo;
        $this->carnet_identidad = $empleado->carnet_identidad;
        $this->telefono = $empleado->telefono;
        $this->correo_electronico = $empleado->correo_electronico;
        $this->estado = (bool) $empleado->estado;
    }

    public function save(): void
    {
        $this->validate();

        $data = $this->except('empleado');

        if ($this->empleado) {
            $this->empleado->update($data);
        } else {
            Empleado::create($data);
        }

        $this->reset();
    }
}
