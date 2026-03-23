<?php

use Livewire\Component;
use App\Livewire\Forms\AntiguedadForm;
use App\Models\Antiguedad;
use Livewire\Attributes\On;

new class extends Component
{
    public AntiguedadForm $form;
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public string $message = '';

    public function create(): void
    {
        $this->form->reset();
        $this->message = '';
        $this->showModal = true;
    }

    #[On('edit')]
    public function edit(int $id): void
    {
        $antiguedad = Antiguedad::findOrFail($id);
        $this->form->setAntiguedad($antiguedad);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->save();

        $this->message = 'Antigüedad guardada correctamente';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-antiguedades-table');
    }

    #[On('confirmDelete')]
    public function confirmDelete(int $id): void
    {
        $this->form->antiguedad = Antiguedad::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->form->antiguedad?->delete();
        $this->showDeleteModal = false;
        $this->form->reset();

        $this->message = 'Antigüedad eliminada.';
        $this->dispatch('pg:eventRefresh-antiguedades-table');
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Antigüedad</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nueva Antigüedad</flux:button>
    </div>

    {{--  Banner de notificación  --}}
    <div x-data="{ show: false, message: '' }"
         x-on:notify.window="message = $event.detail; show = true; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition
         class="mb-4"
         style="display: none;">
        <flux:callout variant="success" x-text="message" />
    </div>

    @if ($message)
        <div x-init="$dispatch('notify', '{{ $message }}'); $wire.set('message', '')"></div>
    @endif

    <livewire:antiguedad-table />

    {{--  Modal form  --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->antiguedad ? 'Editar' : 'Nueva' }} Antigüedad</flux:heading>
                <flux:subheading>Ingrese el número de años de antigüedad y los días de vacaciones asignados.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Desde (años)</flux:label>
                <flux:input type="number" wire:model="form.anios_desde" placeholder="Ej. 1" />
                <flux:error name="form.anios_desde" />
            </flux:field>

            <flux:field>
                <flux:label>Hasta (años)</flux:label>
                <flux:input type="number" wire:model="form.anios_hasta" placeholder="Ej. 5" />
                <flux:error name="form.anios_hasta" />
            </flux:field>

            <flux:field>
                <flux:label>Días asignados</flux:label>
                <flux:input type="number" wire:model="form.dias_asignados" placeholder="Ej. 15" />
                <flux:error name="form.dias_asignados" />
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Confirm Delete Modal --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Eliminar Antigüedad?</flux:heading>
                <flux:subheading>Esta acción no se puede deshacer</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
