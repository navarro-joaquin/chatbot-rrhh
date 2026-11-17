<?php

use App\Livewire\Forms\CompensacionForm;
use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\Gestion;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public CompensacionForm $form;
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public string $message = '';

    public function create(): void
    {
        $this->form->reset();
        $this->form->estado = 'disponible';
        $this->message = '';
        $this->showModal = true;
    }

    #[On('edit')]
    public function edit(int $id): void
    {
        $compensacion = Compensacion::findOrFail($id);
        $this->form->setCompensacion($compensacion);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->form->save();

        $this->message = 'Compensacion guardada correctamente.';
        $this->showModal = false;
        $this->dispatch('pg:eventRefresh-compensaciones-table');
    }

    #[On('confirmDelete')]
    public function confirmDelete(int $id): void
    {
        $this->form->compensacion = Compensacion::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->form->compensacion?->delete();
        $this->showDeleteModal = false;
        $this->form->reset();

        $this->message = 'Compensacion eliminada.';
        $this->dispatch('pg:eventRefresh-compensaciones-table');
    }

    public function with(): array
    {
        return [
            'empleados' => Empleado::query()
                ->where('estado', true)
                ->orderBy('nombre_completo')
                ->get(),
            'gestiones' => Gestion::orderBy('anio', 'desc')->get(),
            'estados' => [
                'disponible' => 'Disponible',
                'utilizado' => 'Utilizado',
                'vencido' => 'Vencido',
            ],
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">Compensaciones</flux:heading>
        <flux:button wire:click="create" variant="primary" icon="plus">Registrar Compensacion</flux:button>
    </div>

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

    <livewire:compensacion-table />

    <flux:modal wire:model="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->compensacion ? 'Editar' : 'Nueva' }} Compensacion</flux:heading>
                <flux:subheading>Registre las horas compensatorias asignadas a un empleado.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:label>Empleado</flux:label>
                    <flux:select wire:model="form.empleado_id">
                        <flux:select.option value="">Seleccione un empleado...</flux:select.option>
                        @foreach ($empleados as $empleado)
                            <flux:select.option value="{{ $empleado->id }}">{{ $empleado->nombre_completo }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.empleado_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Gestion</flux:label>
                    <flux:select wire:model="form.gestion_id">
                        <flux:select.option value="">Seleccione una gestion...</flux:select.option>
                        @foreach ($gestiones as $gestion)
                            <flux:select.option value="{{ $gestion->id }}">{{ $gestion->anio }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.gestion_id" />
                </flux:field>

                <div class="grid grid-cols-2 gap-2">
                    <flux:field>
                        <flux:label>Horas</flux:label>
                        <flux:input type="number" min="0" max="999" step="1" wire:model.live.debounce.800ms="form.cantidad_horas_horas" placeholder="Ej: 5" />
                        <flux:error name="form.cantidad_horas_horas" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Minutos</flux:label>
                        <flux:input type="number" min="0" max="59" step="1" wire:model.live.debounce.800ms="form.cantidad_horas_minutos" placeholder="Ej: 30" />
                        <flux:error name="form.cantidad_horas_minutos" />
                    </flux:field>
                </div>

                <flux:field>
                    @if($form->equivalenciaHoras())
                        <flux:description>Equivale a {{ $form->equivalenciaHoras() }}.</flux:description>
                    @endif
                    <flux:error name="form.cantidad_horas" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha Registro</flux:label>
                    <flux:input type="date" wire:model="form.fecha_registro" />
                    <flux:error name="form.fecha_registro" />
                </flux:field>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <flux:select wire:model="form.estado">
                        @foreach ($estados as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.estado" />
                </flux:field>

                <flux:field>
                    <flux:label>Descripcion / Motivo</flux:label>
                    <flux:textarea wire:model="form.descripcion" placeholder="Ej: Horas extras por cierre de gestion..." rows="2" />
                    <flux:error name="form.descripcion" />
                </flux:field>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Eliminar compensacion</flux:heading>
                <flux:subheading>Esta accion no se puede deshacer.</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button wire:click="delete" variant="danger">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
