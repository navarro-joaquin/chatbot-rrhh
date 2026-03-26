<?php

use Livewire\Component;
use App\Models\Empleado;
use Livewire\Attributes\On;
use App\Livewire\Forms\SolicitudVacacionForm;

new class extends Component
{
    public SolicitudVacacionForm $form;
    public bool $showModal = false;
    public string $message = '';

    public function create(): void
    {
        $this->form->reset();
        $this->message = '';
        $this->showModal = true;
    }

    public function updated($property): void
    {
        if (in_array($property, ['form.fecha_inicio', 'form.fecha_fin'])) {
            $this->form->calcularDias();
        }
    }

    public function save(): void
    {
        $this->form->save();

        if ($this->getErrorBag()->has('form.dias_solicitados')) {
            return;
        }

        $this->message = 'Solicitud de vacación registrada y días descontados correctamente.';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-solicitudes-vacaciones-table');
        $this->dispatch('notify', $this->message);
    }

    public function with(): array
    {
        return [
            'empleados' => Empleado::where('estado', true)->orderBy('nombre_completo')->get(),
        ];
    }
};
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">Solicitudes de Vacación</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Registrar Solicitud</flux:button>
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

    <livewire:solicitud-vacacion-table />

    {{-- Modal Form --}}
    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva Solicitud</flux:heading>
                <flux:subheading>Registre una solicitud de vacación. Los días se descontarán automáticamente de las gestiones más antiguas.</flux:subheading>
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
            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Fecha Inicio</flux:label>
                    <flux:input type="date" wire:model.live="form.fecha_inicio" />
                    <flux:error name="form.fecha_inicio" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha Fin</flux:label>
                    <flux:input type="date" wire:model.live="form.fecha_fin" />
                    <flux:error name="form.fecha_fin" />
                </flux:field>
            </div>
            <flux:field>
                <flux:label>Días a solicitar</flux:label>
                <flux:input type="number" step="0.5" wire:model="form.dias_solicitados" placeholder="Ej: 5" />
                <flux:error name="form.dias_solicitados" />
            </flux:field>

            <flux:field>
                <flux:label>Motivo / Observaciones</flux:label>
                <flux:textarea wire:model="form.motivo" placeholder="Opcional..." />
                <flux:error name="form.motivo" />
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Guardar y Descontar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
