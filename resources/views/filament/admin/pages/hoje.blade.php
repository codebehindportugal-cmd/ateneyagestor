{{-- "Hoje" — ver App\Filament\Admin\Pages\Hoje. Estilos hj-* em filament/tema-ateneya. --}}
<x-filament-panels::page class="fi-dashboard-page">
    <div class="hj">
        <div class="hj-topo">
            <div>
                <div class="hj-data">{{ $dataHoje }}</div>
                <h1 class="hj-ola">{{ $saudacao }}</h1>
            </div>
        </div>

        <div class="hj-kpis">
            @foreach ($kpis as $k)
                <a class="hj-kpi" href="{{ $k['url'] }}">
                    <span class="hj-kpi-rotulo">{{ $k['rotulo'] }}</span>
                    <span class="hj-kpi-num {{ $k['tom'] }}">{{ $k['num'] }}</span>
                    <span class="hj-kpi-desc">{{ $k['desc'] }}</span>
                </a>
            @endforeach
        </div>

        @if ($admin)
            <div class="hj-duas">
                <section class="hj-caixa">
                    <div class="hj-caixa-topo">
                        <h2>Precisa de ti</h2>
                        <span class="hj-nota">o mais grave primeiro</span>
                    </div>

                    @forelse ($atencao as $a)
                        <div class="hj-item">
                            <span class="hj-ponto hj-ponto-{{ $a['tom'] }}" aria-hidden="true"></span>
                            <div class="hj-item-corpo">
                                <div class="hj-item-titulo">
                                    <strong>{{ $a['titulo'] }}</strong>
                                    <span class="hj-onde">{{ $a['onde'] }}</span>
                                </div>
                                @if (filled($a['texto']))
                                    <span class="hj-texto">{{ $a['texto'] }}</span>
                                @endif
                            </div>
                            <a class="hj-acao" href="{{ $a['url'] }}">{{ $a['acao'] }}</a>
                        </div>
                    @empty
                        <div class="hj-vazio">Nada à espera de ti. Os sites respondem, os backups correram e não há nada por corrigir.</div>
                    @endforelse
                </section>

                <section class="hj-caixa">
                    <div class="hj-caixa-topo">
                        <h2>Servidores</h2>
                        <a class="hj-link" href="{{ $urlAuditorias }}">Ver auditorias</a>
                    </div>

                    @forelse ($servidores as $s)
                        @if ($s['url'])
                            <a class="hj-srv" href="{{ $s['url'] }}">
                        @else
                            <div class="hj-srv">
                        @endif
                            <span style="min-width: 0">
                                <span class="hj-srv-nome">{{ $s['nome'] }}</span>
                                <span class="hj-onde">{{ $s['host'] }} · {{ $s['painel'] }}</span>
                            </span>
                            <span class="hj-pill hj-pill-{{ $s['tom'] }}">{{ $s['estado'] }}</span>
                        @if ($s['url'])
                            </a>
                        @else
                            </div>
                        @endif
                    @empty
                        <div class="hj-vazio">Ainda não há servidores activos.</div>
                    @endforelse
                </section>
            </div>
        @endif
    </div>

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="
            [
                ...(property_exists($this, 'filters') ? ['filters' => $this->filters] : []),
                ...$this->getWidgetData(),
            ]
        "
        :widgets="$this->getVisibleWidgets()"
    />
</x-filament-panels::page>
