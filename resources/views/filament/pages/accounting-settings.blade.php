<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Status card --}}
        @if($accessUrl)
            <div class="rounded-xl border border-success-200 bg-success-50 dark:border-success-700 dark:bg-success-950 p-5">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-check-circle class="h-6 w-6 text-success-600 dark:text-success-400 mt-0.5 shrink-0" />
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-success-800 dark:text-success-200 mb-1">Acesso activo</p>
                        <p class="text-sm text-success-700 dark:text-success-300 mb-3">
                            Partilha este URL com o teu contabilista. Só quem tiver o link consegue aceder — sem precisar de conta ou password.
                        </p>
                        <div class="flex items-center gap-2">
                            <code
                                id="access-url"
                                class="flex-1 truncate rounded-lg border border-success-300 dark:border-success-600 bg-white dark:bg-success-900 px-3 py-2 text-sm font-mono text-gray-700 dark:text-gray-200"
                            >{{ $accessUrl }}</code>
                            <button
                                type="button"
                                onclick="navigator.clipboard.writeText('{{ $accessUrl }}').then(() => { this.textContent = '✓ Copiado'; setTimeout(() => this.textContent = 'Copiar', 2000); })"
                                class="shrink-0 rounded-lg border border-success-300 dark:border-success-600 bg-white dark:bg-success-900 px-3 py-2 text-sm text-success-700 dark:text-success-300 hover:bg-success-100 dark:hover:bg-success-800 transition"
                            >Copiar</button>
                            <a
                                href="{{ $accessUrl }}"
                                target="_blank"
                                class="shrink-0 rounded-lg border border-success-300 dark:border-success-600 bg-white dark:bg-success-900 px-3 py-2 text-sm text-success-700 dark:text-success-300 hover:bg-success-100 dark:hover:bg-success-800 transition"
                            >Abrir ↗</a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-5">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-lock-closed class="h-6 w-6 text-gray-400 mt-0.5 shrink-0" />
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Sem acesso activo</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Clica em <strong>Gerar novo token</strong> para criar um URL de acesso para o contabilista.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Info box --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 space-y-2">
            <p class="font-semibold text-gray-700 dark:text-gray-200 text-sm">Como funciona</p>
            <ul class="space-y-1 text-sm text-gray-500 dark:text-gray-400 list-disc list-inside">
                <li>O contabilista acede ao URL sem precisar de login.</li>
                <li>Só pode ver e descarregar documentos — não consegue editar nem apagar.</li>
                <li>Os documentos estão organizados por ano e mês com totais.</li>
                <li>Podes revogar o acesso a qualquer momento ou gerar um novo token.</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
