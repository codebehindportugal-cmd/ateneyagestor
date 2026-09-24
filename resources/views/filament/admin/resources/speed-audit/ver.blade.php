{{-- Resultado de uma auditoria de velocidade: primeiro a máquina, depois um
     bloco por site. Em cada bloco, o que está por corrigir vem primeiro.
     Enquanto a auditoria corre na fila a página refresca-se sozinha. --}}
@php
    $ordem = ['falha' => 0, 'aviso' => 1, 'info' => 2, 'ok' => 3];
    $cores = [
        'falha' => ['rgb(190 18 60)', 'Por corrigir'],
        'aviso' => ['rgb(180 83 9)', 'Aviso'],
        'info'  => ['rgb(100 116 139)', 'Informação'],
        'ok'    => ['rgb(4 120 87)', 'Bem'],
    ];
    $grupos = collect($record->porGrupo())->map(fn ($linhas) => collect($linhas)
        ->sortBy(fn ($r) => ($ordem[$r['estado']] ?? 9) . '-' . ($r['severidade'] === 'critica' ? '0' : '1'))
        ->values());
@endphp

<x-filament-panels::page>
    <div @if ($record->estado === 'pendente' || $record->temCorreccoesACorrer()) wire:poll.5s="refrescar" @endif>

    <x-filament::section>
        <x-slot name="heading">{{ $record->server?->name }} — {{ $record->server?->host }}</x-slot>
        <x-slot name="description">
            @if ($record->estado === 'pendente')
                A analisar em segundo plano desde {{ $record->comecou_em?->format('H:i') }}… (esta página actualiza sozinha)
                @if ($record->comecou_em && $record->comecou_em->lt(now()->subMinutes(15)))
                    <br><span style="color: rgb(190 18 60);">Há mais de 15 minutos sem resultado: o worker da fila (laravel-queue) está a correr no servidor? Em alternativa: php artisan velocidade:auditar {{ $record->server_id }}</span>
                @endif
            @else
                {{ $record->comecou_em?->format('d/m/Y H:i') }} ·
                {{ $record->falhas }} por corrigir, {{ $record->avisos }} avisos, de {{ $record->total }} verificações
                @if ($record->server?->hasPlesk())
                    · <em>máquina com Plesk: as verificações de Apache/PHP da máquina ficam de fora (são do Plesk)</em>
                @endif
            @endif
        </x-slot>

        @if ($record->erro)
            <p style="color: rgb(190 18 60);">{{ $record->erro }}</p>
        @endif

        @if ($record->estado !== 'pendente' && $grupos->isNotEmpty())
            <p class="text-sm text-gray-600">
                Ordem que dá mais resultado: na <strong>Máquina</strong> o OPcache e a compressão; depois, em cada site, a
                <strong>cache de página</strong>. O "Tempo de resposta na própria máquina" de cada site mostra o antes e o depois
                — carrega em "Analisar outra vez" depois de corrigir.
            </p>
        @endif
    </x-filament::section>

    @foreach ($grupos as $grupo => $linhas)
        @php
            $ttfb = $linhas->first(fn ($r) => str_ends_with($r['chave'], '_ttfb'));
            $porCorrigir = $linhas->where('estado', 'falha')->count();
        @endphp

        <x-filament::section collapsible :collapsed="$porCorrigir === 0 && $grupo !== 'Máquina'" style="margin-top: 1rem;">
            <x-slot name="heading">
                {{ $grupo === 'Máquina' ? 'Máquina' : $grupo }}
                @if ($porCorrigir > 0)
                    <span class="text-sm" style="color: rgb(190 18 60); font-weight: 500;">· {{ $porCorrigir }} por corrigir</span>
                @endif
            </x-slot>
            @if ($ttfb)
                <x-slot name="description">{{ \Illuminate\Support\Str::before($ttfb['detalhe'], "\n") }}</x-slot>
            @endif

            <div style="display: grid; gap: .75rem;">
                @foreach ($linhas as $r)
                    @php [$cor, $etiqueta] = $cores[$r['estado']] ?? $cores['info']; @endphp

                    <div style="border: 1px solid rgb(226 232 240); border-left: 4px solid {{ $cor }}; border-radius: .5rem; padding: .85rem 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                            <div style="min-width: 16rem; flex: 1;">
                                <div style="display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;">
                                    <strong>{{ $r['label'] }}</strong>
                                    <span class="text-sm" style="color: {{ $cor }};">{{ $etiqueta }}</span>
                                </div>

                                <p class="text-sm text-gray-600" style="margin-top: .25rem;">{{ $r['porque'] }}</p>

                                @if (filled($r['detalhe']) && $r['estado'] !== 'ok')
                                    <pre style="white-space: pre-wrap; word-break: break-all; font-size: .75rem; background: rgb(248 250 252); padding: .5rem .6rem; border-radius: .375rem; margin-top: .5rem; max-height: 12rem; overflow: auto;">{{ $r['detalhe'] }}</pre>
                                @elseif (filled($r['detalhe']))
                                    <p class="text-sm text-gray-500" style="margin-top: .35rem;">{{ \Illuminate\Support\Str::limit($r['detalhe'], 160) }}</p>
                                @endif

                                @if (filled($r['saida'] ?? null))
                                    <details style="margin-top: .5rem;">
                                        <summary class="text-sm text-gray-600" style="cursor: pointer;">Saída da última correcção ({{ $r['corrigido_em'] ?? '' }}{{ isset($r['saida_codigo']) ? ', código '.$r['saida_codigo'] : '' }})</summary>
                                        <pre style="white-space: pre-wrap; word-break: break-all; font-size: .72rem; background: rgb(248 250 252); padding: .5rem .6rem; border-radius: .375rem; margin-top: .35rem; max-height: 20rem; overflow: auto;">{{ $r['saida'] }}</pre>
                                    </details>
                                @endif
                            </div>

                            <div>
                                @if (! empty($r['a_correr']))
                                    <span class="text-sm" style="color: rgb(180 83 9);">A corrigir…</span>
                                @elseif (filled($r['correcao'] ?? null) && $r['estado'] !== 'ok')
                                    {{ ($this->corrigirAction)(['chave' => $r['chave']]) }}
                                @elseif (blank($r['correcao'] ?? null) && $r['estado'] === 'falha')
                                    <span class="text-sm text-gray-500">à mão</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endforeach

    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
