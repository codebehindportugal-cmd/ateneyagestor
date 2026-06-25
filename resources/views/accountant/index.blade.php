<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Contabilidade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 shadow-sm no-print">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Documentos para Contabilidade</h1>
                <p class="text-xs text-gray-400 mt-0.5">Acesso só de leitura · gerado a {{ now()->format('d/m/Y \à\s H:i') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-700">{{ number_format($grandTotal['amount'], 2, ',', '.') }} €</p>
                <p class="text-xs text-gray-400">{{ $grandTotal['count'] }} documento(s) no total</p>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 space-y-10">

        @forelse($brandGroups as $brandId => $brandData)
            @php
                $brand      = $brandData['brand'];
                $brandName  = $brand ? $brand->name : 'Geral';
                $brandColor = $brand?->color ?? '#9ca3af';
                $brandParent = $brand?->parent?->name;
            @endphp

            <section>
                {{-- Brand header --}}
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="inline-block w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $brandColor }}"></span>
                    <h2 class="text-lg font-bold text-gray-800">{{ $brandName }}</h2>
                    @if($brandParent)
                        <span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5">{{ $brandParent }}</span>
                    @endif
                    <span class="ml-auto text-sm text-gray-500 bg-white border border-gray-200 rounded-full px-3 py-1 whitespace-nowrap">
                        {{ $brandData['total']['count'] }} doc(s) ·
                        <span class="font-semibold text-gray-700">{{ number_format($brandData['total']['amount'], 2, ',', '.') }} €</span>
                    </span>
                </div>

                <div class="space-y-6">
                    @foreach($brandData['grouped'] as $year => $months)
                        <div>
                            {{-- Year sub-header --}}
                            <div class="flex items-center justify-between mb-2 px-1">
                                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">{{ $year }}</h3>
                                <span class="text-xs text-gray-400">
                                    {{ $brandData['yearTotals'][$year]['count'] }} doc(s) ·
                                    <span class="font-semibold text-gray-600">{{ number_format($brandData['yearTotals'][$year]['amount'], 2, ',', '.') }} €</span>
                                </span>
                            </div>

                            <div class="space-y-3">
                                @foreach($months as $month => $docs)
                                    @php
                                        $monthTotal = $docs->sum('amount_cents') / 100;
                                        $monthCount = $docs->count();
                                    @endphp

                                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                        {{-- Month header --}}
                                        <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200">
                                            <h4 class="font-semibold text-gray-700">
                                                {{ \App\Models\AccountingDocument::monthName($month) }}
                                            </h4>
                                            <span class="text-sm text-gray-500">
                                                {{ $monthCount }} doc(s) ·
                                                <span class="font-semibold text-gray-700">{{ number_format($monthTotal, 2, ',', '.') }} €</span>
                                            </span>
                                        </div>

                                        {{-- Documents table --}}
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="text-xs text-gray-400 uppercase tracking-wide">
                                                        <th class="px-5 py-2 text-left font-medium">Título</th>
                                                        <th class="px-5 py-2 text-left font-medium">Nº Fatura</th>
                                                        <th class="px-5 py-2 text-left font-medium">Data</th>
                                                        <th class="px-5 py-2 text-left font-medium">Categoria</th>
                                                        <th class="px-5 py-2 text-right font-medium">Valor</th>
                                                        <th class="px-5 py-2 text-center font-medium no-print">Ficheiro</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($docs->sortByDesc('date') as $doc)
                                                        <tr class="hover:bg-gray-50 transition-colors">
                                                            <td class="px-5 py-3 font-medium text-gray-900">
                                                                {{ $doc->title }}
                                                                @if($doc->notes)
                                                                    <p class="text-xs text-gray-400 font-normal mt-0.5">{{ $doc->notes }}</p>
                                                                @endif
                                                            </td>
                                                            <td class="px-5 py-3 text-gray-500 font-mono text-xs">
                                                                {{ $doc->invoice_number ?? '—' }}
                                                            </td>
                                                            <td class="px-5 py-3 text-gray-600 whitespace-nowrap">
                                                                {{ $doc->date->format('d/m/Y') }}
                                                            </td>
                                                            <td class="px-5 py-3">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                                                    {{ $doc->category_label }}
                                                                </span>
                                                            </td>
                                                            <td class="px-5 py-3 text-right font-mono font-semibold text-gray-800 whitespace-nowrap">
                                                                {{ number_format($doc->amount, 2, ',', '.') }} €
                                                            </td>
                                                            <td class="px-5 py-3 text-center no-print">
                                                                @if($doc->file_path)
                                                                    <a href="{{ route('contabilista.download', ['token' => $token, 'id' => $doc->id]) }}"
                                                                       class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-xs font-medium"
                                                                       target="_blank">
                                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                                        </svg>
                                                                        Download
                                                                    </a>
                                                                @else
                                                                    <span class="text-gray-300 text-xs">—</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                {{-- Month subtotal --}}
                                                <tfoot>
                                                    <tr class="bg-gray-50 border-t-2 border-gray-200">
                                                        <td colspan="4" class="px-5 py-2 text-xs text-gray-500 font-medium">
                                                            Total {{ \App\Models\AccountingDocument::monthName($month) }}
                                                        </td>
                                                        <td class="px-5 py-2 text-right font-bold text-gray-800 font-mono">
                                                            {{ number_format($monthTotal, 2, ',', '.') }} €
                                                        </td>
                                                        <td class="no-print"></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-400">Ainda não há documentos disponíveis.</p>
            </div>
        @endforelse

        {{-- Footer --}}
        <p class="text-center text-xs text-gray-300 pb-4">
            Este acesso é pessoal e intransmissível · {{ config('app.name') }}
        </p>
    </div>

</body>
</html>
