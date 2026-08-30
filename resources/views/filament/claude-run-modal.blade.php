<div class="space-y-4 text-sm">

    {{-- Estado --}}
    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
        <span class="font-semibold uppercase tracking-wide">{{ $run->statusLabel() }}</span>
        @if ($run->durationLabel())
            <span>· {{ $run->durationLabel() }}</span>
        @endif
        @if ($run->costLabel())
            <span>· {{ $run->costLabel() }}</span>
        @endif
        @if ($run->requestedBy)
            <span>· pedido por {{ $run->requestedBy->name }}</span>
        @endif
    </div>

    @if ($run->status === 'failed')
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-red-500 mb-1">Não correu</div>
            <div class="rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 p-3">
                <pre class="text-xs text-red-700 dark:text-red-300 whitespace-pre-wrap font-mono">{{ $run->error }}</pre>
            </div>
        </div>
    @elseif ($run->isDone())
        <div class="overflow-auto max-h-[28rem] rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-sans leading-relaxed">{{ $run->result }}</pre>
        </div>
    @else
        <div class="rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                Ainda a trabalhar. Fecha e volta a abrir daqui a bocado.
                @if ($run->status === 'queued')
                    <br><br>
                    Se ficar muito tempo em <strong>Na fila</strong>, o worker não está a correr:
                    <code>php artisan claude:work</code> na máquina onde estão os repositórios.
                @endif
            </p>
        </div>
    @endif

    {{-- Retomar a conversa no terminal, onde ele pode mesmo alterar ficheiros --}}
    @if ($run->session_id)
        @php($pasta = $run->task?->project?->code_path)
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Continuar esta conversa</div>
            <div class="rounded-lg bg-gray-950 p-3">
<pre class="text-xs text-green-400 whitespace-pre-wrap font-mono select-all">@if ($pasta)cd {{ $pasta }}
@endif claude --resume {{ $run->session_id }}</pre>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Abre a mesma sessão no terminal, com o contexto já carregado. Aí é uma sessão normal do Claude Code — pode alterar ficheiros, ao contrário do botão, que corre só em leitura.
            </p>
        </div>
    @endif

    {{-- O prompt que foi mesmo enviado, para se poder auditar --}}
    @if ($run->prompt)
        <details>
            <summary class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer">Ver o que foi enviado ao Claude</summary>
            <div class="mt-2 overflow-auto max-h-64 rounded-lg bg-gray-950 p-4">
                <pre class="text-xs text-green-400 whitespace-pre-wrap font-mono">{{ $run->prompt }}</pre>
            </div>
        </details>
    @endif

    <div class="text-xs text-gray-400 dark:text-gray-500 flex gap-4">
        <span>Pedido: {{ $run->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
        <span>Fim: {{ $run->finished_at?->format('d/m/Y H:i') ?? '—' }}</span>
    </div>

</div>
