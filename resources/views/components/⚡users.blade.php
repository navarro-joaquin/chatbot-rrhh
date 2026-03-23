<?php

use Livewire\Component;
use App\Models\User;
use Livewire\Attributes\On;
use App\Livewire\Forms\UserForm;

new class extends Component
{
    public UserForm $form;
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
        $user = User::findOrFail($id);
        $this->form->setUser($user);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->save();

        $this->message = 'Usuario guardado correctamente.';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-users-table');
    }

    #[On('confirmDelete')]
    public function confirmDelete(int $id): void
    {
        // Evitar que el usuario se borre a sí mismo
        if ($id === auth()->id()) {
            $this->message = 'No puedes eliminar tu propio usuario.';
            $this->dispatch('notify', $this->message);
            return;
        }

        $this->form->user = User::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->form->user?->delete();
        $this->showDeleteModal = false;
        $this->form->reset();

        $this->message = 'Usuario eliminado.';
        $this->dispatch('pg:eventRefresh-users-table');
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Usuarios</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nuevo Usuario</flux:button>
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

    <livewire:user-table />

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->user ? 'Editar' : 'Nuevo' }} Usuario</flux:heading>
                <flux:subheading>Complete los datos del usuario.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nombre</flux:label>
                <flux:input wire:model="form.name" placeholder="Ej: Administrador" />
                <flux:error name="form.name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="form.email" placeholder="ejemplo@correo.com" />
                <flux:error name="form.email" />
            </flux:field>

            <flux:field>
                <flux:label>Contraseña {{ $form->user ? '(opcional)' : '' }}</flux:label>
                <flux:input type="password" wire:model="form.password" placeholder="Mínimo 8 caracteres" />
                <flux:error name="form.password" />
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
                <flux:heading size="lg">¿Eliminar usuario?</flux:heading>
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
