<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos — {{ $client->company ?: $client->name }}</title>
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
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    {{ $client->company ?: $client->name }}
                </h1>
                <p class="text-xs text-gray-400 mt-0.5">
                    Documentos partilhados · acesso só de leitura · {{ now()->format('d/m/Y \à\s H:i') }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-700">{{ $documents->count() }} documento(s)</p>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 space-y-6">

        @if($documents->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-400">Ainda não há documentos disponíveis.</p>
            </div>
        @else
            {{-- Summary by type --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach(\App\Models\ClientDocument::types() as $typeKey => $typeLabel)
                    @php $count = $documents->where('type', $typeKey)->count(); @endphp
                    @if($count > 0)
                    <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-600">{{ $typeLabel }}</span>
                        <span class="text-sm font-bold text-gray-900">{{ $count }}</span>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Documents table (all in one, sorted by type then date) --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-700">Todos os documentos</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 uppercase tracking-wide">
                                <th class="px-5 py-2 text-left font-medium">Nome</th>
                                <th class="px-5 py-2 text-left font-medium">Tipo</th>
                                <th class="px-5 py-2 text-left font-medium">Ficheiro</th>
                                <th class="px-5 py-2 text-left font-medium">Data</th>
                                <th class="px-5 py-2 text-right font-medium">Tamanho</th>
                                <th class="px-5 py-2 text-center font-medium no-print">Acções</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($documents->sortBy([['type','asc'],['created_at','desc']]) as $doc)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $doc->name }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                            {{ $doc->type_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 text-xs font-mono">{{ $doc->original_name }}</td>
                                    <td class="px-5 py-3 text-gray-600 whitespace-nowrap">
                                        {{ $doc->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-gray-500 text-xs whitespace-nowrap">
                                        {{ $doc->formatted_size }}
                                    </td>
                                    <td class="px-5 py-3 text-center no-print">
                                        <div class="flex items-center justify-center gap-2">
                                            @if($doc->isPreviewable())
                                                <a href="{{ route('contabilista.cliente.view', ['token' => $token, 'document' => $doc->id]) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 text-xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    Ver
                                                </a>
                                            @endif
                                            <a href="{{ route('contabilista.cliente.download', ['token' => $token, 'document' => $doc->id]) }}"
                                               class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                Download
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <p class="text-center text-xs text-gray-300 pb-4">
            Este acesso é pessoal e intransmissível · {{ config('app.name') }}
        </p>
    </div>

</body>
</html>
