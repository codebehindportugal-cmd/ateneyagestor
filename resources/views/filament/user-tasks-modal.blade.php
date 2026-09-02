<div class="space-y-4 text-sm">

    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
        <span class="font-semibold uppercase tracking-wide">{{ $user->roleLabel() }}</span>
        @if ($user->job_title)
            <span>· {{ $user->job_title }}</span>
        @endif
        <span>· {{ $tasks->count() }} tarefas</span>
        <span>· {{ \App\Models\ProjectTask::formatarHoras($tasks->sum('estimated_hours')) ?? '0 h' }} estimadas</span>
        <span>· {{ \App\Models\ProjectTask::formatarHoras($tasks->sum('hours')) ?? '0 h' }} registadas</span>
    </div>

    @forelse ($tasks as $task)
        <div class="flex items-start gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <div class="min-w-0 flex-1">
                <div class="font-medium {{ $task->isDone() ? 'text-gray-400 line-through' : 'text-gray-900 dark:text-gray-100' }}">
                    {{ $task->title }}
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $task->project?->name ?? 'sem projecto' }}
                    · {{ $task->statusLabel() }}
                    @if ($task->due_date)
                        · prazo {{ $task->due_date->format('d/m/Y') }}
                    @endif
                    @if ($task->completed_at)
                        · concluída {{ $task->completed_at->format('d/m/Y H:i') }}
                    @endif
                    @if ($task->estimated_hours)
                        · estimativa {{ \App\Models\ProjectTask::formatarHoras($task->estimated_hours) }}
                    @endif
                    @if ($task->hours)
                        · {{ \App\Models\ProjectTask::formatarHoras($task->hours) }} feitas
                    @endif
                </div>
            </div>
            @if ($task->isOverdue())
                <span class="shrink-0 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 dark:bg-red-950 dark:text-red-300">em atraso</span>
            @endif
        </div>
    @empty
        <p class="italic text-gray-500 dark:text-gray-400">
            Ainda não tem nenhuma tarefa atribuída. Vai a um projecto, abre as Tarefas e escolhe o responsável.
        </p>
    @endforelse
</div>
