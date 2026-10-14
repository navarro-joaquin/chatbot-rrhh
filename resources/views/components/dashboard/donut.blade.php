@props(['items', 'centro' => 'total'])

@php
    $colores = [
        ['stroke' => 'stroke-emerald-500', 'bg' => 'bg-emerald-500'],
        ['stroke' => 'stroke-sky-500', 'bg' => 'bg-sky-500'],
        ['stroke' => 'stroke-violet-500', 'bg' => 'bg-violet-500'],
        ['stroke' => 'stroke-amber-500', 'bg' => 'bg-amber-500'],
        ['stroke' => 'stroke-rose-500', 'bg' => 'bg-rose-500'],
        ['stroke' => 'stroke-zinc-400', 'bg' => 'bg-zinc-400'],
    ];
    $total = (float) collect($items)->sum('value');
    $acumulado = 0;
@endphp

<div class="flex flex-col items-center gap-5 sm:flex-row">
    <div class="relative size-40 shrink-0">
        <svg viewBox="0 0 36 36" class="size-full -rotate-90">
            <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="4" class="stroke-zinc-100 dark:stroke-zinc-800" />
            @if ($total > 0)
                @foreach ($items as $i => $item)
                    @php
                        $porcentaje = $item['value'] / $total * 100;
                        $color = $colores[$i % count($colores)]['stroke'];
                    @endphp
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke-width="4"
                        class="{{ $color }}"
                        stroke-dasharray="{{ round($porcentaje, 3) }} {{ round(100 - $porcentaje, 3) }}"
                        stroke-dashoffset="{{ round(-$acumulado, 3) }}">
                        <title>{{ $item['label'] }}: {{ number_format($item['value']) }}</title>
                    </circle>
                    @php($acumulado += $porcentaje)
                @endforeach
            @endif
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ number_format($total) }}</span>
            <span class="text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $centro }}</span>
        </div>
    </div>

    <ul class="w-full space-y-2 text-sm">
        @forelse ($items as $i => $item)
            <li class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-2 text-zinc-700 dark:text-zinc-200">
                    <span class="size-3 rounded-full {{ $colores[$i % count($colores)]['bg'] }}"></span>
                    {{ $item['label'] }}
                </span>
                <span class="font-medium text-zinc-900 dark:text-white">
                    {{ number_format($item['value']) }}
                    <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">
                        ({{ $total > 0 ? round($item['value'] / $total * 100) : 0 }}%)
                    </span>
                </span>
            </li>
        @empty
            <li class="text-zinc-500 dark:text-zinc-400">Sin datos registrados.</li>
        @endforelse
    </ul>
</div>
