<?php

namespace App\Policies;

use App\Models\ProjectTask;
use App\Models\User;

/**
 * A regra dos estagiários: mexem nas tarefas que são suas e mais nada.
 * Podem apontar tarefas novas (ficam automaticamente com elas — ver
 * ProjectTask::booted), mudar o estado, registar horas e comentar.
 * Não apagam, não reordenam e não passam trabalho a outra pessoa.
 */
class ProjectTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProjectTask $task): bool
    {
        return $user->isAdmin() || (int) $task->assigned_user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProjectTask $task): bool
    {
        return $this->view($user, $task);
    }

    public function delete(User $user, ProjectTask $task): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ProjectTask $task): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ProjectTask $task): bool
    {
        return $user->isAdmin();
    }

    public function replicate(User $user, ProjectTask $task): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Distribuir trabalho é só de quem manda. */
    public function assign(User $user, ProjectTask $task): bool
    {
        return $user->isAdmin();
    }
}
