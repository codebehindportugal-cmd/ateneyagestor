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
                {{-- Os contadores sao sempre renderizados e escondidos com
                     style inline quando nao se aplicam. Antes so' existiam no
                     HTML quando > 0 e eram Blade puro: marcar documentos nao os
                     fazia descer, e e' justamente o numero que ele olha para
                     saber quanto falta ao fechar o mes. Quem os actualiza agora
                     e' o recalcularContadores(), la' em baixo.
                     Style inline e nao o atributo `hidden` porque as classes de
                     display do Tailwind ganham ao [hidden] da folha do browser. --}}
                <button type="button" id="filtro-por-importar"
                        @if(($porImportar['count'] ?? 0) < 1) style="display:none" @endif
                        class="mt-2 inline-flex items-center gap-2 rounded-full bg-amber-400/15 px-3 py-1 text-xs font-semibold text-amber-300 ring-1 ring-amber-400/30 hover:bg-amber-400/25 transition-colors">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    <span data-contador-topo>{{ $porImportar['count'] }} por importar · {{ number_format($porImportar['amount'], 2, ',', '.') }} €</span>
                    <span class="text-amber-200/70" data-estado>mostrar só estes</span>
                </button>
                <p class="mt-2 text-xs text-emerald-400" data-tudo-importado
                   @if(($porImportar['count'] ?? 0) > 0) style="display:none" @endif>Está tudo importado.</p>
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
            <section data-ano="{{ $ano }}">
                {{-- Cabeçalho do ano --}}
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <h2 class="text-xl font-semibold text-slate-800 tnum">{{ $ano }}</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200"
                          data-contador-ano="{{ $ano }}"
                          @if(($dadosAno['total']['porImportar'] ?? 0) < 1) style="display:none" @endif>
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span data-valor>{{ $dadosAno['total']['porImportar'] }}</span> por importar
                    </span>
                    <span class="ml-auto inline-flex items-center gap-1.5 text-sm text-slate-500 bg-white border border-slate-200 rounded-full px-3.5 py-1 shadow-sm whitespace-nowrap">
                        {{ $dadosAno['total']['count'] }} doc(s) ·
                        <span class="font-semibold text-slate-800 tnum">{{ number_format($dadosAno['total']['amount'], 2, ',', '.') }} €</span>
                    </span>
                </div>

                <div class="space-y-6">
                    @foreach($dadosAno['meses'] as $mes => $dadosMes)
                        @php $nomeDoMes = \App\Models\AccountingDocument::monthName($mes); @endphp

                        <div class="cartao-mes bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden"
                             data-mes-chave="{{ $ano }}-{{ $mes }}">
                            {{-- Cabeçalho do mês --}}
                            <div class="flex flex-wrap items-center gap-3 px-5 py-3 bg-slate-50 border-b border-slate-200">
                                <label class="inline-flex items-center cursor-pointer select-none no-print" title="Escolher todos os documentos deste mês">
                                    <input type="checkbox"
                                           class="selector-mes h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                    <span class="sr-only">Escolher o mês inteiro</span>
                                </label>
                                <h3 class="font-semibold text-slate-800">
                                    {{ $nomeDoMes }} <span class="font-normal text-slate-400 tnum">{{ $ano }}</span>
                                </h3>

                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200"
                                      data-contador-mes="{{ $ano }}-{{ $mes }}"
                                      @if(($dadosMes['total']['porImportar'] ?? 0) < 1) style="display:none" @endif>
                                    <span data-valor>{{ $dadosMes['total']['porImportar'] }}</span> por importar
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200"
                                      data-mes-fechado="{{ $ano }}-{{ $mes }}"
                                      @if(($dadosMes['total']['porImportar'] ?? 0) > 0) style="display:none" @endif>
                                    Mês fechado
                                </span>

                                <span class="ml-auto text-sm text-slate-500 whitespace-nowrap">
                                    {{ $dadosMes['total']['count'] }} doc(s) ·
                                    <span class="font-semibold text-slate-800 tnum">{{ number_format($dadosMes['total']['amount'], 2, ',', '.') }} €</span>
                                </span>

                                {{-- Um link, nao um botao: o mes inteiro nao precisa de seleccao nenhuma. --}}
                                <a href="{{ route('contabilista.zip', ['token' => $token, 'ano' => $ano, 'mes' => $mes]) }}"
                                   class="no-print inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors whitespace-nowrap"
                                   title="Descarrega todos os ficheiros de {{ $nomeDoMes }} de {{ $ano }} num zip">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    ZIP do mês
                                </a>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                            <th class="px-3 py-2.5 text-center font-semibold no-print w-10"><span class="sr-only">Escolher</span></th>
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
                                                <td class="px-3 py-2 text-center no-print">
                                                    <input type="checkbox"
                                                           class="selector-grupo h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                           title="Escolher todos os documentos desta marca">
                                                </td>
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
                                                    data-total="{{ $totalComIva }}"
                                                    data-importada="{{ $doc->importado_contabilidade ? '1' : '0' }}">
                                                    <td class="px-3 py-3 text-center no-print">
                                                        <input type="checkbox"
                                                               class="selector-doc h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                               value="{{ $doc->id }}">
                                                    </td>
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
                                                        @if($doc->anexos->isNotEmpty())
                                                            <div class="mt-1 flex flex-col items-center gap-0.5">
                                                                @foreach($doc->anexos as $anexo)
                                                                    <a href="{{ route('contabilista.anexos.download', ['token' => $token, 'attachment' => $anexo]) }}"
                                                                       class="text-xs text-slate-500 hover:text-slate-700"
                                                                       title="Veio no mesmo email">
                                                                        {{ \Illuminate\Support\Str::limit($anexo->original_name, 22) }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        @if(! $doc->file_path && empty($doc->image_paths) && $doc->anexos->isEmpty())
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
                                            <td colspan="6" class="px-4 py-2.5 text-xs font-semibold text-slate-600 uppercase tracking-wide">
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

    {{-- A barra so' aparece quando ha alguma coisa escolhida: uma barra sempre
         a vista rouba espaco ao fundo da pagina para nao dizer nada. --}}
    <div id="barra-seleccao"
         class="no-print fixed inset-x-0 bottom-0 z-40 hidden border-t border-slate-700 bg-slate-900/95 backdrop-blur text-white shadow-2xl">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold whitespace-nowrap">
                <span id="conta-seleccao" class="tnum">0</span> documento(s) escolhidos
            </span>

            <button type="button" id="limpar-seleccao"
                    class="text-xs text-slate-400 hover:text-white underline underline-offset-2">
                limpar
            </button>

            <div class="ml-auto flex flex-wrap items-center gap-2">
                <button type="button" id="accao-zip"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3.5 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/20 hover:bg-white/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Descarregar ZIP
                </button>

                <button type="button" id="accao-desmarcar"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-3.5 py-2 text-sm font-semibold text-white ring-1 ring-inset ring-white/20 hover:bg-white/20 transition-colors">
                    Desmarcar
                </button>

                <button type="button" id="accao-marcar"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500 px-3.5 py-2 text-sm font-semibold text-white hover:bg-emerald-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Marcar como importadas
                </button>
            </div>
        </div>
    </div>

    {{-- O zip da seleccao vai por POST: um mes com trezentos documentos nao
         cabe num URL, e o browser trata do download sozinho. --}}
    <form id="form-zip" method="POST" action="{{ route('contabilista.zip', ['token' => $token]) }}" class="hidden">
        @csrf
    </form>

    <script>
        // A caixa de marcar grava sozinha. A pagina e' comprida — recarrega-la
        // por cada documento fazia perder o sitio onde se ia.
        (function () {
            const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const urlMassa = @json(route('contabilista.marcar-importado-massa', ['token' => $token]));

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
                        recalcularContadores();

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

            // ── Seleccao e accoes em lote ───────────────────────────────────
            //
            // Marcar um mes fechado caixa a caixa sao trinta gestos e trinta
            // hipoteses de saltar um. Aqui escolhe-se o mes (ou a marca) de uma
            // vez e a barra do fundo faz o resto: marcar, desmarcar, ou trazer
            // os ficheiros todos num zip.

            const barra    = document.getElementById('barra-seleccao');
            const conta    = document.getElementById('conta-seleccao');
            const formZip  = document.getElementById('form-zip');
            const botaoZip = document.getElementById('accao-zip');
            const botaoMarcar    = document.getElementById('accao-marcar');
            const botaoDesmarcar = document.getElementById('accao-desmarcar');

            function caixasDoc(dentro) {
                return Array.from((dentro || document).querySelectorAll('.selector-doc'));
            }

            // Uma linha escondida pelo filtro nao entra: escolher "o mes
            // inteiro" com o filtro ligado tem de dar o que se esta a ver.
            function visivel(caixa) {
                const linha = caixa.closest('tr');

                return linha !== null && linha.style.display !== 'none';
            }

            function escolhidas() {
                return caixasDoc().filter(function (caixa) { return caixa.checked; });
            }

            function marcarPai(caixa, filhas) {
                const quantas = filhas.filter(function (f) { return f.checked; }).length;

                caixa.checked = filhas.length > 0 && quantas === filhas.length;
                caixa.indeterminate = quantas > 0 && quantas < filhas.length;
            }

            function sincronizarPais() {
                document.querySelectorAll('.grupo-marca').forEach(function (grupo) {
                    const caixa = grupo.querySelector('.selector-grupo');

                    if (caixa) {
                        marcarPai(caixa, caixasDoc(grupo).filter(visivel));
                    }
                });

                document.querySelectorAll('.cartao-mes').forEach(function (cartao) {
                    const caixa = cartao.querySelector('.selector-mes');

                    if (caixa) {
                        marcarPai(caixa, caixasDoc(cartao).filter(visivel));
                    }
                });
            }

            function actualizarBarra() {
                const quantos = escolhidas().length;

                conta.textContent = quantos;
                barra.classList.toggle('hidden', quantos === 0);
                document.body.style.paddingBottom = quantos === 0 ? '' : '5.5rem';

                sincronizarPais();
            }

            function limparSeleccao() {
                caixasDoc().forEach(function (caixa) { caixa.checked = false; });
                actualizarBarra();
            }

            caixasDoc().forEach(function (caixa) {
                caixa.addEventListener('change', actualizarBarra);
            });

            document.querySelectorAll('.selector-grupo').forEach(function (caixa) {
                caixa.addEventListener('change', function () {
                    caixasDoc(caixa.closest('.grupo-marca'))
                        .filter(visivel)
                        .forEach(function (filha) { filha.checked = caixa.checked; });

                    actualizarBarra();
                });
            });

            document.querySelectorAll('.selector-mes').forEach(function (caixa) {
                caixa.addEventListener('change', function () {
                    caixasDoc(caixa.closest('.cartao-mes'))
                        .filter(visivel)
                        .forEach(function (filha) { filha.checked = caixa.checked; });

                    actualizarBarra();
                });
            });

            document.getElementById('limpar-seleccao')?.addEventListener('click', limparSeleccao);

            botaoZip?.addEventListener('click', function () {
                const ids = escolhidas().map(function (caixa) { return caixa.value; });

                if (ids.length === 0) {
                    return;
                }

                formZip.querySelectorAll('input[name="ids[]"]').forEach(function (campo) {
                    campo.remove();
                });

                ids.forEach(function (id) {
                    const campo = document.createElement('input');
                    campo.type = 'hidden';
                    campo.name = 'ids[]';
                    campo.value = id;
                    formZip.appendChild(campo);
                });

                formZip.submit();
            });

            async function marcarEmLote(importado) {
                const caixas = escolhidas();
                const ids = caixas.map(function (caixa) { return Number(caixa.value); });

                if (ids.length === 0) {
                    return;
                }

                const botoes = [botaoMarcar, botaoDesmarcar, botaoZip];
                botoes.forEach(function (botao) { if (botao) { botao.disabled = true; } });

                try {
                    const resposta = await fetch(urlMassa, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ ids: ids, importado: importado }),
                    });

                    if (resposta.status === 419) {
                        throw new Error('A pagina esteve aberta demasiado tempo. Recarrega (F5) e marca outra vez.');
                    }

                    if (!resposta.ok) {
                        throw new Error('O servidor respondeu ' + resposta.status + '.');
                    }

                    const dados = await resposta.json();
                    const gravados = new Set((dados.ids ?? []).map(Number));

                    caixas.forEach(function (caixa) {
                        if (!gravados.has(Number(caixa.value))) {
                            return;
                        }

                        const linha  = caixa.closest('tr');
                        const marca  = linha?.querySelector('.marcar-importada');
                        const rotulo = linha?.querySelector('[data-rotulo]');

                        if (marca) {
                            marca.checked = dados.importado;
                        }

                        linha?.setAttribute('data-importada', dados.importado ? '1' : '0');

                        if (rotulo) {
                            rotulo.textContent = dados.importado
                                ? (dados.importado_em ?? 'Importada')
                                : 'Por importar';
                            rotulo.className = 'text-[11px] leading-tight '
                                + (dados.importado ? 'text-emerald-600' : 'text-amber-600');
                        }

                        caixa.checked = false;
                    });

                    aplicarFiltro();
                    actualizarBarra();
                    recalcularContadores();

                    // Um id que nao voltou nao ficou gravado. Dizer "pronto" na
                    // mesma dava o documento por lancado sem o estar.
                    if (gravados.size !== ids.length) {
                        alert('Gravei ' + gravados.size + ' de ' + ids.length + '.\n\n'
                            + 'Os restantes ja nao estao disponiveis no portal. Recarrega a pagina (F5).');
                    }
                } catch (erro) {
                    alert('Nao consegui gravar.\n\n' + (erro?.message ?? erro));
                } finally {
                    botoes.forEach(function (botao) { if (botao) { botao.disabled = false; } });
                }
            }

            botaoMarcar?.addEventListener('click', function () { marcarEmLote(true); });
            botaoDesmarcar?.addEventListener('click', function () { marcarEmLote(false); });

            if (filtro) {
                filtro.addEventListener('click', function () {
                    soPorImportar = !soPorImportar;
                    aplicarFiltro();

                    // Uma escolha que desaparece de vista mas continua contada
                    // acaba num zip com documentos que ele julgava ter tirado.
                    limparSeleccao();
                });
            }

            function euros(n) {
                const partes = n.toFixed(2).split('.');
                return partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + partes[1];
            }

            // Os contadores ("X por importar") eram Blade renderizado no
            // servidor: marcar documentos nao os fazia descer, e e' justamente
            // o numero que ele olha para saber quanto falta ao fechar o mes.
            // Recalculam-se aqui a partir do proprio DOM, que ja' tem o estado
            // certo de cada linha em data-importada.
            function porImportarDentro(raiz) {
                if (!raiz) {
                    return { n: 0, total: 0 };
                }

                const linhas = Array.from(raiz.querySelectorAll('.linha-documento'))
                    .filter(function (linha) { return linha.getAttribute('data-importada') === '0'; });

                return {
                    n: linhas.length,
                    total: linhas.reduce(function (soma, linha) {
                        return soma + (parseFloat(linha.dataset.total) || 0);
                    }, 0),
                };
            }

            function mostrar(elemento, visivel) {
                if (elemento) {
                    elemento.style.display = visivel ? '' : 'none';
                }
            }

            function recalcularContadores() {
                const topo = porImportarDentro(document);
                const rotulo = document.querySelector('[data-contador-topo]');

                if (rotulo) {
                    rotulo.textContent = topo.n + ' por importar \u00b7 ' + euros(topo.total) + ' \u20ac';
                }

                mostrar(document.getElementById('filtro-por-importar'), topo.n > 0);
                mostrar(document.querySelector('[data-tudo-importado]'), topo.n === 0);

                document.querySelectorAll('[data-contador-ano]').forEach(function (cracha) {
                    const conta = porImportarDentro(
                        document.querySelector('section[data-ano="' + cracha.dataset.contadorAno + '"]')
                    );
                    const valor = cracha.querySelector('[data-valor]');

                    if (valor) { valor.textContent = conta.n; }
                    mostrar(cracha, conta.n > 0);
                });

                document.querySelectorAll('[data-contador-mes]').forEach(function (cracha) {
                    const chave = cracha.dataset.contadorMes;
                    const conta = porImportarDentro(document.querySelector('[data-mes-chave="' + chave + '"]'));
                    const valor = cracha.querySelector('[data-valor]');

                    if (valor) { valor.textContent = conta.n; }
                    mostrar(cracha, conta.n > 0);
                    mostrar(document.querySelector('[data-mes-fechado="' + chave + '"]'), conta.n === 0);
                });
            }

            actualizarBarra();
        })();
    </script>

</body>
</html>
