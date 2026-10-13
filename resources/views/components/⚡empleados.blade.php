<?php

use Livewire\Component;
use App\Models\Empleado;
use Livewire\Attributes\On;
use App\Livewire\Forms\EmpleadoForm;

new class extends Component
{
    public EmpleadoForm $form;
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public bool $verEliminados = false;
    public bool $showRestoreModal = false;
    public ?int $restaurarId = null;
    public string $restaurarNombre = '';
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
        $empleado = Empleado::findOrFail($id);
        $this->form->setEmpleado($empleado);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->save();

        $this->message = 'Empleado guardado correctamente.';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-empleados-table');
    }

    #[On('confirmDelete')]
    public function confirmDelete(int $id): void
    {
        $this->form->empleado = Empleado::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->form->empleado?->delete();
        $this->showDeleteModal = false;
        $this->form->reset();

        $this->message = 'Empleado eliminado.';
        $this->dispatch('pg:eventRefresh-empleados-table');
    }

    #[On('restore')]
    public function confirmRestore(int $id): void
    {
        $this->authorize('restaurar-empleados');

        $empleado = Empleado::onlyTrashed()->findOrFail($id);
        $this->restaurarId = $empleado->id;
        $this->restaurarNombre = $empleado->nombre_completo;
        $this->showRestoreModal = true;
    }

    public function restore(): void
    {
        $this->authorize('restaurar-empleados');

        Empleado::onlyTrashed()->findOrFail($this->restaurarId)->restore();
        $this->showRestoreModal = false;
        $this->reset('restaurarId', 'restaurarNombre');

        $this->message = 'Empleado restaurado.';
        $this->dispatch('pg:eventRefresh-empleados-table');
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Empleados</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nuevo Empleado</flux:button>
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

    @can('restaurar-empleados')
        <div class="mb-4 w-fit">
            <flux:field variant="inline">
                <flux:switch wire:model.live="verEliminados" />
                <flux:label>Ver empleados eliminados</flux:label>
            </flux:field>
        </div>
    @endcan

    <livewire:empleado-table :solo-eliminados="$verEliminados" :key="'empleados-table-'.(int) $verEliminados" />

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" variant="wide">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->empleado ? 'Editar' : 'Nuevo' }} Empleado</flux:heading>
                <flux:subheading>Complete la información del empleado.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:label>Nombre Completo</flux:label>
                    <flux:input wire:model="form.nombre_completo" placeholder="Ej: Juan Pérez" />
                    <flux:error name="form.nombre_completo" />
                </flux:field>

                <flux:field>
                    <flux:label>Carnet de Identidad</flux:label>
                    <flux:input wire:model="form.carnet_identidad" placeholder="Ej: 1234567 LP" />
                    <flux:error name="form.carnet_identidad" />
                </flux:field>

                <flux:field>
                    <flux:label>Teléfono</flux:label>
                    <flux:input wire:model="form.telefono" placeholder="Ej: 70012345" />
                    <flux:error name="form.telefono" />
                </flux:field>

                <flux:field>
                    <flux:label>Correo Electrónico</flux:label>
                    <flux:input type="email" wire:model="form.correo_electronico" placeholder="ejemplo@correo.com" />
                    <flux:error name="form.correo_electronico" />
                </flux:field>

                <div class="flex items-center gap-2 pt-8">
                    <flux:switch wire:model="form.estado" />
                    <flux:label>Empleado Activo</flux:label>
                </div>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    {{-- Confirm Delete Modal --}}
    <flux:modal wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Eliminar empleado?</flux:heading>
                <flux:subheading>Esta acción no se puede deshacer.</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Confirm Restore Modal --}}
    <flux:modal wire:model="showRestoreModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Restaurar empleado?</flux:heading>
                <flux:subheading>{{ $restaurarNombre }} volverá a aparecer en el sistema con todo su historial.</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showRestoreModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button wire:click="restore" variant="primary">Restaurar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
