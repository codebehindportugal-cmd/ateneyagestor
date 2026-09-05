<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Documentos para Contabilidade · {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .tnum { font-variant-numeric: tabular-nums; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 antialiased">

    {{-- Top bar --}}
    <header class="bg-slate-900 text-white no-print">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/ateneya-logo.jpg') }}" alt="Ateneya" class="h-10 w-10 rounded-lg object-cover ring-1 ring-white/20">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Ateneya · Portal de Contabilidade</p>
                    <h1 class="text-lg font-semibold leading-tight">Documentos para Contabilidade</h1>
                </div>
            </div>
            <div class="sm:text-right">
                <p class="text-xl font-semibold tnum leading-tight">{{ number_format($grandTotal['amount'], 2, ',', '.') }} €</p>
                <p class="text-xs text-slate-400">{{ $grandTotal['count'] }} documento(s) · gerado a {{ now()->format('d/m/Y \à\s H:i') }}</p>
                @if(($porImportar['count'] ?? 0) > 0)
                    <button type="button" id="filtro-por-importar"
                            class="mt-2 inline-flex items-center gap-2 rounded-full bg-amber-400/15 px-3 py-1 text-xs font-semibold text-amber-300 ring-1 ring-amber-400/30 hover:bg-amber-400/25 transition-colors">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        <span>{{ $porImportar['count'] }} por importar · {{ number_format($porImportar['amount'], 2, ',', '.') }} €</span>
                        <span class="text-amber-200/70" data-estado>mostrar só estes</span>
                    </button>
                @else
                    <p class="mt-2 text-xs text-emerald-400">Está tudo importado.</p>
                @endif
            </div>
        </div>
    </header>
    <div class="h-1 bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500 no-print"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-12">

        @if(($supplierInvoices ?? collect())->isNotEmpty())
            <section>
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                    <h2 class="text-base font-semibold text-slate-800">Faturas de fornecedores confirmadas</h2>
                    <span class="ml-auto inline-flex items-center gap-1.5 text-sm text-slate-500 bg-white border border-slate-200 rounded-full px-3.5 py-1 shadow-sm whitespace-nowrap">
                        {{ $supplierGrandTotal['count'] }} fatura(s) ·
                        <span class="font-semibold text-slate-800 tnum">{{ number_format($supplierGrandTotal['amount'], 2, ',', '.') }} €</span>
                    </span>
                </div>

                <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-[11px] text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-200">
                                    <th class="px-4 py-2.5 text-left font-semibold">Marca</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Fornecedor</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Nº Documento</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Finalidade</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Categoria</th>
                                    <th class="px-4 py-2.5 text-left font-semibold">Data</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">IVA</th>
                                    <th class="px-4 py-2.5 text-right font-semibold">Total</th>
                                    <th class="px-4 py-2.5 text-center font-semibold no-print">Ficheiros</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($supplierInvoices as $invoice)
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-4 py-3 text-slate-700">{{ $invoice->brand?->full_name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700">
                                            <span class="font-medium text-slate-800">{{ $invoice->supplier_name ?? '—' }}</span>
                                            @if($invoice->supplier_tax_number)
                                                <p class="text-xs text-slate-400 tnum">NIF {{ $invoice->supplier_tax_number }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-500 tnum text-xs whitespace-nowrap">{{ $invoice->invoice_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-slate-700 min-w-48">
                                            <div class="font-medium">{{ $invoice->purpose }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200">
                                                {{ $invoice->category_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap tnum">{{ $invoice->invoice_date?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right tnum text-slate-500 whitespace-nowrap">{{ number_format((float) $invoice->tax_total, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-right tnum font-semibold text-slate-900 whitespace-nowrap">{{ number_format((float) $invoice->total, 2, ',', '.') }} €</td>
                                        <td class="px-4 py-3 text-center no-print whitespace-nowrap">
                                            <a href="{{ route('contabilista.supplier-invoices.download', ['token' => $token, 'supplierInvoice' => $invoice]) }}"
                                               class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                Download
                                            </a>
                                            @foreach(($invoice->image_paths ?? []) as $index => $path)
                                                <a href="{{ route('contabilista.supplier-invoices.download', ['token' => $token, 'supplierInvoice' => $invoice, 'image' => $index]) }}"
                                                   class="ml-2 text-xs text-slate-500 hover:text-slate-700">Foto {{ $index + 1 }}</a>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @forelse($anos as $ano => $dadosAno)
            <section>
                {{-- Cabeçalho do ano --}}
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <h2 class="text-xl font-semibold text-slate-800 tnum">{{ $ano }}</h2>
                    @if($dadosAno['total']['porImportar'] > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            {{ $dadosAno['total']['porImportar'] }} por importar
                        </span>
                    @endif
                    <span class="ml-auto inline-flex items-center gap-1.5 text-sm text-slate-500 bg-white border border-slate-200 rounded-full px-3.5 py-1 shadow-sm whitespace-nowrap">
                        {{ $dadosAno['total']['count'] }} doc(s) ·
                        <span class="font-semibold text-slate-800 tnum">{{ number_format($dadosAno['total']['amount'], 2, ',', '.') }} €</span>
                    </span>
                </div>

                <div class="space-y-6">
                    @foreach($dadosAno['meses'] as $mes => $dadosMes)
                        @php $nomeDoMes = \App\Models\AccountingDocument::monthName($mes); @endphp

                        <div class="cartao-mes bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                            {{-- Cabeçalho do mês --}}
                            <div class="flex flex-wrap items-center gap-3 px-5 py-3 bg-slate-50 border-b border-slate-200">
                                <h3 class="font-semibold text-slate-800">
                                    {{ $nomeDoMes }} <span class="font-normal text-slate-400 tnum">{{ $ano }}</span>
                                </h3>

                                @if($dadosMes['total']['porImportar'] > 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                                        {{ $dadosMes['total']['porImportar'] }} por importar
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        Mês fechado
                                    </span>
                                @endif

                                <span class="ml-auto text-sm text-slate-500 whitespace-nowrap">
                                    {{ $dadosMes['total']['count'] }} doc(s) ·
                                    <span class="font-semibold text-slate-800 tnum">{{ number_format($dadosMes['total']['amount'], 2, ',', '.') }} €</span>
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                            <th class="px-4 py-2.5 text-left font-semibold">Tipo</th>
                                            <th class="px-4 py-2.5 text-left font-semibold">Nº Documento</th>
                                            <th class="px-4 py-2.5 text-left font-semibold">Fornecedor</th>
                                            <th class="px-4 py-2.5 text-left font-semibold">Finalidade</th>
                                            <th class="px-4 py-2.5 text-left font-semibold">Data</th>
                                            <th class="px-4 py-2.5 text-right font-semibold">Total s/ IVA</th>
                                            <th class="px-4 py-2.5 text-right font-semibold">IVA</th>
                                            <th class="px-4 py-2.5 text-right font-semibold">Total</th>
                                            <th class="px-4 py-2.5 text-center font-semibold">Estado</th>
                                            <th class="px-4 py-2.5 text-center font-semibold">Importada</th>
                                            <th class="px-4 py-2.5 text-center font-semibold no-print">Ficheiro</th>
                                        </tr>
                                    </thead>

                                    @foreach($dadosMes['marcas'] as $chaveMarca => $dadosMarca)
                                        @php
                                            $marca      = $dadosMarca['brand'];
                                            $nomeMarca  = $marca?->name ?? 'Sem marca atribuída';
                                            $corMarca   = $marca?->color ?? '#94a3b8';
                                            $marcaMae   = $marca?->parent?->name;
                                            $grupo      = $ano.'-'.$mes.'-'.$chaveMarca;
                                        @endphp

                                        <tbody class="divide-y divide-slate-100 grupo-marca" data-grupo="{{ $grupo }}">
                                            {{-- Sub-cabeçalho da marca dentro do mês --}}
                                            <tr class="bg-slate-50/70 border-t border-slate-200">
                                                <td colspan="7" class="px-4 py-2">
                                                    <span class="inline-flex items-center gap-2">
                                                        <span class="inline-block w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $corMarca }}"></span>
                                                        <span class="text-xs font-semibold text-slate-700 uppercase tracking-wide">{{ $nomeMarca }}</span>
                                                        @if($marcaMae)
                                                            <span class="text-[11px] text-slate-400">{{ $marcaMae }}</span>
                                                        @endif
                                                        <span class="text-[11px] text-slate-400">{{ $dadosMarca['total']['count'] }} doc(s)</span>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-right tnum text-sm font-semibold text-slate-700 whitespace-nowrap">
                                                    {{ number_format($dadosMarca['total']['amount'], 2, ',', '.') }} €
                                                </td>
                                                <td colspan="3"></td>
                                            </tr>

                                            @foreach($dadosMarca['docs'] as $doc)
                                                @php
                                                    $totalComIva = $doc->amount;
                                                    $iva         = $doc->iva;
                                                    $totalSemIva = $totalComIva - $iva;
                                                @endphp
                                                <tr class="hover:bg-slate-50/70 transition-colors linha-documento"
                                                    data-grupo="{{ $grupo }}"
                                                    data-importada="{{ $doc->importado_contabilidade ? '1' : '0' }}">
                                                    <td class="px-4 py-3">
                                                        @php $tipos = \App\Models\AccountingDocument::tipos(); @endphp
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200">
                                                            {{ $tipos[$doc->tipo ?? 'fatura'] ?? ucfirst($doc->tipo ?? 'fatura') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-500 tnum text-xs whitespace-nowrap">
                                                        {{ $doc->invoice_number ?? '—' }}
                                                        @if($doc->atcud)
                                                            <p class="text-slate-300 mt-0.5">{{ $doc->atcud }}</p>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700 text-sm">
                                                        <span class="font-medium text-slate-800">{{ $doc->fornecedor ?? $doc->title }}</span>
                                                        @if($doc->supplier_nif)
                                                            <p class="text-xs text-slate-400 tnum">NIF {{ $doc->supplier_nif }}</p>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-700 text-sm min-w-48">
                                                        <div class="font-medium">{{ \App\Models\AccountingDocument::finalidadeLabel($doc->title) }}</div>
                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                {{ $doc->category_label }}
                                                            </span>
                                                            @if($doc->origem === 'email')
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-600 ring-1 ring-inset ring-indigo-200"
                                                                      title="Chegou por email: {{ $doc->email_de }}">
                                                                    Email
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-slate-600 whitespace-nowrap text-sm tnum">
                                                        {{ $doc->date->format('d/m/Y') }}
                                                    </td>
                                                    <td class="px-4 py-3 text-right tnum text-slate-700 whitespace-nowrap text-sm">
                                                        {{ number_format($totalSemIva, 2, ',', '.') }} €
                                                    </td>
                                                    <td class="px-4 py-3 text-right tnum text-slate-500 whitespace-nowrap text-sm">
                                                        {{ number_format($iva, 2, ',', '.') }} €
                                                    </td>
                                                    <td class="px-4 py-3 text-right tnum font-semibold text-slate-900 whitespace-nowrap">
                                                        {{ number_format($totalComIva, 2, ',', '.') }} €
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        @php
                                                            $estadoBadges = [
                                                                'pendente' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                                                'aprovado' => 'bg-sky-50 text-sky-700 ring-sky-200',
                                                                'pago'     => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                            ];
                                                            $estadoNames = \App\Models\AccountingDocument::estados();
                                                            $estadoKey   = $doc->estado ?? 'pendente';
                                                            $badge       = $estadoBadges[$estadoKey] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ring-1 ring-inset {{ $badge }}">
                                                            {{ $estadoNames[$estadoKey] ?? ucfirst($estadoKey) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer select-none">
                                                            <input type="checkbox"
                                                                   class="marcar-importada h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                                                   data-url="{{ route('contabilista.marcar-importado', ['token' => $token, 'id' => $doc->id]) }}"
                                                                   @checked($doc->importado_contabilidade)>
                                                            <span class="text-[11px] leading-tight {{ $doc->importado_contabilidade ? 'text-emerald-600' : 'text-amber-600' }}" data-rotulo>
                                                                {{ $doc->importado_contabilidade
                                                                    ? ($doc->importado_em?->format('d/m/Y') ?? 'Importada')
                                                                    : 'Por importar' }}
                                                            </span>
                                                        </label>
                                                    </td>
                                                    <td class="px-4 py-3 text-center no-print">
                                                        <a href="{{ route('contabilista.details', ['token' => $token, 'id' => $doc->id]) }}"
                                                           class="inline-flex items-center gap-1 text-slate-600 hover:text-slate-900 text-xs font-medium">
                                                            Detalhes
                                                        </a>
                                                        @if($doc->file_path)
                                                            <a href="{{ route('contabilista.download', ['token' => $token, 'id' => $doc->id]) }}"
                                                               class="ml-2 inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                                                               target="_blank">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                                </svg>
                                                                Download
                                                            </a>
                                                        @endif
                                                        @if(! empty($doc->image_paths))
                                                            <div class="mt-1 flex flex-col items-center gap-1">
                                                                @foreach(array_values($doc->image_paths) as $index => $imagePath)
                                                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($imagePath) }}"
                                                                       class="text-xs text-slate-500 hover:text-slate-700"
                                                                       target="_blank">
                                                                        Imagem {{ $index + 1 }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        @if(! $doc->file_path && empty($doc->image_paths))
                                                            <span class="text-slate-300 text-xs">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    @endforeach

                                    {{-- Total do mês --}}
                                    <tfoot>
                                        <tr class="bg-slate-100 border-t-2 border-slate-300">
                                            <td colspan="5" class="px-4 py-2.5 text-xs font-semibold text-slate-600 uppercase tracking-wide">
                                                Total de {{ $nomeDoMes }} {{ $ano }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right tnum text-sm text-slate-600 whitespace-nowrap">
                                                {{ number_format($dadosMes['total']['amount'] - $dadosMes['total']['iva'], 2, ',', '.') }} €
                                            </td>
                                            <td class="px-4 py-2.5 text-right tnum text-sm text-slate-600 whitespace-nowrap">
                                                {{ number_format($dadosMes['total']['iva'], 2, ',', '.') }} €
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-bold text-slate-900 tnum whitespace-nowrap">
                                                {{ number_format($dadosMes['total']['amount'], 2, ',', '.') }} €
                                            </td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

        @empty
            <div class="bg-white rounded-xl ring-1 ring-slate-200 p-14 text-center shadow-sm">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-slate-500 font-medium">Ainda não há documentos disponíveis.</p>
                <p class="text-slate-400 text-sm mt-1">Assim que forem carregados documentos, aparecem aqui automaticamente.</p>
            </div>
        @endforelse

        {{-- Footer --}}
        <footer class="border-t border-slate-200 pt-5 pb-6 text-center">
            <p class="text-xs text-slate-400">
                Este acesso é pessoal e intransmissível · {{ config('app.name') }}
            </p>
        </footer>
    </div>

    <script>
        // A caixa de marcar grava sozinha. A pagina e' comprida — recarrega-la
        // por cada documento fazia perder o sitio onde se ia.
        (function () {
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            document.querySelectorAll('.marcar-importada').forEach(function (caixa) {
                caixa.addEventListener('change', async function () {
                    const linha = caixa.closest('tr');
                    const rotulo = caixa.parentElement.querySelector('[data-rotulo]');
                    const queria = caixa.checked;

                    caixa.disabled = true;

                    try {
                        const resposta = await fetch(caixa.dataset.url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ importado: queria }),
                        });

                        if (resposta.status === 419) {
                            // A pagina ficou aberta ate a sessao expirar. Dizer
                            // "erro de ligacao" mandava-o procurar no sitio errado.
                            throw new Error('A pagina esteve aberta demasiado tempo. Recarrega (F5) e marca outra vez.');
                        }

                        if (!resposta.ok) {
                            throw new Error('O servidor respondeu ' + resposta.status + '.');
                        }

                        const dados = await resposta.json();

                        linha?.setAttribute('data-importada', dados.importado ? '1' : '0');
                        aplicarFiltro();

                        if (rotulo) {
                            rotulo.textContent = dados.importado
                                ? (dados.importado_em ?? 'Importada')
                                : 'Por importar';
                            rotulo.className = 'text-[11px] leading-tight '
                                + (dados.importado ? 'text-emerald-600' : 'text-amber-600');
                        }
                    } catch (erro) {
                        // Desfazer: uma marca que nao chegou a gravar e' pior do
                        // que uma por marcar — dava o documento por lancado.
                        caixa.checked = !queria;
                        alert('Nao consegui gravar.\n\n' + (erro?.message ?? erro));
                    } finally {
                        caixa.disabled = false;
                    }
                });
            });

            const filtro = document.getElementById('filtro-por-importar');
            let soPorImportar = false;

            // Esconder so as linhas deixava para tras o cabecalho da marca e o
            // cartao do mes, vazios: um mes todo lancado aparecia na mesma, a
            // dizer que faltava alguma coisa la dentro.
            function aplicarFiltro() {
                document.querySelectorAll('.linha-documento').forEach(function (linha) {
                    const escondida = soPorImportar && linha.dataset.importada === '1';
                    linha.style.display = escondida ? 'none' : '';
                });

                document.querySelectorAll('.grupo-marca').forEach(function (grupo) {
                    const linhas = grupo.querySelectorAll('.linha-documento');
                    const visiveis = Array.from(linhas).filter(function (linha) {
                        return !soPorImportar || linha.dataset.importada !== '1';
                    }).length;

                    grupo.style.display = visiveis === 0 ? 'none' : '';
                });

                document.querySelectorAll('.cartao-mes').forEach(function (cartao) {
                    const linhas = cartao.querySelectorAll('.linha-documento');
                    const visiveis = Array.from(linhas).filter(function (linha) {
                        return !soPorImportar || linha.dataset.importada !== '1';
                    }).length;

                    cartao.style.display = visiveis === 0 ? 'none' : '';
                });

                const estado = filtro?.querySelector('[data-estado]');

                if (estado) {
                    estado.textContent = soPorImportar ? 'mostrar todos' : 'mostrar só estes';
                }
            }

            if (filtro) {
                filtro.addEventListener('click', function () {
                    soPorImportar = !soPorImportar;
                    aplicarFiltro();
                });
            }
        })();
    </script>

</body>
</html>
