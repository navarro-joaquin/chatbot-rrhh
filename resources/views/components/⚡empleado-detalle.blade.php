<?php

use App\Livewire\Forms\EmpleadoContratoForm;
use App\Models\Empleado;
use App\Models\EmpleadoContrato;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Empleado $empleado;
    public EmpleadoContratoForm $contratoForm;
    public bool $showContratoModal = false;
    public bool $showDeleteContratoModal = false;
    public string $message = '';

    public function mount(int $id): void
    {
        $this->loadEmpleado($id);
        $this->contratoForm->setEmpleadoId($this->empleado->id);
    }

    public function loadEmpleado(int $id): void
    {
        $this->empleado = Empleado::with(['contratoVigente', 'antiguedadVigente'])->findOrFail($id);
    }

    public function createContrato(): void
    {
        $this->contratoForm->reset();
        $this->contratoForm->tipo = 'Planta';
        $this->contratoForm->estado = 'Vigente';
        $this->contratoForm->es_vigente = true;
        $this->contratoForm->setEmpleadoId($this->empleado->id);
        $this->message = '';
        $this->showContratoModal = true;
    }

    #[On('editContrato')]
    public function editContrato(int $id): void
    {
        $contrato = EmpleadoContrato::where('empleado_id', $this->empleado->id)->findOrFail($id);
        $this->contratoForm->setContrato($contrato);
        $this->showContratoModal = true;
    }

    public function saveContrato(): void
    {
        $this->contratoForm->save();
        $this->loadEmpleado($this->empleado->id);
        $this->message = 'Contrato guardado correctamente.';
        $this->showContratoModal = false;
        $this->dispatch('pg:eventRefresh-empleado-contratos-table');
    }

    #[On('confirmDeleteContrato')]
    public function confirmDeleteContrato(int $id): void
    {
        $this->contratoForm->contrato = EmpleadoContrato::where('empleado_id', $this->empleado->id)->findOrFail($id);
        $this->showDeleteContratoModal = true;
    }

    public function deleteContrato(): void
    {
        $this->contratoForm->contrato?->delete();
        $this->showDeleteContratoModal = false;
        $this->contratoForm->reset();
        $this->contratoForm->setEmpleadoId($this->empleado->id);
        $this->loadEmpleado($this->empleado->id);
        $this->message = 'Contrato eliminado.';
        $this->dispatch('pg:eventRefresh-empleado-contratos-table');
    }

    public function getAntiguedadVigenteTextoProperty(): string
    {
        $contratoVigente = $this->empleado->contratoVigente;

        if (! $contratoVigente?->fecha_inicio) {
            return 'Sin contrato vigente';
        }

        $fechaInicio = Carbon::parse($contratoVigente->fecha_inicio)->startOfDay();
        $hoy = Carbon::now()->startOfDay();

        if ($fechaInicio->greaterThan($hoy)) {
            return '0a 0m 0d';
        }

        $diferencia = $fechaInicio->diff($hoy);
        $anios = $diferencia->y;
        $meses = $diferencia->m;
        $dias = $diferencia->d;

        $antiguedad = $this->empleado->antiguedadVigente;

        if ($antiguedad) {
            $anios += $antiguedad->anios_reconocidos;
            $meses += $antiguedad->meses_reconocidos;
            $dias += $antiguedad->dias_reconocidos;
        }

        $fechaNormalizada = Carbon::create(2000, 1, 1)
            ->addYears($anios)
            ->addMonths($meses)
            ->addDays($dias);

        return sprintf(
            '%da %dm %dd',
            $fechaNormalizada->year - 2000,
            $fechaNormalizada->month - 1,
            $fechaNormalizada->day - 1
        );
    }
};
?>

