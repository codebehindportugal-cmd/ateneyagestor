<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

/**
 * Mesma lógica das tarefas: um estagiário só vê os tickets que lhe foram
 * atribuídos. Quem distribui — e quem apaga — é o administrador.
 */
class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || (int) $ticket->assigned_user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function replicate(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }
}
