<x-filament-panels::page>
    @php
        $failedSyncs = $syncRuns->filter(fn ($run) => $run->status === \App\Enums\SyncStatus::Failed)->count();
        $runningSyncs = $syncRuns->filter(fn ($run) => $run->status === \App\Enums\SyncStatus::Running)->count();
        $activeProjects = $syncProjects->count();
    @endphp

    <div x-data="{ tab: 'sync' }" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Projetos ativos</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ $activeProjects }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Syncs em curso</p>
                <p class="mt-2 text-3xl font-semibold {{ $runningSyncs > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $runningSyncs }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Falhas recentes</p>
                <p class="mt-2 text-3xl font-semibold {{ $failedSyncs > 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $failedSyncs }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Ficheiros de log</p>
                <p class="mt-2 text-3xl font-semibold text-gray-950 dark:text-white">{{ count($logFiles) }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-800">
            @foreach([
                ['id' => 'sync', 'label' => 'Execuções Sync'],
                ['id' => 'projects', 'label' => 'Projetos'],
                ['id' => 'files', 'label' => 'Ficheiros Log'],
                ['id' => 'laravel', 'label' => 'Laravel'],
                ['id' => 'schedule', 'label' => 'Scheduler'],
                ['id' => 'backups', 'label' => 'Backups'],
            ] as $item)
                <button
                    type="button"
                    @click="tab = '{{ $item['id'] }}'"
                    :class="tab === '{{ $item['id'] }}' ? 'border-primary-600 text-primary-700 dark:text-primary-300' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-gray-200'"
                    class="border-b-2 px-3 py-2 text-sm font-medium transition"
                >{{ $item['label'] }}</button>
            @endforeach
        </div>

        <div x-show="tab === 'sync'" x-cloak class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-950">
                    <tr>
                        <th class="px-4 py-3">Projeto</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Produtos</th>
                        <th class="px-4 py-3 text-right">Encomendas</th>
                        <th class="px-4 py-3 text-right">Erros</th>
                        <th class="px-4 py-3">Início</th>
                        <th class="px-4 py-3">Duração</th>
                        <th class="px-4 py-3">Erro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($syncRuns as $run)
                        @php
                            $statusClass = match ($run->status) {
                                \App\Enums\SyncStatus::Running => 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
                                \App\Enums\SyncStatus::Success => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                                \App\Enums\SyncStatus::Partial => 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                                \App\Enums\SyncStatus::Failed => 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
                            };
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $run->syncProject?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $run->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono">{{ $run->products_synced }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ $run->orders_synced }}</td>
                            <td class="px-4 py-3 text-right font-mono {{ $run->errors_count > 0 ? 'text-rose-600' : 'text-gray-500' }}">{{ $run->errors_count }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $run->started_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $run->elapsedSeconds() !== null ? gmdate('H:i:s', $run->elapsedSeconds()) : '—' }}</td>
                            <td class="px-4 py-3 text-xs text-rose-600">{{ \Illuminate\Support\Str::limit($run->error ?: '—', 120) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">Ainda não há execuções registadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'projects'" x-cloak class="grid gap-3 lg:grid-cols-2">
            @forelse($syncProjects as $project)
                @php $last = $project->latestSyncRun; @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-gray-950 dark:text-white">{{ $project->name }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ \App\Models\SyncProject::typeOptions()[$project->type] ?? $project->type }} · {{ $project->site_url ?: 'sem site' }}</p>
                        </div>
                        <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $project->status === 'error' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                            {{ $project->status === 'error' ? 'Erro' : ($project->status === 'ok' ? 'OK' : 'Nunca correu') }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-950">
                            <span class="block text-gray-500">Último run</span>
                            <strong>{{ $last?->status->label() ?? '—' }}</strong>
                        </div>
                        <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-950">
                            <span class="block text-gray-500">Produtos</span>
                            <strong>{{ $last?->products_synced ?? 0 }}</strong>
                        </div>
                        <div class="rounded-md bg-gray-50 p-2 dark:bg-gray-950">
                            <span class="block text-gray-500">Erros</span>
                            <strong class="{{ ($last?->errors_count ?? 0) > 0 ? 'text-rose-600' : '' }}">{{ $last?->errors_count ?? 0 }}</strong>
                        </div>
                    </div>
                    @if($last?->error)
                        <p class="mt-3 text-xs text-rose-600">{{ \Illuminate\Support\Str::limit($last->error, 180) }}</p>
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-800 dark:bg-gray-900">Sem projetos ativos.</div>
            @endforelse
        </div>

        <div x-show="tab === 'files'" x-cloak class="space-y-4">
            @forelse($logFiles as $file)
                <details class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $file['name'] }}
                        <span class="ml-2 text-xs font-normal text-gray-500">{{ number_format($file['size'] / 1024, 1, ',', '.') }} KB · {{ $file['updated_at']?->format('d/m/Y H:i') ?? '—' }}</span>
                    </summary>
                    <div class="border-t border-gray-100 p-4 dark:border-gray-800">
                        <p class="mb-2 break-all text-xs text-gray-500">{{ $file['path'] }}</p>
                        <pre class="max-h-96 overflow-auto rounded-md bg-gray-950 p-4 text-xs text-emerald-300">{{ $file['tail'] }}</pre>
                    </div>
                </details>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-800 dark:bg-gray-900">Não foram encontrados ficheiros de log.</div>
            @endforelse
        </div>

        <div x-show="tab === 'laravel'" x-cloak class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-2 text-xs text-gray-500">Últimas 200 linhas, mais recente primeiro.</p>
            <pre class="max-h-[600px] overflow-auto whitespace-pre-wrap rounded-md bg-gray-950 p-4 text-xs text-gray-200">{{ $laravelLog }}</pre>
        </div>

        <div x-show="tab === 'schedule'" x-cloak class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <pre class="whitespace-pre-wrap rounded-md bg-gray-950 p-4 text-xs text-gray-200">{{ $scheduleList }}</pre>
        </div>

        <div x-show="tab === 'backups'" x-cloak class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @if($backupErrors->isEmpty())
                <div class="p-8 text-center text-emerald-600">Sem erros de backup recentes.</div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-950">
                        <tr>
                            <th class="px-4 py-3">Servidor</th>
                            <th class="px-4 py-3">Início</th>
                            <th class="px-4 py-3">Erro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($backupErrors as $run)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $run->server?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $run->started_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-rose-600">{{ $run->error ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-filament-panels::page>
