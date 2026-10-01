{{-- Contabilidade > Moloni — ver App\Filament\Admin\Pages\MoloniPage. --}}
<x-filament-panels::page>
    <form wire:submit="guardar" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                Guardar
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
