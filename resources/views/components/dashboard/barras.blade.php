@props(['etiquetas', 'series'])

@php
    $maximo = max(1, (float) collect($series)->flatMap(fn ($serie) => $serie['valores'])->max());
@endphp

<div>
    <div class="mb-3 flex flex-wrap gap-4 text-sm">
        @foreach ($series as $serie)
            <span class="flex items-center gap-2 text-zinc-700 dark:text-zinc-200">
                <span class="size-3 rounded-sm {{ $serie['color'] }}"></span>
                {{ $serie['nombre'] }}
            </span>
        @endforeach
    </div>

    <div class="flex h-56 items-end gap-1.5 border-b border-zinc-200 sm:gap-3 dark:border-zinc-700">
        @foreach ($etiquetas as $mes => $etiqueta)
            <div class="flex h-full flex-1 items-end justify-center gap-0.5">
                @foreach ($series as $serie)
                    @php($valor = (float) ($serie['valores'][$mes] ?? 0))
                    <div class="flex h-full w-full max-w-5 flex-col justify-end" title="{{ $serie['nombre'] }} · {{ $etiqueta }}: {{ $serie['formato'] ?? number_format($valor, 0) }}">
                        @if ($valor > 0)
                            <span class="mb-1 text-center text-[10px] font-medium text-zinc-500 dark:text-zinc-400">{{ rtrim(rtrim(number_format($valor, 1), '0'), '.') }}</span>
                        @endif
                        <div class="w-full rounded-t {{ $serie['color'] }}" style="height: {{ $valor > 0 ? max(3, round($valor / $maximo * 85)) : 0 }}%"></div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="mt-2 flex gap-1.5 sm:gap-3">
        @foreach ($etiquetas as $etiqueta)
            <span class="flex-1 text-center text-xs text-zinc-500 dark:text-zinc-400">{{ $etiqueta }}</span>
        @endforeach
    </div>
</div>
