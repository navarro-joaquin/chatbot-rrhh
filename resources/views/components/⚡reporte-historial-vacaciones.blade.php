<?php

use Livewire\Component;
use App\Models\Empleado;

new class extends Component
{
    public ?int $empleadoId = null;

    public function mount()
    {
        // Podríamos pre-seleccionar uno o dejarlo nulo para ver todos
    }

    public function with()
    {
        return [
            'empleados' => Empleado::orderBy('nombre_completo')->get()
        ];
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Historial de Vacaciones</flux:heading>
    </div>

    <div class="mb-6">
        <flux:field class="max-w-md">
            <flux:label>Filtrar por Empleado</flux:label>
            <flux:select wire:model.live="empleadoId" placeholder="Todos los empleados">
                <flux:select.option value="">Todos los empleados</flux:select.option>
                @foreach ($empleados as $empleado)
                    <flux:select.option value="{{ $empleado->id }}">{{ $empleado->nombre_completo }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <livewire:historial-vacacion-table :empleado-id="$empleadoId" :wire:key="'historial-'.$empleadoId" />
</div>
