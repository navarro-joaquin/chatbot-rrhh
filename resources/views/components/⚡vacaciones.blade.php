<?php

use Livewire\Component;
use App\Models\Vacacion;
use App\Models\Empleado;
use App\Models\Gestion;
use Livewire\Attributes\On;
use App\Livewire\Forms\VacacionForm;

new class extends Component
{
    public VacacionForm $form;
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
        $vacacion = Vacacion::findOrFail($id);
        $this->form->setVacacion($vacacion);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->save();

        $this->message = 'Vacación guardada correctamente.';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-vacaciones-table');
    }

    #[On('confirmDelete')]
    public function confirmDelete(int $id): void
    {
        $this->form->vacacion = Vacacion::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->form->vacacion?->delete();
        $this->showDeleteModal = false;
        $this->form->reset();

        $this->message = 'Vacación eliminada.';
        $this->dispatch('pg:eventRefresh-vacaciones-table');
    }

    public function with(): array
    {
        return [
            'empleados' => Empleado::where('estado', true)->orderBy('nombre_completo')->get(),
            'gestiones' => Gestion::orderBy('anio', 'desc')->get(),
        ];
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Vacaciones Disponibles</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Asignar Vacación</flux:button>
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

    <livewire:vacacion-table />

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->vacacion ? 'Editar' : 'Nueva' }} Asignación</flux:heading>
                <flux:subheading>Asigne días de vacación a un empleado para una gestión específica.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Empleado</flux:label>
                <flux:select wire:model="form.empleado_id" placeholder="Seleccione un empleado...">
                    @foreach($empleados as $empleado)
                        <flux:select.option value="{{ $empleado->id }}">{{ $empleado->nombre_completo }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.empleado_id" />
            </flux:field>

            <flux:field>
                <flux:label>Gestión</flux:label>
                <flux:select wire:model="form.gestion_id" placeholder="Seleccione gestión...">
                    @foreach($gestiones as $gestion)
                        <flux:select.option value="{{ $gestion->id }}">{{ $gestion->anio }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="form.gestion_id" />
            </flux:field>

            <flux:field>
                <flux:label>Días Disponibles</flux:label>
                <flux:input type="number" step="0.5" wire:model="form.dias_disponibles" placeholder="Ej: 15" />
                <flux:error name="form.dias_disponibles" />
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
                <flux:heading size="lg">¿Eliminar registro?</flux:heading>
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
