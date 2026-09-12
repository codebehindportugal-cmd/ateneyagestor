<?php

namespace App\Filament\Admin\Resources\SiteProvisionResource\Pages;

use App\Filament\Admin\Resources\SiteProvisionResource;
use App\Models\SiteProvision;
use App\Services\Provisioning\ProvisionadorDeSite;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSiteProvisions extends ListRecords
{
    protected static string $resource = SiteProvisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('criar')
                ->label('Criar site')
                ->icon('heroicon-m-plus')
                ->modalHeading('Criar um site novo')
                ->modalDescription('Escreve o domínio. O painel trata do utilizador de sistema, das pastas, do PHP-FPM, do Apache, da base de dados, do certificado e do WordPress — e guarda as senhas no cofre.')
                ->modalSubmitActionLabel('Montar agora')
                ->modalWidth('2xl')
                ->form(SiteProvisionResource::camposDoPedido())
                ->action(function (array $data) {
                    // Descarregar o WordPress e pedir o certificado leva bem
                    // mais do que o tempo normal de um pedido web.
                    @set_time_limit(0);

                    $provisao = SiteProvision::create([
                        'server_id' => $data['server_id'],
                        'client_id' => $data['client_id'] ?? null,
                        'user_id'   => auth()->id(),
                        'dominio'   => $data['dominio'],
                        'estado'    => 'pendente',
                        'opcoes'    => [
                            'ssl'       => (bool) ($data['ssl'] ?? true),
                            'wordpress' => (bool) ($data['wordpress'] ?? true),
                            'email'     => $data['email'] ?? null,
                            'titulo'    => $data['titulo'] ?? null,
                            'admin'     => $data['admin'] ?? 'ateneya',
                        ],
                    ]);

                    $provisao = app(ProvisionadorDeSite::class)->correr($provisao);

                    if ($provisao->estado === 'erro') {
                        Notification::make()
                            ->title('Parou a meio')
                            ->body($provisao->erro)
                            ->danger()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title("{$provisao->dominio} está montado")
                            ->body('As senhas ficaram no Cofre de Passwords.')
                            ->success()
                            ->send();
                    }

                    $this->redirect(SiteProvisionResource::getUrl('view', ['record' => $provisao]));
                }),
        ];
    }
}
