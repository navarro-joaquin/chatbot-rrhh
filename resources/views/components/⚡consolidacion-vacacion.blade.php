<?php

use Livewire\Component;

new class extends Component
{
    // Este componente es principalmente un contenedor para la tabla de PowerGrid ConsolidacionVacacion
};
?>

<div class="p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Consolidaciones</flux:heading>
            <flux:subheading>Historial de consolidación de vacaciones automáticas realizadas por el sistema.</flux:subheading>
        </div>
    </div>

    <livewire:consolidacion-vacacion-table />
</div>
