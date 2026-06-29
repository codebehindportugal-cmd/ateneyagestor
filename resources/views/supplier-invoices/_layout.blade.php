<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Faturas de fornecedores' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-100 text-zinc-900">
    <main class="mx-auto max-w-7xl px-4 py-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">{{ $title ?? 'Faturas de fornecedores' }}</h1>
                <p class="text-sm text-zinc-500">Upload privado, OCR local e revisao antes de confirmar.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('supplier-invoices.index') }}" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium">Lista</a>
                <a href="{{ route('supplier-invoices.create') }}" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white">Enviar fatura</a>
                <a href="/admin" class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium">Admin</a>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="mb-5 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
