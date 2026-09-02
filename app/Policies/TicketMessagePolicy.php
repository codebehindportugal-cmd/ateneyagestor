<?php

namespace App\Policies;

use App\Models\TicketMessage;
use App\Models\User;

/**
 * As mensagens seguem o ticket: quem pode ver o ticket pode ler e responder.
 * Apagar mensagens é só do administrador — é conversa com o cliente.
 */
class TicketMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TicketMessage $message): bool
    {
        return $user->isAdmin()
            || (int) $message->ticket?->assigned_user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TicketMessage $message): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TicketMessage $message): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, TicketMessage $message): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, TicketMessage $message): bool
    {
        return $user->isAdmin();
    }

    public function replicate(User $user, TicketMessage $message): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }
}
