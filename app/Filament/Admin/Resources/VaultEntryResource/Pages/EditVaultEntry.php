<?php

namespace App\Filament\Admin\Resources\VaultEntryResource\Pages;

use App\Filament\Admin\Resources\VaultEntryResource;
use App\Filament\Admin\Support\ExigeCofreAberto;
use App\Services\Cofre\CofreCrypto;
use App\Services\Cofre\CofreException;
use App\Services\Cofre\CofreSessao;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVaultEntry extends EditRecord
{
    use ExigeCofreAberto;

    protected static string $resource = VaultEntryResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->exigirCofreAberto();
    }

    /** Decifra para o formulário poder mostrar o que lá está. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        try {
            $chave = CofreSessao::chaveObrigatoria();

            $data['senha']        = CofreCrypto::decifrar((string) $data['segredo'], $chave);
            $data['notas_claras'] = filled($data['notas'] ?? null)
                ? CofreCrypto::decifrar($data['notas'], $chave)
                : null;
        } catch (CofreException) {
            $data['senha']        = '';
            $data['notas_claras'] = null;
        }

        unset($data['segredo'], $data['notas']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $chave = CofreSessao::chaveObrigatoria();

        $data['segredo'] = CofreCrypto::cifrar((string) ($data['senha'] ?? ''), $chave);
        $data['notas']   = filled($data['notas_claras'] ?? null)
            ? CofreCrypto::cifrar($data['notas_claras'], $chave)
            : null;

        unset($data['senha'], $data['notas_claras']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