<div class="p-6">
    <div class="flex items-center gap-4 mb-8">
        <flux:button icon="arrow-left" variant="ghost" :href="route('empleados.index')" wire:navigate />
        <div>
            <flux:heading size="xl">Detalles de Empleado</flux:heading>
            <flux:subheading>{{ $empleado->nombre_completo }} | CI: {{ $empleado->carnet_identidad }}</flux:subheading>
        </div>
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

    <div class="space-y-12">
        <section class="space-y-4 mt-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <flux:icon name="briefcase" class="text-zinc-500" />
                    <flux:heading size="lg">Contratos</flux:heading>
                </div>
                <flux:button wire:click="createContrato" variant="primary" icon="plus">Nuevo Contrato</flux:button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-1">
                    <flux:text class="text-sm text-zinc-500">Tipo vigente</flux:text>
                    <flux:heading size="md">{{ $empleado->contratoVigente?->tipo ?? 'Sin contrato vigente' }}</flux:heading>
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-1">
                    <flux:text class="text-sm text-zinc-500">Contrato / Item</flux:text>
                    <flux:heading size="md">
                        {{ $empleado->contratoVigente?->numero_contrato ?: ($empleado->contratoVigente?->nro_item ?? 'Sin dato') }}
                    </flux:heading>
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-1">
                    <flux:text class="text-sm text-zinc-500">Inicio</flux:text>
                    <flux:heading size="md">{{ $empleado->contratoVigente?->fecha_inicio?->format('d/m/Y') ?? 'Sin dato' }}</flux:heading>
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 space-y-1">
                    <flux:text class="text-sm text-zinc-500">Antigüedad vigente</flux:text>
                    <flux:heading size="md">{{ $this->antiguedadVigenteTexto }}</flux:heading>
                </div>
            </div>

            <livewire:empleado-contrato-table :empleado-id="$empleado->id" />
        </section>

        <section class="space-y-4 mt-4">
            <div class="flex items-center gap-2">
                <flux:icon name="calendar-days" class="text-zinc-500" />
                <flux:heading size="lg">Vacaciones</flux:heading>
            </div>
            <livewire:vacacion-table :empleado-id="$empleado->id" :is-detail-view="true" />
        </section>

        <section class="space-y-4 mt-4">
            <div class="flex items-center gap-2">
                <flux:icon name="clipboard-document-check" class="text-zinc-500" />
                <flux:heading size="lg">Solicitudes</flux:heading>
            </div>
            <livewire:solicitud-vacacion-table :empleado-id="$empleado->id" :is-detail-view="true" />
        </section>

        <section class="space-y-4 mt-4">
            <div class="flex items-center gap-2">
                <flux:icon name="clock" class="text-zinc-500" />
                <flux:heading size="lg">Compensaciones</flux:heading>
            </div>
            <livewire:compensacion-table :empleado-id="$empleado->id" :is-detail-view="true" />
        </section>
    </div>

    <flux:modal wire:model="showContratoModal" class="md:w-96">
        <form wire:submit="saveContrato" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $contratoForm->contrato ? 'Editar' : 'Nuevo' }} Contrato</flux:heading>
                <flux:subheading>Registre el historial contractual del empleado.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Tipo</flux:label>
                    <flux:select wire:model.live="contratoForm.tipo">
                        <flux:select.option value="Planta">Planta</flux:select.option>
                        <flux:select.option value="Eventual">Eventual</flux:select.option>
                    </flux:select>
                    <flux:error name="contratoForm.tipo" />
                </flux:field>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <flux:select wire:model="contratoForm.estado">
                        <flux:select.option value="Vigente">Vigente</flux:select.option>
                        <flux:select.option value="Finalizado">Finalizado</flux:select.option>
                        <flux:select.option value="Anulado">Anulado</flux:select.option>
                    </flux:select>
                    <flux:error name="contratoForm.estado" />
                </flux:field>

                <flux:field>
                    <flux:label>Numero de contrato</flux:label>
                    <flux:input type="text" wire:model="contratoForm.numero_contrato" placeholder="Ej: C-2026-001" />
                    <flux:error name="contratoForm.numero_contrato" />
                </flux:field>

                <flux:field>
                    <flux:label>Nro. item</flux:label>
                    <flux:input type="text" wire:model="contratoForm.nro_item" placeholder="Ej: 145" />
                    <flux:error name="contratoForm.nro_item" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de inicio</flux:label>
                    <flux:input type="date" wire:model="contratoForm.fecha_inicio" />
                    <flux:error name="contratoForm.fecha_inicio" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de fin</flux:label>
                    <flux:input type="date" wire:model="contratoForm.fecha_fin" />
                    <flux:error name="contratoForm.fecha_fin" />
                </flux:field>

                <flux:field class="md:col-span-2">
                    <flux:label>Resolucion</flux:label>
                    <flux:input type="text" wire:model="contratoForm.resolucion" placeholder="Ej: RA-123/2026" />
                    <flux:error name="contratoForm.resolucion" />
                </flux:field>

                <div class="flex items-center gap-2 pt-8">
                    <flux:switch wire:model="contratoForm.es_vigente" />
                    <flux:label>Contrato vigente</flux:label>
                </div>
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDeleteContratoModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Eliminar contrato?</flux:heading>
                <flux:subheading>Esta accion no se puede deshacer.</flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteContratoModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button wire:click="deleteContrato" variant="danger">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
