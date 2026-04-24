<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-linear-to-br from-white via-zinc-50 to-emerald-50/70 p-6 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:via-zinc-900 dark:to-emerald-950/30">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">Resumen de RRHH</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">Dashboard operativo</h1>
                    <p class="max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">
                        Seguimiento rápido del personal, contratos y saldos de la gestión
                        {{ $currentGestion?->anio ?? now()->year }}.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/60 bg-white/80 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Cobertura contractual</p>
                        <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $contratosVigentes }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">contratos vigentes registrados</p>
                    </div>
                    <div class="rounded-2xl border border-white/60 bg-white/80 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Alertas activas</p>
                        <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $contratosPorVencer + $empleadosSinContratoVigente }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">casos que requieren revisión</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-3xl border border-white/60 bg-white/80 px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Empleados activos</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($empleadosActivos) }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ number_format($totalEmpleados) }} registrados en total</p>
            </article>

            <article class="rounded-3xl border border-zinc-200 bg-white/80 px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Contratos por vencer</p>
                <p class="mt-2 text-2xl font-semibold text-amber-600 dark:text-amber-300">{{ number_format($contratosPorVencer) }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">vencen en los próximos 30 días</p>
            </article>

            <article class="rounded-3xl border border-white/60 bg-white/80 px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Vacaciones disponibles</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($diasVacacionesDisponibles, 1) }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">promedio {{ number_format($promedioVacaciones, 1) }} días por registro</p>
            </article>

            <article class="rounded-3xl border border-white/60 bg-white/80 px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Compensaciones disponibles</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($horasCompensacionDisponibles, 1) }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ number_format($solicitudesMes) }} solicitudes este mes</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[1.2fr_1fr_1fr]">
            <article class="rounded-3xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Estado contractual</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">Distribución general de contratos cargados</p>
                    </div>
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ number_format($contratosVigentes) }} vigentes</span>
                </div>

                <div class="mt-5 space-y-4">
                    @php($maxContratos = max(1, (int) $resumenContratos->max('total')))
                    @foreach ($resumenContratos as $item)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ ucfirst(strtolower($item->estado)) }}</span>
                                <span class="text-zinc-500 dark:text-zinc-400">{{ number_format($item->total) }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div
                                    class="h-2 rounded-full bg-emerald-500"
                                    style="width: {{ max(10, (int) round(($item->total / $maxContratos) * 100)) }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
                    {{ number_format($empleadosSinContratoVigente) }} empleados activos no tienen un contrato vigente marcado.
                </div>
            </article>

            <article class="rounded-3xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Tipos de contrato</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">Composición del personal contratado</p>

                <div class="mt-5 space-y-3">
                    @php($maxTipos = max(1, (int) $resumenTiposContrato->max('total')))
                    @forelse ($resumenTiposContrato as $item)
                        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-zinc-800 dark:text-white">{{ $item->tipo }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($item->total) }}</p>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-white dark:bg-zinc-700">
                                <div class="h-2 rounded-full bg-sky-500" style="width: {{ max(12, (int) round(($item->total / $maxTipos) * 100)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-white">Aún no hay contratos registrados.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Solicitudes de vacaciones</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">Estado de las solicitudes cargadas</p>

                <div class="mt-5 space-y-3">
                    @php($maxSolicitudes = max(1, (int) $resumenSolicitudes->max('total')))
                    @forelse ($resumenSolicitudes as $item)
                        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-zinc-800 dark:text-white">{{ ucfirst($item->estado) }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($item->total) }}</p>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-white dark:bg-zinc-700">
                                <div class="h-2 rounded-full bg-violet-500" style="width: {{ max(12, (int) round(($item->total / $maxTipos) * 100)) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-white">Todavía no se registraron solicitudes.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-3xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Contratos próximos a vencer</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">Próximos 60 días</p>

                <div class="mt-5 space-y-3">
                    @forelse ($contratosProximosAVencer as $contrato)
                        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800">
                            <p class="font-medium text-zinc-800 dark:text-white">{{ $contrato->empleado?->nombre_completo }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $contrato->tipo }} · vence {{ optional($contrato->fecha_fin)->format('d/m/Y') }}
                            </p>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-white">No hay vencimientos cercanos.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Feriados próximos</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">Calendario activo</p>

                <div class="mt-5 space-y-3">
                    @forelse ($feriadosProximos as $feriado)
                        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800">
                            <p class="font-medium text-zinc-800 dark:text-white">{{ $feriado->nombre }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $feriado->fecha->translatedFormat('d \\d\\e F \\d\\e Y') }}
                            </p>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-white">No hay feriados futuros registrados.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Mayores saldos de vacaciones</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">Top registros de la gestión actual</p>

                <div class="mt-5 space-y-3">
                    @forelse ($topSaldosVacaciones as $vacacion)
                        <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $vacacion->empleado?->nombre_completo }}</p>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
                                    {{ number_format((float) $vacacion->dias_disponibles, 1) }} días
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-white">No hay saldos de vacaciones cargados.</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-layouts::app>
