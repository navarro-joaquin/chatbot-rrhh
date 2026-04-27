<?php

use App\Livewire\Forms\CompensacionForm;
use App\Models\Compensacion;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
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

    public function updatedFormEmpleadoId(): void
    {
        $this->form->syncContratoVigente();
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
        $contratoVigente = $this->form->empleado_id
            ? EmpleadoContrato::query()
                ->where('empleado_id', $this->form->empleado_id)
                ->where('es_vigente', true)
                ->first()
            : null;

        return [
            'empleados' => Empleado::query()
                ->where('estado', true)
                ->whereHas('contratoVigente')
                ->orderBy('nombre_completo')
                ->get(),
            'gestiones' => Gestion::orderBy('anio', 'desc')->get(),
            'estados' => [
                'disponible' => 'Disponible',
                'utilizado' => 'Utilizado',
                'vencido' => 'Vencido',
            ],
            'contratoVigente' => $contratoVigente,
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
                <flux:subheading>Registre las horas compensatorias asignadas a un empleado usando su contrato vigente.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <flux:field>
                    <flux:label>Empleado</flux:label>
                    <flux:select wire:model.live="form.empleado_id">
                        <flux:select.option value="">Seleccione un empleado...</flux:select.option>
                        @foreach ($empleados as $empleado)
                            <flux:select.option value="{{ $empleado->id }}">{{ $empleado->nombre_completo }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.empleado_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Contrato vigente</flux:label>
                    <flux:select wire:model="form.contrato_id" :disabled="! $form->empleado_id || ! $contratoVigente">
                        <flux:select.option value="">
                            {{ $form->empleado_id ? 'Seleccione un contrato vigente...' : 'Primero seleccione un empleado...' }}
                        </flux:select.option>
                        @if ($contratoVigente)
                            <flux:select.option value="{{ $contratoVigente->id }}">
                                {{ $contratoVigente->numero_contrato ?: ($contratoVigente->nro_item ?: 'Contrato #'.$contratoVigente->id) }}
                            </flux:select.option>
                        @endif
                    </flux:select>
                    <flux:error name="form.contrato_id" />
                </flux:field>

                @if ($contratoVigente)
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                        Tipo: {{ $contratoVigente->tipo }} | Inicio: {{ $contratoVigente->fecha_inicio?->format('d/m/Y') ?? 'Sin fecha' }} | Estado: {{ $contratoVigente->estado }}
                    </div>
                @elseif ($form->empleado_id)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                        El empleado seleccionado no tiene un contrato vigente marcado.
                    </div>
                @endif

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

                <flux:field>
                    <flux:label>Horas</flux:label>
                    <flux:input type="number" step="0.5" wire:model="form.cantidad_horas" placeholder="Ej: 8" />
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

                <flux:field class="md:col-span-2">
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
