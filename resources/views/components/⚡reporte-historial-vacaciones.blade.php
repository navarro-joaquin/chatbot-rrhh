<?php

use Livewire\Component;
use App\Models\Empleado;

new class extends Component
{
    public ?int $empleadoId = null;

    public function with(): array
    {
        return [
            'empleados' => Empleado::whereHas('contratoVigente', fn ($query) => $query->where('tipo', 'Indefinido'))
                ->orderBy('nombre_completo')
                ->get()
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
            <flux:label>Empleado</flux:label>
            <flux:select wire:model.live="empleadoId" placeholder="Seleccione un empleado...">
                @foreach ($empleados as $empleado)
                    <flux:select.option value="{{ $empleado->id }}">{{ $empleado->nombre_completo }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <livewire:historial-vacacion-table :empleado-id="$empleadoId" :wire:key="'historial-'.$empleadoId" />
</div>
