<div class="space-y-4 text-sm">

    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
        <span class="font-semibold uppercase tracking-wide">{{ $task->statusLabel() }}</span>
        <span>· responsável: {{ $task->assignedUser?->name ?? 'por atribuir' }}</span>
        @if ($task->creator)
            <span>· criada por {{ $task->creator->name }}</span>
        @endif
        @if ($task->hours)
            <span>· {{ number_format((float) $task->hours, 2, ',', '.') }} h</span>
        @endif
    </div>

    <ol class="space-y-3">
        @forelse ($activities as $activity)
            <li class="flex gap-3">
                <div class="mt-1 shrink-0">
                    <x-filament::icon
                        :icon="\App\Models\TaskActivity::typeIcon($activity->type)"
                        class="h-4 w-4 text-gray-400"
                    />
                </div>
                <div class="min-w-0 flex-1 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="text-gray-900 dark:text-gray-100">
                        <span class="font-medium">{{ $activity->actorName() }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ mb_strtolower($activity->typeLabel()) }}</span>
                        @if ($activity->changes)
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $activity->changes['campo'] ?? '' }}:
                            </span>
                            <span class="text-gray-400 line-through">{{ $activity->changes['antes'] ?? '—' }}</span>
                            <span class="text-gray-400">&rarr;</span>
                            <span class="font-medium">{{ $activity->changes['depois'] ?? '—' }}</span>
                        @endif
                    </div>

                    @if ($activity->body)
                        <p class="mt-1 whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ $activity->body }}</p>
                    @endif

                    <div class="mt-1 text-xs text-gray-400">
                        {{ $activity->created_at->format('d/m/Y H:i') }}
                        · {{ $activity->created_at->diffForHumans() }}
                    </div>
                </div>
            </li>
        @empty
            <li class="italic text-gray-500 dark:text-gray-400">
                Ainda não há nada registado nesta tarefa.
            </li>
        @endforelse
    </ol>
</div>
