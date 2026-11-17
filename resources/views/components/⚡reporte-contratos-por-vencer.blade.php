<?php

use App\Livewire\ContratoPorVencerTable;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        return [
            'dias' => ContratoPorVencerTable::DIAS,
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <flux:heading size="xl">Contratos por vencer</flux:heading>
        <flux:subheading>Contratos vigentes que vencen entre hoy y los próximos {{ $dias }} días.</flux:subheading>
    </div>

    <livewire:contrato-por-vencer-table />
</div>
