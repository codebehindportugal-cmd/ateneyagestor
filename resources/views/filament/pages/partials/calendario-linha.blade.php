@php
    $cor = $cores[$ev['tipo']] ?? [null, 'bg-gray-400', 'text-gray-600'];
    $marcavel = str_starts_with($ev['tipo'], 'rotina_');
@endphp

<div @class(['flex items-center gap-3 px-4 py-2.5 text-sm', 'opacity-50' => $ev['concluido']])>
    <span class="h-2 w-2 shrink-0 rounded-full {{ $cor[1] }}"></span>

    <div class="min-w-0 flex-1">
        @if ($ev['url'])
            <a href="{{ $ev['url'] }}" @class(['block truncate font-medium hover:underline', 'line-through' => $ev['concluido']])>{{ $ev['titulo'] }}</a>
        @else
            <span @class(['block truncate font-medium', 'line-through' => $ev['concluido']])>{{ $ev['titulo'] }}</span>
        @endif

        @if (! empty($ev['contexto']))
            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $ev['contexto'] }}</span>
        @endif
    </div>

    @if ($ev['valor'])
        <span class="shrink-0 tabular-nums text-gray-600 dark:text-gray-300">{{ $euros($ev['valor']) }}</span>
    @endif

    @if ($ev['atrasado'])
        <span class="shrink-0 rounded bg-rose-100 px-1.5 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">em atraso</span>
    @endif

    @if ($marcavel)
        <button
            type="button"
            wire:click="marcarRotina({{ $ev['id'] }})"
            wire:loading.attr="disabled"
            class="shrink-0 rounded-md border border-gray-300 px-2 py-1 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
        >{{ $ev['concluido'] ? 'Reabrir' : 'Marcar' }}</button>
    @endif
</div>
