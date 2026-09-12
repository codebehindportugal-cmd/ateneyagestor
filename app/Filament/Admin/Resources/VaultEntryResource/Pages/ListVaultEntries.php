<?php

namespace App\Filament\Admin\Resources\VaultEntryResource\Pages;

use App\Filament\Admin\Pages\ImportarKeePass;
use App\Filament\Admin\Resources\VaultEntryResource;
use App\Filament\Admin\Support\ExigeCofreAberto;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVaultEntries extends ListRecords
{
    use ExigeCofreAberto;

    protected static string $resource = VaultEntryResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->exigirCofreAberto();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nova senha'),
            Actions\Action::make('importar')
                ->label('Importar do KeePass')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->url(ImportarKeePass::getUrl()),
        ];
    }
}
