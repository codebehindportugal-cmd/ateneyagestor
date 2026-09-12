<?php

namespace App\Filament\Admin\Support;

use App\Filament\Admin\Pages\CofrePessoal;
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
}
