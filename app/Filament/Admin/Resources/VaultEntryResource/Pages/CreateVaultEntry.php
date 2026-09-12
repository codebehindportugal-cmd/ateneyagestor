<?php

namespace App\Filament\Admin\Resources\VaultEntryResource\Pages;

use App\Filament\Admin\Resources\VaultEntryResource;
use App\Filament\Admin\Support\ExigeCofreAberto;
use App\Services\Cofre\CofreCrypto;
use Filament\Resources\Pages\CreateRecord;

class CreateVaultEntry extends CreateRecord
{
    use ExigeCofreAberto;

    protected static string $resource = VaultEntryResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->exigirCofreAberto();
    }

    /**
     * `senha` e `notas_claras` são campos do formulário, não colunas. Aqui é
     * onde passam a `segredo` e `notas`, já cifrados.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $chave = $this->chaveDoCofreOuPara();

        $data['user_id'] = auth()->id();
        $data['segredo'] = CofreCrypto::cifrar((string) ($data['senha'] ?? ''), $chave);
        $data['notas']   = filled($data['notas_claras'] ?? null)
            ? CofreCrypto::cifrar($data['notas_claras'], $chave)
            : null;

        unset($data['senha'], $data['notas_claras']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
