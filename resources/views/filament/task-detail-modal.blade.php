<div class="space-y-4 text-sm">

    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
        <span class="font-semibold uppercase tracking-wide">{{ $task->statusLabel() }}</span>
        <span>· {{ $task->project?->name ?? 'sem projecto' }}</span>
        <span>· {{ $task->assignedUser?->name ?? 'por atribuir' }}</span>
        @if ($task->due_date)
            <span>· prazo {{ $task->due_date->format('d/m/Y') }}</span>
        @endif
        @if ($task->estimated_hours)
            <span>· estimativa {{ \App\Models\ProjectTask::formatarHoras($task->estimated_hours) }}</span>
        @endif
        @if ($task->hours)
            <span>· {{ \App\Models\ProjectTask::formatarHoras($task->hours) }} registadas</span>
        @endif
    </div>

    @if (filled($task->description))
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <p class="whitespace-pre-wrap leading-relaxed text-gray-800 dark:text-gray-200">{{ $task->description }}</p>
        </div>
    @else
        <p class="italic text-gray-500 dark:text-gray-400">Esta tarefa não tem descrição.</p>
    @endif

    @if (filled($task->project?->notes))
        <details class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <summary class="cursor-pointer text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Notas do projecto (regras e links do código)
            </summary>
            <p class="mt-2 whitespace-pre-wrap leading-relaxed text-gray-700 dark:text-gray-300">{{ $task->project->notes }}</p>
        </details>
    @endif
</div>
