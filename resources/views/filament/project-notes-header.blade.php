@if (filled($notes))
    <div class="border-b border-gray-200 px-4 py-4 dark:border-gray-700 sm:px-6">
        <div class="flex items-start gap-3">
            <x-filament::icon
                icon="heroicon-o-information-circle"
                class="mt-0.5 h-5 w-5 shrink-0 text-primary-500"
            />
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Notas do projecto
                </div>
                <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $notes }}</p>
            </div>
        </div>
    </div>
@endif
