<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

/**
 * Estagiarios veem e carregam; apagar e' so' do administrador.
 *
 * Why: um ficheiro que o cliente mandou nao se recupera. Carregar a mais custa
 * espaco em disco; apagar a menos custa a foto do erro que ninguem voltou a
 * tirar. Decidido pelo Andre a 08/09/2026.
 *
 * Este modelo NAO entra na ADMIN_ONLY_MODELS do AppServiceProvider de
 * proposito — se entrasse, os estagiarios deixavam de ver os anexos das
 * tarefas deles.
 */
class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Attachment $anexo): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Attachment $anexo): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Attachment $anexo): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
