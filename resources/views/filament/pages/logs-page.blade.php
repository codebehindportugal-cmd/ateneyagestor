<x-filament-panels::page>
    {{-- Tabs --}}
    <div x-data="{ tab: 'laravel' }" class="space-y-4">
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700 pb-0">
            @foreach([
                ['id' => 'laravel',   'label' => 'Laravel Log'],
                ['id' => 'schedule',  'label' => 'Scheduler'],
                ['id' => 'backups',   'label' => 'Erros Backup'],
                ['id' => 'sync',      'label' => 'Erros Sync'],
            ] as $t)
            <button
                @click="tab = '{{ $t['id'] }}'"
                :class="tab === '{{ $t['id'] }}' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                class="px-4 py-2 text-sm transition-colors"
            >{{ $t['label'] }}</button>
            @endforeach
        </div>

        {{-- Laravel Log --}}
        <div x-show="tab === 'laravel'" x-cloak>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
                <p class="text-xs text-gray-500 mb-2">Últimas 200 linhas (mais recente primeiro) — atualiza a página para refrescar</p>
                <pre class="text-xs font-mono whitespace-pre-wrap break-all overflow-auto max-h-[600px] leading-relaxed">@foreach(array_filter(explode("\n", $laravelLog)) as $line)@php
    $color = 'text-gray-300 dark:text-gray-400';
    if (str_contains($line, '.ERROR')) $color = 'text-red-400 dark:text-red-400 font-semibold';
    elseif (str_contains($line, '.WARNING')) $color = 'text-yellow-400';
    elseif (str_contains($line, '.INFO')) $color = 'text-green-400';
    elseif (str_contains($line, 'production.')) $color = 'text-blue-400';
@endphp<span class="{{ $color }}">{{ $line }}</span>
@endforeach</pre>
            </div>
        </div>

        {{-- Schedule --}}
        <div x-show="tab === 'schedule'" x-cloak>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4">
                <p class="text-xs text-gray-500 mb-2">Output de <code>php artisan schedule:list</code></p>
                <pre class="text-sm font-mono whitespace-pre-wrap text-gray-200 dark:text-gray-300">{{ $scheduleList }}</pre>
            </div>
        </div>

        {{-- Backup Errors --}}
        <div x-show="tab === 'backups'" x-cloak>
            @if($backupErrors->isEmpty())
                <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-950 p-6 text-center text-green-700 dark:text-green-300">
                    Sem erros de backup recentes.
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-left">
                            <tr>
                                <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Servidor</th>
                                <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Início</th>
                                <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Erro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($backupErrors as $run)
                            <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-2 font-medium">{{ $run->server?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $run->started_at?->format('d/m H:i') ?? '—' }}</td>
                                <td class="px-4 py-2 text-red-600 dark:text-red-400 text-xs font-mono break-all">{{ $run->error ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Sync Errors --}}
        <div x-show="tab === 'sync'" x-cloak>
            @if($syncErrors->isEmpty())
                <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-950 p-6 text-center text-green-700 dark:text-green-300">
                    Sem erros ou execuções presas.
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-left">
                            <tr>
                                <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Projeto</th>
                                <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Estado</th>
                                <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Início</th>
                                <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Fim</th>
                                <th class="px-4 py-2 font-medium text-gray-600 dark:text-gray-300">Erro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($syncErrors as $run)
                            <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-2 font-medium">{{ $run->syncProject?->name ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $run->status->value === 'running' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' }}">
                                        {{ $run->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-500">{{ $run->started_at?->format('d/m H:i') ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $run->finished_at?->format('d/m H:i') ?? '—' }}</td>
                                <td class="px-4 py-2 text-red-600 dark:text-red-400 text-xs font-mono break-all">{{ $run->error ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
