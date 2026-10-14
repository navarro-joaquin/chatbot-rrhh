<x-layouts::app :title="__('Dashboard')">
    @php
        $tarjeta = 'rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900';
        $enlace = 'text-sm font-medium text-emerald-700 transition hover:text-emerald-600 dark:text-emerald-300 dark:hover:text-emerald-200';
        $vacio = 'rounded-xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300';
        $alertas = $contratosPorVencer + $empleadosSinContratoVigente;
        $maxSaldo = max(1, (float) $topSaldosVacaciones->max('total_dias_disponibles'));
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Resumen de RRHH</p>
                <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">Dashboard operativo</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                    Gestión {{ $currentGestion?->anio ?? now()->year }} · {{ number_format($empleadosActivos) }} empleados activos de {{ number_format($totalEmpleados) }} registrados · {{ number_format($contratosVigentes) }} contratos vigentes
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('empleados.index') }}" class="inline-flex items-center rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-500">Ver empleados</a>
                <a href="{{ route('solicitudes-vacaciones.index') }}" class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">Solicitudes de vacaciones</a>
                <a href="{{ route('solicitudes-compensaciones.index') }}" class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:border-emerald-200 hover:text-emerald-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">Solicitudes de compensación</a>
            </div>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="{{ $tarjeta }} border-t-4 border-t-emerald-500">
                <p class="text-xs font-medium uppercase tracking-[0.15em] text-zinc-500 dark:text-zinc-400">Vacaciones disponibles ({{ $currentGestion?->anio ?? now()->year }})</p>
                <p class="mt-3 text-3xl font-semibold leading-tight text-zinc-900 dark:text-white">{{ \App\Support\VacacionesTiempo::aTextoDias($diasVacacionesDisponibles) ?? '0 días' }}</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ number_format($solicitudesMes) }} solicitudes este mes</p>
            </article>

            <article class="{{ $tarjeta }} border-t-4 border-t-sky-500">
                <p class="text-xs font-medium uppercase tracking-[0.15em] text-zinc-500 dark:text-zinc-400">Compensaciones disponibles</p>
                <p class="mt-3 text-3xl font-semibold leading-tight text-zinc-900 dark:text-white">{{ \App\Support\VacacionesTiempo::aTextoHoras($horasCompensacionDisponibles) ?? '0 horas' }}</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ number_format($solicitudesCompensacionMes) }} solicitudes este mes</p>
            </article>

            <article class="{{ $tarjeta }} border-t-4 border-t-amber-500">
                <p class="text-xs font-medium uppercase tracking-[0.15em] text-zinc-500 dark:text-zinc-400">Contratos por vencer</p>
                <p class="mt-3 text-3xl font-semibold leading-tight text-amber-600 dark:text-amber-300">{{ number_format($contratosPorVencer) }}</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">vencen en los próximos 30 días</p>
            </article>

            <article class="{{ $tarjeta }} border-t-4 {{ $alertas > 0 ? 'border-t-rose-500' : 'border-t-zinc-300 dark:border-t-zinc-600' }}">
                <p class="text-xs font-medium uppercase tracking-[0.15em] text-zinc-500 dark:text-zinc-400">Alertas activas</p>
                <p class="mt-3 text-3xl font-semibold leading-tight {{ $alertas > 0 ? 'text-rose-600 dark:text-rose-300' : 'text-zinc-900 dark:text-white' }}">{{ number_format($alertas) }}</p>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ number_format($empleadosSinContratoVigente) }} activos sin contrato vigente</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article class="{{ $tarjeta }} xl:col-span-2">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Solicitudes por mes</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Cantidad de solicitudes registradas en {{ now()->year }}</p>
                    </div>
                    <a href="{{ route('solicitudes-vacaciones.index') }}" class="{{ $enlace }}">Ver solicitudes</a>
                </div>

                <x-dashboard.barras :etiquetas="$meses" :series="$serieSolicitudesMensuales" />
            </article>

            <article class="{{ $tarjeta }}">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Estado de solicitudes de vacaciones</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">Distribución de las solicitudes cargadas</p>
                </div>

                <x-dashboard.donut :items="$donutSolicitudes" centro="solicitudes" />
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article class="{{ $tarjeta }}">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Tipos de contrato</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Composición del personal contratado</p>
                    </div>
                    <a href="{{ route('empleados.index') }}" class="{{ $enlace }}">Administrar</a>
                </div>

                <x-dashboard.donut :items="$donutTiposContrato" centro="contratos" />
            </article>

            <article class="{{ $tarjeta }}">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Estado contractual</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Distribución general de contratos</p>
                    </div>
                    <a href="{{ route('empleados.index') }}" class="{{ $enlace }}">Ver detalle</a>
                </div>

                <x-dashboard.donut :items="$donutEstadoContratos" centro="contratos" />

                @if ($empleadosSinContratoVigente > 0)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
                        {{ number_format($empleadosSinContratoVigente) }} empleados activos no tienen un contrato vigente marcado.
                    </div>
                @endif
            </article>

            <article class="{{ $tarjeta }}">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Mayores saldos de vacaciones</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Top 5 con saldo acumulado disponible</p>
                    </div>
                    <a href="{{ route('vacaciones.index') }}" class="{{ $enlace }}">Ver saldos</a>
                </div>

                <div class="space-y-4">
                    @forelse ($topSaldosVacaciones as $vacacion)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $vacacion->nombre_completo }}</p>
                                <span class="shrink-0 text-zinc-600 dark:text-zinc-300">{{ \App\Support\VacacionesTiempo::aTextoDias((float) $vacacion->total_dias_disponibles) ?? '—' }}</span>
                            </div>
                            <div class="mt-2 h-2.5 rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-2.5 rounded-full bg-emerald-500" style="width: {{ max(4, (int) round(((float) $vacacion->total_dias_disponibles / $maxSaldo) * 100)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="{{ $vacio }}">No hay saldos de vacaciones cargados.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="{{ $tarjeta }}">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Contratos próximos a vencer</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Próximos 60 días</p>
                    </div>
                    <a href="{{ route('empleados.index') }}" class="{{ $enlace }}">Revisar</a>
                </div>

                <div class="space-y-2">
                    @forelse ($contratosProximosAVencer as $contrato)
                        @php($dias = (int) now()->startOfDay()->diffInDays($contrato->fecha_fin, false))
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-zinc-800 dark:text-white">{{ $contrato->empleado?->nombre_completo }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $contrato->tipo }} · vence {{ optional($contrato->fecha_fin)->format('d/m/Y') }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-medium {{ $dias <= 30 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                {{ $dias <= 0 ? 'hoy' : 'en '.$dias.' '.($dias === 1 ? 'día' : 'días') }}
                            </span>
                        </div>
                    @empty
                        <p class="{{ $vacio }}">No hay vencimientos cercanos.</p>
                    @endforelse
                </div>
            </article>

            <article class="{{ $tarjeta }}">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Feriados próximos</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Calendario activo</p>
                    </div>
                    <a href="{{ route('feriados.index') }}" class="{{ $enlace }}">Gestionar</a>
                </div>

                <div class="space-y-2">
                    @forelse ($feriadosProximos as $feriado)
                        <div class="flex items-center gap-4 rounded-xl bg-zinc-50 px-4 py-3 dark:bg-zinc-800">
                            <div class="flex size-12 shrink-0 flex-col items-center justify-center rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                <span class="text-lg font-semibold leading-none">{{ $feriado->fecha->format('d') }}</span>
                                <span class="text-[10px] uppercase">{{ $feriado->fecha->translatedFormat('M') }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-zinc-800 dark:text-white">{{ $feriado->nombre }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $feriado->fecha->translatedFormat('l d \\d\\e F \\d\\e Y') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="{{ $vacio }}">No hay feriados futuros registrados.</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-layouts::app>
