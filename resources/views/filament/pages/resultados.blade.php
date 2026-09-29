@php
    $eur = fn (int $c) => number_format($c / 100, 2, ',', ' ') . ' €';
    $cor = fn (int $c) => $c < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-gray-100';
    $t = $r['total'];
    $maxCat = max(1, ...array_map('abs', array_values($r['categorias']) ?: [1]));
@endphp

<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Filtros --}}
        <div class="flex flex-wrap items-end gap-4">
            <label class="text-sm">
                <span class="block mb-1 font-medium text-gray-700 dark:text-gray-300">Ano</span>
                <select wire:model.live="ano" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                    @foreach ($anos as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">
                <span class="block mb-1 font-medium text-gray-700 dark:text-gray-300">Marca</span>
                <select wire:model.live="marca" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm">
                    <option value="todas">Todas</option>
                    @foreach ($marcas as $id => $nome)
                        <option value="{{ $id }}">{{ $nome }}</option>
                    @endforeach
                    <option value="sem">Sem marca</option>
                </select>
            </label>
            <p class="text-xs text-gray-500 dark:text-gray-400 ml-auto">
                @if (! $moloniLigado)
                    <span class="text-warning-600 dark:text-warning-400">Moloni não configurado — as vendas estão a zero.</span>
                    Ver <code>MOLONI_*</code> no .env.
                @elseif ($ultimaSync)
                    Moloni sincronizado {{ $ultimaSync->diffForHumans() }} ({{ $ultimaSync->format('d/m H:i') }}).
                @else
                    Moloni ainda não sincronizado.
                @endif
            </p>
        </div>

        @if (count($seriesSemMarca) > 0)
            <div class="rounded-xl border border-warning-200 bg-warning-50 dark:border-warning-700 dark:bg-warning-950 p-4 text-sm text-warning-800 dark:text-warning-200">
                Há séries do Moloni sem marca: <strong>{{ implode(', ', $seriesSemMarca) }}</strong>.
                As vendas delas contam em "Sem marca" até lhes dares uma em <strong>Séries e marcas</strong>.
            </div>
        @endif

        {{-- Resumo --}}
        <div style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
            @foreach ([
                ['Vendas (s/ IVA)', $t['vendas'], $t['n_vendas'] . ' documentos no Moloni'],
                ['Despesas (s/ IVA)', $t['despesas'], $t['n_despesas'] . ' documentos'],
                ['Resultado', $t['resultado'], 'vendas − despesas'],
                ['IVA a entregar', $t['iva_a_entregar'], 'liquidado ' . $eur($t['iva_liquidado']) . ' − dedutível ' . $eur($t['iva_dedutivel'])],
            ] as [$titulo, $valor, $nota])
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $titulo }}</p>
                    <p style="font-variant-numeric:tabular-nums" class="mt-1 text-2xl font-semibold {{ $cor($valor) }}">{{ $eur($valor) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $nota }}</p>
                </div>
            @endforeach
        </div>

        {{-- Mes a mes --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <table class="w-full text-sm" style="font-variant-numeric:tabular-nums">
                <thead class="bg-gray-50 dark:bg-gray-900 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Mês</th>
                        <th class="px-4 py-3 text-right">Vendas</th>
                        <th class="px-4 py-3 text-right">Despesas</th>
                        <th class="px-4 py-3 text-right">Resultado</th>
                        <th class="px-4 py-3 text-right">IVA liquidado</th>
                        <th class="px-4 py-3 text-right">IVA dedutível</th>
                        <th class="px-4 py-3 text-right">IVA a entregar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($r['meses'] as $m => $l)
                        @php $vazio = $l['n_vendas'] === 0 && $l['n_despesas'] === 0; @endphp
                        <tr class="{{ $vazio ? 'text-gray-400 dark:text-gray-500' : '' }}">
                            <td class="px-4 py-2">{{ $meses[$m - 1] }}</td>
                            <td class="px-4 py-2 text-right">{{ $eur($l['vendas']) }}</td>
                            <td class="px-4 py-2 text-right">{{ $eur($l['despesas']) }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ $vazio ? '' : $cor($l['resultado']) }}">{{ $eur($l['resultado']) }}</td>
                            <td class="px-4 py-2 text-right">{{ $eur($l['iva_liquidado']) }}</td>
                            <td class="px-4 py-2 text-right">{{ $eur($l['iva_dedutivel']) }}</td>
                            <td class="px-4 py-2 text-right">{{ $eur($l['iva_a_entregar']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-gray-200 dark:border-gray-600 font-semibold">
                    <tr>
                        <td class="px-4 py-3">Total {{ $ano }}</td>
                        <td class="px-4 py-3 text-right">{{ $eur($t['vendas']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $eur($t['despesas']) }}</td>
                        <td class="px-4 py-3 text-right {{ $cor($t['resultado']) }}">{{ $eur($t['resultado']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $eur($t['iva_liquidado']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $eur($t['iva_dedutivel']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $eur($t['iva_a_entregar']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Despesas por categoria --}}
        @if (count($r['categorias']) > 0)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
                <p class="mb-3 font-semibold text-gray-700 dark:text-gray-200 text-sm">Despesas por categoria ({{ $ano }}, sem IVA)</p>
                <table class="w-full text-sm" style="font-variant-numeric:tabular-nums">
                    @foreach ($r['categorias'] as $cat => $valor)
                        <tr>
                            <td class="py-1 pr-3 text-gray-600 dark:text-gray-300" style="width:30%">{{ $categorias[$cat] ?? ucfirst($cat) }}</td>
                            <td class="py-1 pr-3">
                                <div style="height:.5rem;border-radius:.25rem;background:rgba(148,163,184,.25)">
                                    <div style="height:.5rem;border-radius:.25rem;background:rgb(99,102,241);width: {{ max(1, round(abs($valor) / $maxCat * 100)) }}%"></div>
                                </div>
                            </td>
                            <td class="py-1 text-right" style="width:9rem">{{ $eur($valor) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        <p class="text-xs text-gray-500 dark:text-gray-400">
            Valores sem IVA. Vendas: faturas, faturas-recibo, faturas simplificadas e notas de débito fechadas no Moloni, menos notas de crédito — os recibos não contam, porque pagam faturas já contadas.
            Despesas: documentos de contabilidade que não estão "Por rever", menos notas de crédito, sem recibos.
            É uma vista de gestão: não inclui salários, amortizações ou despesas sem fatura, e o IVA dedutível assume que todo o IVA das despesas se deduz.
        </p>
    </div>
</x-filament-panels::page>
