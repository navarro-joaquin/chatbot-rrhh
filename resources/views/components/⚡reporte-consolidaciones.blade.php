<?php

use App\Models\Gestion;
use App\Services\VacacionService;
use App\Support\VacacionesTiempo;
use Illuminate\Support\Carbon;
use Livewire\Component;

new class extends Component
{
    public int $anio;

    public ?int $mes = null;

    public function mount(): void
    {
        $this->anio = now()->year;
    }

    public function updatedAnio(): void
    {
        $this->mes = null;
    }

    /**
     * Selecciona un mes para ver su detalle; volver a elegirlo muestra el año completo.
     */
    public function seleccionarMes(int $mes): void
    {
        $this->mes = $this->mes === $mes ? null : $mes;
    }

    public function with(): array
    {
        $porMes = app(VacacionService::class)->consolidacionesPorContrato($this->anio)->groupBy('mes');

        $meses = collect(range(1, 12))->map(fn (int $mes) => [
            'numero' => $mes,
            'nombre' => ucfirst(Carbon::create($this->anio, $mes)->translatedFormat('F')),
            'empleados' => $porMes->get($mes)?->count() ?? 0,
            'dias' => (float) ($porMes->get($mes)?->sum('dias') ?? 0),
        ]);

        return [
            'anios' => $this->aniosDisponibles(),
            'meses' => $meses,
            'totalEmpleados' => $meses->sum('empleados'),
            'totalDias' => $meses->sum('dias'),
            'nombreMes' => $this->mes ? $meses->firstWhere('numero', $this->mes)['nombre'] : null,
            'texto' => fn (float $dias) => $dias > 0 ? VacacionesTiempo::aTextoDiasConSigno($dias) : '—',
        ];
    }

    /**
     * Años de las gestiones registradas, más el año actual.
     *
     * @return list<int>
     */
    private function aniosDisponibles(): array
    {
        return Gestion::query()
            ->pluck('anio')
            ->push(now()->year)
            ->map(fn ($anio) => (int) $anio)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }
};
?>

<div class="p-6">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">Consolidaciones de vacaciones</flux:heading>
            <flux:subheading>Empleados con contrato indefinido vigente agrupados por el mes en que su contrato cumple años, con los días de vacaciones que les corresponden.</flux:subheading>
        </div>

        <flux:field class="w-40">
            <flux:label>Año</flux:label>
            <flux:select wire:model.live="anio">
                @foreach ($anios as $opcion)
                    <flux:select.option value="{{ $opcion }}">{{ $opcion }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <div class="mb-6 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 text-left text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3 font-semibold">Mes</th>
                    <th class="px-4 py-3 text-right font-semibold">Empleados</th>
                    <th class="px-4 py-3 text-right font-semibold">Días que corresponden</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($meses as $fila)
                    <tr
                        wire:key="mes-{{ $fila['numero'] }}"
                        @if ($fila['empleados'] > 0) wire:click="seleccionarMes({{ $fila['numero'] }})" @endif
                        @class([
                            'transition-colors',
                            'cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800' => $fila['empleados'] > 0,
                            'text-zinc-400 dark:text-zinc-500' => $fila['empleados'] === 0,
                            'bg-emerald-50 font-semibold dark:bg-emerald-950/40' => $mes === $fila['numero'],
                        ])
                    >
                        <td class="px-4 py-2.5">{{ $fila['nombre'] }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $fila['empleados'] }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $texto($fila['dias']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-zinc-50 font-semibold text-zinc-800 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <td class="px-4 py-3">Total {{ $anio }}</td>
                    <td class="px-4 py-3 text-right">{{ $totalEmpleados }}</td>
                    <td class="px-4 py-3 text-right">{{ $texto($totalDias) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mb-3 flex items-center justify-between gap-3">
        <flux:heading size="lg">
            Detalle de {{ $nombreMes ? $nombreMes.' '.$anio : 'todo '.$anio }}
        </flux:heading>

        @if ($mes)
            <flux:button size="sm" variant="ghost" wire:click="$set('mes', null)">Ver todo el año</flux:button>
        @endif
    </div>

    <livewire:consolidacion-mensual-table :anio="$anio" :mes="$mes" :wire:key="'consolidaciones-'.$anio.'-'.$mes" />
</div>
