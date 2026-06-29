<div class="space-y-4 text-sm">

    {{-- Resumo numérico --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $run->products_synced }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Produtos sincronizados</div>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $run->orders_synced }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Encomendas sincronizadas</div>
        </div>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
            <div class="text-2xl font-bold {{ $run->errors_count > 0 ? 'text-red-500' : 'text-gray-900 dark:text-white' }}">{{ $run->errors_count }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Erros</div>
        </div>
    </div>

    {{-- Erro (se existir) --}}
    @if ($run->error)
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-red-500 mb-1">Erro</div>
            <div class="rounded-lg bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 p-3">
                <pre class="text-xs text-red-700 dark:text-red-300 whitespace-pre-wrap font-mono">{{ $run->error }}</pre>
            </div>
        </div>
    @endif

    {{-- Metadata (o que o script enviou) --}}
    @if ($run->metadata)
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Metadata (enviado pelo script)</div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-3">
                <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono">{{ json_encode($run->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    @endif

    @php
        $liveLog = $run->liveLog();
        $shownLog = $run->log ?: $liveLog;
    @endphp

    {{-- Log completo --}}
    <div>
        <div class="flex items-center justify-between mb-1">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Log</div>
            @if ($run->status === \App\Enums\SyncStatus::Running)
                <div class="text-xs text-blue-500">Em curso - fecha e abre os detalhes para atualizar</div>
            @endif
        </div>
        @if ($shownLog)
            <div class="overflow-auto max-h-64 rounded-lg bg-gray-950 p-4">
                <pre class="text-xs text-green-400 whitespace-pre-wrap font-mono">{{ $shownLog }}</pre>
            </div>
        @else
            <div class="rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                    Sem log disponível. O script não enviou o campo <code>log</code> no POST para <code>/api/sync/runs</code>.
                    @if ($run->products_synced === 0 && $run->orders_synced === 0)
                        <br><br>
                        <strong class="text-amber-600 dark:text-amber-400">Atenção:</strong>
                        produtos_synced e orders_synced estão a 0. Verifica se o script está a enviar estes campos no body do POST,
                        e se o campo <code>SYNC_RESULT:</code> está a ser impresso no output (para runs locais via <code>sync:run</code>).
                    @endif
                </p>
            </div>
        @endif
    </div>

    {{-- Timestamps --}}
    <div class="text-xs text-gray-400 dark:text-gray-500 flex gap-4">
        <span>Início: {{ $run->started_at?->format('d/m/Y H:i:s') ?? '—' }}</span>
        <span>Fim: {{ $run->finished_at?->format('d/m/Y H:i:s') ?? '—' }}</span>
        @if ($run->elapsedSeconds() !== null)
            <span>Duração: {{ gmdate('H:i:s', $run->elapsedSeconds()) }}</span>
        @endif
    </div>

</div>
