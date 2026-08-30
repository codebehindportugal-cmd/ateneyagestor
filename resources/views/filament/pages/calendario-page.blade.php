@php
    $semanas  = $this->semanas();
    $resumo   = $this->resumo();
    $doDia    = $this->eventosDoDia();
    $inicio   = $this->primeiroDia();
    $mesLabel = $inicio->locale('pt_PT')->isoFormat('MMMM [de] YYYY');

    // Escritas por extenso de propósito: o purge do Tailwind não vê nomes
    // montados por interpolação e deitava as cores todas fora em produção.
    $cores = [
        'rotina_tarefa'    => ['rotina',               'bg-indigo-500',  'text-indigo-700 dark:text-indigo-300'],
        'rotina_pagamento' => ['pagamento recorrente', 'bg-rose-500',    'text-rose-700 dark:text-rose-300'],
        'tarefa'           => ['prazo de tarefa',      'bg-amber-500',   'text-amber-700 dark:text-amber-300'],
        'receber'          => ['a receber',            'bg-emerald-500', 'text-emerald-700 dark:text-emerald-300'],
        'pagar'            => ['a pagar',              'bg-sky-500',     'text-sky-700 dark:text-sky-300'],
    ];

    $euros = fn ($cents) => $cents ? number_format($cents / 100, 2, ',', ' ').' €' : null;
    $maxPorDia = 3;
@endphp

<x-filament-panels::page>

    {{-- Resumo do mês: uma caixa só, quatro colunas --}}
    <div class="grid grid-cols-2 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 sm:grid-cols-4 sm:divide-x sm:divide-gray-200 dark:bg-gray-900 dark:ring-white/10 dark:sm:divide-gray-800">
        <div class="px-4 py-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">A receber</div>
            <div class="mt-0.5 text-lg font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $euros($resumo['a_receber']) ?? '—' }}</div>
        </div>
        <div class="px-4 py-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">A pagar</div>
            <div class="mt-0.5 text-lg font-semibold tabular-nums text-rose-600 dark:text-rose-400">{{ $euros($resumo['a_pagar']) ?? '—' }}</div>
        </div>
        <div class="px-4 py-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">Por fazer</div>
            <div class="mt-0.5 text-lg font-semibold tabular-nums text-gray-900 dark:text-white">{{ $resumo['por_fazer'] }}</div>
        </div>
        <div class="px-4 py-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">Em atraso</div>
            <div @class([
                'mt-0.5 text-lg font-semibold tabular-nums',
                'text-rose-600 dark:text-rose-400' => $resumo['atrasados'] > 0,
                'text-gray-400' => $resumo['atrasados'] === 0,
            ])>{{ $resumo['atrasados'] }}</div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-base font-semibold capitalize">{{ $mesLabel }}</h2>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-gray-500 dark:text-gray-400">
            @foreach ($cores as $c)
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full {{ $c[1] }}"></span>{{ $c[0] }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- ===================== GRELHA (ecrã grande) ===================== --}}
    <div class="hidden overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 md:block dark:bg-gray-900 dark:ring-white/10">
        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-800">
            @foreach (['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $i => $d)
                <div @class([
                    'py-2 text-center text-xs font-medium',
                    'text-gray-500 dark:text-gray-400' => $i < 5,
                    'text-gray-400 dark:text-gray-500' => $i >= 5,
                ])>{{ $d }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-800">
            @foreach ($semanas as $semana)
                @foreach ($semana as $i => $celula)
                    @php
                        $eventos = $celula['eventos'];
                        $visiveis = array_slice($eventos, 0, $maxPorDia);
                        $extra = count($eventos) - count($visiveis);
                        $fds = $i >= 5;
                    @endphp

                    <div
                        wire:click="abrirDia('{{ $celula['data']->toDateString() }}')"
                        @class([
                            'min-h-[7.5rem] cursor-pointer p-1.5 transition-colors',
                            'bg-white dark:bg-gray-900'            => $celula['noMes'] && ! $fds,
                            'bg-gray-50 dark:bg-gray-900/40'       => $celula['noMes'] && $fds,
                            'bg-gray-50/60 dark:bg-gray-900/30'    => ! $celula['noMes'],
                            'ring-1 ring-inset ring-primary-500'   => $this->diaAberto === $celula['data']->toDateString(),
                        ])
                    >
                        <div class="mb-1 flex justify-end">
                            @if ($celula['hoje'])
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-600 text-[11px] font-bold text-white">{{ $celula['data']->day }}</span>
                            @else
                                <span @class([
                                    'text-xs',
                                    'text-gray-500 dark:text-gray-400' => $celula['noMes'],
                                    'text-gray-300 dark:text-gray-600' => ! $celula['noMes'],
                                ])>{{ $celula['data']->day }}</span>
                            @endif
                        </div>

                        <div class="space-y-0.5">
                            @foreach ($visiveis as $ev)
                                @php $cor = $cores[$ev['tipo']] ?? [null, 'bg-gray-400', 'text-gray-600']; @endphp
                                <div @class(['flex items-center gap-1.5 px-1 text-[11px] leading-tight', 'line-through opacity-45' => $ev['concluido']])>
                                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $cor[1] }}"></span>
                                    <span class="min-w-0 flex-1 truncate {{ $cor[2] }}">{{ $ev['titulo'] }}</span>
                                    @if ($ev['valor'])
                                        <span class="shrink-0 tabular-nums text-gray-500 dark:text-gray-400">{{ $euros($ev['valor']) }}</span>
                                    @endif
                                    @if ($ev['atrasado'])
                                        <span class="shrink-0 font-bold text-rose-600 dark:text-rose-400">!</span>
                                    @endif
                                </div>
                            @endforeach

                            @if ($extra > 0)
                                <div class="px-1 text-[11px] font-medium text-gray-400">+{{ $extra }} mais</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- ===================== AGENDA (telemóvel) ===================== --}}
    <div class="space-y-3 md:hidden">
        @foreach ($semanas as $semana)
            @foreach ($semana as $celula)
                @if ($celula['noMes'] && count($celula['eventos']))
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div @class([
                            'border-b px-4 py-2 text-xs font-semibold uppercase tracking-wide',
                            'border-gray-100 bg-primary-50 text-primary-700 dark:border-gray-800 dark:bg-primary-500/10 dark:text-primary-300' => $celula['hoje'],
                            'border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400' => ! $celula['hoje'],
                        ])>
                            {{ $celula['data']->locale('pt_PT')->isoFormat('dddd, D [de] MMMM') }}{{ $celula['hoje'] ? ' · hoje' : '' }}
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($celula['eventos'] as $ev)
                                @include('filament.pages.partials.calendario-linha', ['ev' => $ev, 'cores' => $cores, 'euros' => $euros])
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>

    {{-- ===================== PAINEL DO DIA ===================== --}}
    @if ($this->diaAberto)
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                <h2 class="text-sm font-semibold capitalize">
                    {{ \Carbon\CarbonImmutable::parse($this->diaAberto)->locale('pt_PT')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </h2>
                <button type="button" wire:click="fecharDia" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800">&#10005;</button>
            </div>

            @if (count($doDia))
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($doDia as $ev)
                        @include('filament.pages.partials.calendario-linha', ['ev' => $ev, 'cores' => $cores, 'euros' => $euros])
                    @endforeach
                </div>
            @else
                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">Nada marcado para este dia.</div>
            @endif
        </div>
    @endif

</x-filament-panels::page>
