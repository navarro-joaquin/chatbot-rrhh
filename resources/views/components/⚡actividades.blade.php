<?php

use Livewire\Component;

new class extends Component
{
    // Este componente es principalmente un contenedor para la tabla de PowerGrid
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl">Registro de Actividad</flux:heading>
            <flux:subheading>Historial de cambios y acciones realizadas en el sistema.</flux:subheading>
        </div>
    </div>

    <livewire:actividad-table />
</div>
