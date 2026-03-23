<?php

use Livewire\Component;
use App\Models\Gestion;
use Livewire\Attributes\On;
use App\Livewire\Forms\GestionForm;

new class extends Component
{
    public GestionForm $form;
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
        $gestion = Gestion::findOrFail($id);
        $this->form->setGestion($gestion);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->save();

        $this->message = 'Gestión guardada correctamente.';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-gestiones-table');
    }

    #[On('confirmDelete')]
    public function confirmDelete(int $id): void
    {
        $this->form->gestion = Gestion::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->form->gestion?->delete();
        $this->showDeleteModal = false;
        $this->form->reset();

        $this->message = 'Gestión eliminada.';
        $this->dispatch('pg:eventRefresh-gestiones-table');
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Gestiones</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nueva Gestión</flux:button>
    </div>

    {{-- Banner de Notificación --}}
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

    <livewire:gestion-table />

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->gestion ? 'Editar' : 'Nueva' }} Gestión</flux:heading>
                <flux:subheading>Ingrese el año de la gestión (2000 - 2099).</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Año</flux:label>
                <flux:input type="number" wire:model="form.anio" placeholder="Ej: 2026" min="2000" max="2099" />
                <flux:error name="form.anio" />
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
                <flux:heading size="lg">¿Eliminar gestión?</flux:heading>
                <flux:subheading>Esta acción no se puede deshacer.</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
