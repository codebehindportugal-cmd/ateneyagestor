{{-- O que correu ao montar o site, passo a passo, e o log completo em baixo. --}}
@php
    $cores = ['ok' => 'rgb(4 120 87)', 'aviso' => 'rgb(180 83 9)', 'erro' => 'rgb(190 18 60)'];
@endphp

<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">{{ $record->dominio }}</x-slot>
        <x-slot name="description">
            {{ $record->server?->name }} ({{ $record->server?->host }}) ·
            {{ $record->estadoLabel() }}
            @if ($record->comecou_em) · {{ $record->comecou_em->format('d/m/Y H:i') }} @endif
            @if ($record->client) · {{ $record->client->name }} @endif
        </x-slot>

        @if ($record->erro)
            <p style="color: rgb(190 18 60); margin-bottom: 1rem;">{{ $record->erro }}</p>
        @endif

        <div style="display: grid; gap: .5rem;">
            @forelse ($record->passos ?? [] as $passo)
                @php $cor = $cores[$passo['estado']] ?? 'rgb(100 116 139)'; @endphp

                <details style="border: 1px solid rgb(226 232 240); border-left: 4px solid {{ $cor }}; border-radius: .5rem; padding: .6rem .85rem;">
                    <summary style="cursor: pointer;">
                        <strong>{{ $passo['label'] }}</strong>
                        <span class="text-sm" style="color: {{ $cor }};">— {{ $passo['estado'] }}</span>
                    </summary>
                    <pre style="white-space: pre-wrap; word-break: break-all; font-size: .75rem; background: rgb(248 250 252); padding: .6rem; border-radius: .375rem; margin-top: .5rem; max-height: 18rem; overflow: auto;">{{ $passo['saida'] ?: '(sem saída)' }}</pre>
                </details>
            @empty
                <p class="text-sm text-gray-500">Sem passos registados.</p>
            @endforelse
        </div>

        @if ($record->estado === 'concluido')
            <div style="margin-top: 1.25rem; display: flex; gap: .5rem; flex-wrap: wrap;">
                <x-filament::button tag="a" href="https://{{ $record->dominio }}" target="_blank" rel="noopener" color="gray">
                    Abrir o site
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Admin\Resources\CredentialResource::getUrl() }}"
                    color="gray"
                    icon="heroicon-m-key"
                >
                    Ver as senhas no cofre
                </x-filament::button>

                @if ($record->site)
                    <x-filament::button
                        tag="a"
                        href="{{ \App\Filament\Admin\Resources\SiteResource::getUrl('edit', ['record' => $record->site]) }}"
                        color="gray"
                    >
                        Ficha do site
                    </x-filament::button>
                @endif
            </div>
        @endif
    </x-filament::section>

    @if (filled($record->log))
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">Log completo</x-slot>

            <pre style="white-space: pre-wrap; word-break: break-all; font-size: .75rem; background: rgb(15 23 42); color: rgb(226 232 240); padding: .85rem; border-radius: .5rem; max-height: 30rem; overflow: auto;">{{ $record->log }}</pre>
        </x-filament::section>
    @endif

</x-filament-panels::page>
