<?php

use App\Livewire\Forms\FeriadoForm;
use App\Models\Feriado;
use App\Models\Gestion;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public FeriadoForm $form;
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
        $feriado = Feriado::findOrFail($id);
        $this->form->setFeriado($feriado);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->save();

        $this->message = 'Feriado guardado correctamente.';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-feriados-table');
    }

    #[On('confirmDelete')]
    public function confirmDelete(int $id): void
    {
        $this->form->feriado = Feriado::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->form->feriado?->delete();
        $this->showDeleteModal = false;
        $this->form->reset();

        $this->message = 'Feriado eliminado.';
        $this->dispatch('pg:eventRefresh-feriados-table');
    }

    public function with(): array
    {
        return [
            'gestiones' => Gestion::orderBy('anio', 'desc')->get(),
        ];
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Feriados</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Nuevo Feriado</flux:button>
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

    <livewire:feriado-table />

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->feriado ? 'Editar' : 'Nuevo' }} Feriado</flux:heading>
                <flux:subheading>Registre los dias feriados de la gestion.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input type="text" wire:model="form.nombre" placeholder="Ej. Año Nuevo" />
                    <flux:error name="form.nombre" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha</flux:label>
                    <flux:input type="date" wire:model="form.fecha" />
                    <flux:error name="form.fecha" />
                </flux:field>

                <flux:field>
                    <flux:label>Gestion</flux:label>
                    <flux:select wire:model="form.gestion_id">
                        <flux:select.option value="">Seleccione una gestion...</flux:select.option>
                        @foreach($gestiones as $gestion)
                            <flux:select.option value="{{ $gestion->id }}">{{ $gestion->anio }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.gestion_id" />
                </flux:field>

                <div class="flex items-center gap-2 pt-8">
                    <flux:switch wire:model="form.estado" />
                    <flux:label>Feriado activo</flux:label>
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
                <flux:heading size="lg">Eliminar feriado?</flux:heading>
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
