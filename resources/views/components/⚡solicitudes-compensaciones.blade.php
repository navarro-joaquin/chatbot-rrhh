<?php

use App\Livewire\Forms\SolicitudCompensacionForm;
use App\Models\Empleado;
use App\Models\SolicitudCompensacion;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public SolicitudCompensacionForm $form;
    public bool $showModal = false;
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
        $this->form->setSolicitud(SolicitudCompensacion::findOrFail($id));
        $this->message = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $isEditing = $this->form->solicitud !== null;

        $this->form->save();

        if ($this->getErrorBag()->has('form.horas_solicitadas')) {
            return;
        }

        $this->message = $isEditing
            ? 'Solicitud de compensacion actualizada y saldo reajustado correctamente.'
            : 'Solicitud de compensacion registrada y horas descontadas correctamente.';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-solicitudes-compensaciones-table');
        $this->dispatch('notify', $this->message);
    }

    public function with(): array
    {
        return [
            'empleados' => Empleado::where('estado', true)
                ->orderBy('nombre_completo', 'asc')
                ->pluck('nombre_completo', 'id'),
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Solicitudes de Compensacion</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Registrar Solicitud</flux:button>
    </div>

    <div x-data="{ show: false, message: '' }"
         x-on:notify.window="message = $event.detail; show = true; setTimeout(() => show = false, 3000)"
         x-show="show"
         x-transition
         class="mb-4"
         style="display: none;">
        <flux:callout variant="success" x-text="message" />
    </div>

    <livewire:solicitud-compensacion-table />

    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->solicitud ? 'Editar' : 'Nueva' }} Solicitud</flux:heading>
                <flux:subheading>Registre el uso de horas de compensacion. Las horas se descuentan por FIFO y al editar se reajusta el saldo automaticamente.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:label>Empleado</flux:label>
                    <flux:select wire:model="form.empleado_id">
                        <flux:select.option value="">Seleccione un empleado...</flux:select.option>
                        @foreach ($empleados as $id => $nombre)
                            <flux:select.option value="{{ $id }}">{{ $nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.empleado_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de compensacion</flux:label>
                    <flux:input type="date" wire:model="form.fecha_compensacion" />
                    <flux:error name="form.fecha_compensacion" />
                </flux:field>

                <flux:field>
                    <flux:label>Horas a solicitar</flux:label>
                    <flux:input type="number" step="0.5" wire:model="form.horas_solicitadas" placeholder="Ej: 4" />
                    <flux:error name="form.horas_solicitadas" />
                </flux:field>

                <flux:field>
                    <flux:label>Motivo / Observaciones</flux:label>
                    <flux:textarea wire:model="form.motivo" placeholder="Opcional..." />
                    <flux:error name="form.motivo" />
                </flux:field>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">{{ $form->solicitud ? 'Guardar Cambios' : 'Guardar y Descontar' }}</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
