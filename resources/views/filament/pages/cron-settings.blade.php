<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Cron do servidor</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Mantém apenas um cron no servidor: <code>* * * * * php {{ base_path('artisan') }} schedule:run</code>.
                A frequência real das tarefas é configurada aqui no painel.
            </p>
        </x-filament::section>

        {{ $this->form }}
    </div>
</x-filament-panels::page>
