<?php

use Livewire\Component;
use App\Models\Empleado;

new class extends Component
{
    public Empleado $empleado;

    public function mount(int $id): void
    {
        $this->empleado = Empleado::findOrFail($id);
    }
};
?>

<div class="p-6">
    <div class="flex items-center gap-4 mb-8">
        <flux:button icon="arrow-left" variant="ghost" :href="route('empleados.index')" wire:navigate />
        <div>
            <flux:heading size="xl">Detalles de Empleado</flux:heading>
            <flux:subheading>{{ $empleado->nombre_completo }} | CI: {{ $empleado->carnet_identidad }} | Item: {{ $empleado->nro_item ?? 'No tiene Item' }}</flux:subheading>
        </div>
    </div>

    <div class="space-y-12">
        {{-- Tabla de Vacaciones --}}
        <section class="space-y-4 mt-4">
            <div class="flex items-center gap-2">
                <flux:icon name="calendar-days" class="text-zinc-500" />
                <flux:heading size="lg">Vacaciones</flux:heading>
            </div>
            <livewire:vacacion-table :empleado-id="$empleado->id" :is-detail-view="true" />
        </section>

        {{-- Tabla de Compensaciones --}}
        <section class="space-y-4 mt-4">
            <div class="flex items-center gap-2">
                <flux:icon name="clock" class="text-zinc-500" />
                <flux:heading size="lg">Compensaciones</flux:heading>
            </div>
            <livewire:compensacion-table :empleado-id="$empleado->id" :is-detail-view="true" />
        </section>
    </div>
</div>
