@extends('supplier-invoices._layout', ['title' => 'Enviar fatura'])

@section('content')
    <form method="POST" action="{{ route('supplier-invoices.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-lg border border-zinc-200 bg-white p-6">
        @csrf

        <div class="grid gap-4 md:grid-cols-3">
            <label class="block">
                <span class="text-sm font-medium">Marca</span>
                <select name="brand_id" required class="mt-1 w-full rounded-md border-zinc-300">
                    <option value="">Escolher marca</option>
                    @foreach($brands as $id => $name)
                        <option value="{{ $id }}" @selected(old('brand_id') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm font-medium">Para que foi</span>
                <input name="purpose" value="{{ old('purpose') }}" required placeholder="Ex: compra para stock, software, material escritorio" class="mt-1 w-full rounded-md border-zinc-300">
            </label>

            <label class="block">
                <span class="text-sm font-medium">Categoria</span>
                <select name="category" required class="mt-1 w-full rounded-md border-zinc-300">
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', 'fornecedores') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm font-medium">Ficheiros da fatura</span>
                <input type="file" name="documents[]" required multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full rounded-md border border-zinc-300 bg-white p-2">
                <span class="mt-1 block text-xs text-zinc-500">Podes selecionar varias fotos da mesma fatura. O sistema le todas antes da revisao.</span>
            </label>

            <label class="block">
                <span class="text-sm font-medium">Fotos extra opcionais</span>
                <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png" class="mt-1 w-full rounded-md border border-zinc-300 bg-white p-2">
            </label>
        </div>

        <button class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white">Enviar e processar</button>
    </form>
@endsection
