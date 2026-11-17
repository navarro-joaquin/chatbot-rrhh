<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Saldos de vacaciones</flux:heading>
        <flux:subheading>Saldo acumulado de cada empleado en todas sus gestiones, del mayor al menor.</flux:subheading>
    </div>

    <livewire:saldo-vacacion-table />
</div>
