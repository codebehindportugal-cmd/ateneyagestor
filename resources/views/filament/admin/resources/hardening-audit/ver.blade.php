{{-- Resultado de uma auditoria de endurecimento, verificação a verificação.
     As que têm correcção automática trazem botão; as outras trazem só o que
     se encontrou, para se decidir à mão. --}}
@php
    $ordem = ['falha' => 0, 'aviso' => 1, 'info' => 2, 'ok' => 3];
    $resultados = collect($record->resultados ?? [])
        ->sortBy(fn ($r) => ($ordem[$r['estado']] ?? 9).'-'.($r['severidade'] === 'critica' ? '0' : '1'))
        ->values();

    $cores = [
        'falha' => ['rgb(190 18 60)', 'Por corrigir'],
        'aviso' => ['rgb(180 83 9)', 'Aviso'],
        'info'  => ['rgb(100 116 139)', 'Informação'],
        'ok'    => ['rgb(4 120 87)', 'Bem'],
    ];
@endphp

<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">{{ $record->server?->name }} — {{ $record->server?->host }}</x-slot>
        <x-slot name="description">
            {{ $record->comecou_em?->format('d/m/Y H:i') }} ·
            {{ $record->falhas }} por corrigir, {{ $record->avisos }} avisos, de {{ $record->total }} verificações
            @if ($record->server?->hasPlesk())
                · <em>máquina com Plesk: as verificações de Apache e firewall ficam de fora, porque a configuração é do painel</em>
            @endif
        </x-slot>

        @if ($record->erro)
            <p style="color: rgb(190 18 60);">{{ $record->erro }}</p>
        @endif

        <div style="display: grid; gap: .75rem;">
            @foreach ($resultados as $r)
                @php [$cor, $etiqueta] = $cores[$r['estado']] ?? $cores['info']; @endphp

                <div style="border: 1px solid rgb(226 232 240); border-left: 4px solid {{ $cor }}; border-radius: .5rem; padding: .85rem 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                        <div style="min-width: 16rem; flex: 1;">
                            <div style="display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;">
                                <strong>{{ $r['label'] }}</strong>
                                <span class="text-sm" style="color: {{ $cor }};">{{ $etiqueta }}</span>
                                @if ($r['severidade'] === 'critica' && $r['estado'] !== 'ok')
                                    <span class="text-sm" style="color: rgb(190 18 60);">· crítica</span>
                                @endif
                            </div>

                            <p class="text-sm text-gray-600" style="margin-top: .25rem;">{{ $r['porque'] }}</p>

                            @if (filled($r['detalhe']) && $r['estado'] !== 'ok')
                                <pre style="white-space: pre-wrap; word-break: break-all; font-size: .75rem; background: rgb(248 250 252); padding: .5rem .6rem; border-radius: .375rem; margin-top: .5rem; max-height: 12rem; overflow: auto;">{{ $r['detalhe'] }}</pre>
                            @elseif (filled($r['detalhe']))
                                <p class="text-sm text-gray-500" style="margin-top: .35rem;">{{ \Illuminate\Support\Str::limit($r['detalhe'], 120) }}</p>
                            @endif
                        </div>

                        <div>
                            @if (filled($r['correcao'] ?? null) && $r['estado'] !== 'ok')
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

    <x-filament-actions::modals />

</x-filament-panels::page>
