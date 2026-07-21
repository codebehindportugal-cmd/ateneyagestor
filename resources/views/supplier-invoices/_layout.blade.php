<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Faturas de fornecedores' }} · {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', system-ui, sans-serif; }
        .tnum { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 antialiased">

    {{-- Top bar --}}
    <header class="bg-slate-900 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/ateneya-logo.jpg') }}" alt="Ateneya" class="h-10 w-10 rounded-lg object-cover ring-1 ring-white/20">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.18em] text-slate-400">Ateneya · Gestão</p>
                    <h1 class="text-lg font-semibold leading-tight">{{ $title ?? 'Faturas de fornecedores' }}</h1>
                </div>
            </div>
            <nav class="flex items-center gap-2">
                <a href="{{ route('supplier-invoices.index') }}"
                   class="rounded-lg px-3.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('supplier-invoices.index') ? 'bg-white/15 text-white' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">Lista</a>
                <a href="{{ route('supplier-invoices.create') }}"
                   class="rounded-lg px-3.5 py-2 text-sm font-medium transition-colors {{ request()->routeIs('supplier-invoices.create') ? 'bg-white/15 text-white' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">Enviar fatura</a>
                <a href="/admin"
                   class="rounded-lg border border-white/20 px-3.5 py-2 text-sm font-medium text-slate-200 hover:bg-white/10 transition-colors">Admin</a>
            </nav>
        </div>
    </header>
    <div class="h-1 bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500"></div>

    <main class="mx-auto max-w-7xl px-4 sm:px-6 py-8">
        <p class="mb-6 text-sm text-slate-500">Upload privado, OCR local e revisão antes de confirmar.</p>

        @if(session('status'))
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')

        <footer class="mt-10 border-t border-slate-200 pt-5 pb-4 text-center">
            <p class="text-xs text-slate-400">{{ config('app.name') }} · área interna</p>
        </footer>
    </main>
</body>
</html>
