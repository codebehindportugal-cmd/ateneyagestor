<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos — {{ $client->company ?: $client->name }}</title>
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
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/ateneya-logo.jpg') }}" alt="Ateneya" class="h-10 w-10 rounded-lg object-cover ring-1 ring-white/20">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Ateneya · Documentos partilhados</p>
                    <h1 class="text-lg font-semibold leading-tight">{{ $client->company ?: $client->name }}</h1>
                </div>
            </div>
            <div class="sm:text-right">
                <p class="text-xl font-semibold tnum leading-tight">{{ $documents->count() }}</p>
                <p class="text-xs text-slate-400">documento(s) · acesso só de leitura · {{ now()->format('d/m/Y \à\s H:i') }}</p>
            </div>
        </div>
    </header>
    <div class="h-1 bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500 no-print"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">

        @if($documents->isEmpty())
            <div class="bg-white rounded-xl ring-1 ring-slate-200 p-14 text-center shadow-sm">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-slate-500 font-medium">Ainda não há documentos disponíveis.</p>
                <p class="text-slate-400 text-sm mt-1">Assim que forem partilhados documentos, aparecem aqui automaticamente.</p>
            </div>
        @else
            {{-- Summary by type --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach(\App\Models\ClientDocument::types() as $typeKey => $typeLabel)
                    @php $count = $documents->where('type', $typeKey)->count(); @endphp
                    @if($count > 0)
                    <div class="bg-white rounded-xl ring-1 ring-slate-200 shadow-sm px-4 py-3 flex items-center justify-between">
                        <span class="text-sm text-slate-600">{{ $typeLabel }}</span>
                        <span class="text-sm font-bold text-slate-900 tnum">{{ $count }}</span>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Documents table --}}
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                    <h2 class="font-semibold text-slate-700">Todos os documentos</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[11px] text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                <th class="px-5 py-2.5 text-left font-semibold">Nome</th>
                                <th class="px-5 py-2.5 text-left font-semibold">Tipo</th>
                                <th class="px-5 py-2.5 text-left font-semibold">Ficheiro</th>
                                <th class="px-5 py-2.5 text-left font-semibold">Data</th>
                                <th class="px-5 py-2.5 text-right font-semibold">Tamanho</th>
                                <th class="px-5 py-2.5 text-center font-semibold no-print">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($documents->sortBy([['type','asc'],['created_at','desc']]) as $doc)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-3 font-medium text-slate-900">{{ $doc->name }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200">
                                            {{ $doc->type_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-500 text-xs tnum">{{ $doc->original_name }}</td>
                                    <td class="px-5 py-3 text-slate-600 whitespace-nowrap tnum">
                                        {{ $doc->created_at->format('d/m/Y') }}
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-500 text-xs whitespace-nowrap tnum">
                                        {{ $doc->formatted_size }}
                                    </td>
                                    <td class="px-5 py-3 text-center no-print">
                                        <div class="flex items-center justify-center gap-3">
                                            @if($doc->isPreviewable())
                                                <a href="{{ route('contabilista.cliente.view', ['token' => $token, 'document' => $doc->id]) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 text-slate-500 hover:text-slate-700 text-xs font-medium">
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

        <footer class="border-t border-slate-200 pt-5 pb-6 text-center">
            <p class="text-xs text-slate-400">
                Este acesso é pessoal e intransmissível · {{ config('app.name') }}
            </p>
        </footer>
    </div>

</body>
</html>
