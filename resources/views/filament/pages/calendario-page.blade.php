@php
    $semanas = $this->semanas();
    $resumo  = $this->resumo();
    $mesLabel = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $this->mes.'-01')
        ->locale('pt_PT')->isoFormat('MMMM [de] YYYY');

    // Classes escritas por extenso de proposito: o purge do Tailwind nao ve
    // nomes montados por interpolacao e deitava as cores todas fora.
    $estilos = [
        'rotina_tarefa'    => ['rotina',    'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/30'],
        'rotina_pagamento' => ['recorrente','bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/30'],
        'tarefa'           => ['tarefa',    'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/30'],
        'receber'          => ['a receber', 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/30'],
        'pagar'            => ['a pagar',   'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/30'],
    ];

    $euros = fn ($cents) => $cents ? number_format($cents / 100, 2, ',', ' ').' €' : null;
@endphp

<x-filament-panels::page>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-filament::section class="text-center">
            <div class="text-xs text-gray-500 dark:text-gray-400">A receber este mês</div>
            <div class="mt-1 text-xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $euros($resumo['a_receber']) ?? '—' }}</div>
        </x-filament::section>
        <x-filament::section class="text-center">
            <div class="text-xs text-gray-500 dark:text-gray-400">A pagar este mês</div>
            <div class="mt-1 text-xl font-semibold text-rose-600 dark:text-rose-400">{{ $euros($resumo['a_pagar']) ?? '—' }}</div>
        </x-filament::section>
        <x-filament::section class="text-center">
            <div class="text-xs text-gray-500 dark:text-gray-400">Por fazer</div>
            <div class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ $resumo['por_fazer'] }}</div>
        </x-filament::section>
        <x-filament::section class="text-center">
            <div class="text-xs text-gray-500 dark:text-gray-400">Em atraso</div>
            <div class="mt-1 text-xl font-semibold {{ $resumo['atrasados'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-400' }}">{{ $resumo['atrasados'] }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            <span class="capitalize">{{ $mesLabel }}</span>
        </x-slot>

        <x-slot name="description">
            <div class="flex flex-wrap items-center gap-3 text-xs">
                @foreach ($estilos as $par)
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full ring-1 {{ $par[1] }}"></span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $par[0] }}</span>
                    </span>
                @endforeach
            </div>
        </x-slot>

        <div class="overflow-x-auto">
            <div class="min-w-[52rem]">

                <div class="grid grid-cols-7 gap-px border-b border-gray-200 pb-2 text-center text-xs font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    @foreach (['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $d)
                        <div>{{ $d }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700">
                    @foreach ($semanas as $semana)
                        @foreach ($semana as $celula)
                            <div @class([
                                'min-h-28 bg-white p-1.5 dark:bg-gray-900',
                                'opacity-40' => ! $celula['noMes'],
                                'ring-2 ring-inset ring-primary-500' => $celula['hoje'],
                            ])>
                                <div @class([
                                    'mb-1 text-right text-xs',
                                    'font-bold text-primary-600 dark:text-primary-400' => $celula['hoje'],
                                    'text-gray-400' => ! $celula['hoje'],
                                ])>{{ $celula['data']->day }}</div>

                                <div class="space-y-1">
                                    @foreach ($celula['eventos'] as $ev)
                                        @php
                                            $estilo = $estilos[$ev['tipo']][1] ?? 'bg-gray-100 text-gray-700 ring-gray-500/20';
                                            $eRotina = str_starts_with($ev['tipo'], 'rotina_');
                                        @endphp

                                        <div @class([
                                            'group flex items-start gap-1 rounded px-1.5 py-1 text-[11px] leading-tight ring-1 ring-inset',
                                            $estilo,
                                            'line-through opacity-60' => $ev['concluido'],
                                        ])>
                                            @if ($eRotina)
                                                <button
                                                    type="button"
                                                    wire:click="marcarRotina({{ $ev['id'] }})"
                                                    wire:loading.attr="disabled"
                                                    title="{{ $ev['concluido'] ? 'Reabrir' : 'Marcar como feita' }}"
                                                    class="mt-px shrink-0"
                                                >{!! $ev['concluido'] ? '&#10003;' : '&#9675;' !!}</button>
                                            @endif

                                            <div class="min-w-0 flex-1">
                                                @if ($ev['url'])
                                                    <a href="{{ $ev['url'] }}" class="block truncate hover:underline">{{ $ev['titulo'] }}</a>
                                                @else
                                                    <span class="block truncate">{{ $ev['titulo'] }}</span>
                                                @endif

                                                @if (! empty($ev['contexto']))
                                                    <span class="block truncate opacity-70">{{ $ev['contexto'] }}</span>
                                                @endif

                                                @if ($ev['valor'])
                                                    <span class="block font-medium">{{ $euros($ev['valor']) }}</span>
                                                @endif
                                            </div>

                                            @if ($ev['atrasado'])
                                                <span class="shrink-0 font-bold text-danger-600 dark:text-danger-400" title="Em atraso">!</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>

            </div>
        </div>
    </x-filament::section>

</x-filament-panels::page>
