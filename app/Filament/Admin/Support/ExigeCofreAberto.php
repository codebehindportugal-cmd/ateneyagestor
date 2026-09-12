<?php

namespace App\Filament\Admin\Support;

use App\Filament\Admin\Pages\CofrePessoal;
use App\Services\Cofre\CofreException;
use App\Services\Cofre\CofreSessao;
use Filament\Notifications\Notification;

/**
 * Quem não tem o cofre aberto não fica com uma página meia partida: vai para
 * a porta do cofre com um aviso do porquê.
 */
trait ExigeCofreAberto
{
    protected function exigirCofreAberto(): bool
    {
        if (CofreSessao::destrancado()) {
            return true;
        }

        Notification::make()
            ->title('O cofre está trancado')
            ->body('Abre-o para veres ou gravares senhas privadas.')
            ->warning()
            ->send();

        $this->redirect(CofrePessoal::getUrl());

        return false;
    }

    /**
     * A chave do cofre na hora de gravar — ou para tudo com um aviso.
     *
     * ⚠️ O cofre cai ao fim de `cofre.minutos_inactividade` (15) SEM PEDIDO AO
     * SERVIDOR, e escrever no formulário não conta como actividade. Antes, o
     * `chaveObrigatoria()` era chamado em cru no `mutateFormDataBeforeSave`:
     * quem abrisse a edição de uma senha, escrevesse com calma um quarto de
     * hora e carregasse em Gravar levava com uma `CofreException` por apanhar —
     * 500, e o texto perdido.
     *
     * O `halt(true)` desfaz a transacção e devolve o controlo ao Filament com o
     * formulário intacto: reabre-se o cofre e carrega-se em Gravar outra vez.
     */
    protected function chaveDoCofreOuPara(): string
    {
        try {
            return CofreSessao::chaveObrigatoria();
        } catch (CofreException $erro) {
            Notification::make()
                ->title('O cofre trancou-se')
                ->body('Esteve '.config('cofre.minutos_inactividade', 15).' minutos sem actividade. Abre-o outra vez e carrega em Gravar — o que escreveste continua aqui.')
                ->warning()
                ->persistent()
                ->send();

            $this->halt(true);

            throw $erro; // Nunca chega aqui: o halt() lança. É só para o tipo de retorno.
        }
    }
}
