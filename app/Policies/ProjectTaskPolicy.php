<?php

namespace App\Policies;

use App\Models\ProjectTask;
use App\Models\User;

/**
 * A regra dos estagiários: vêem as tarefas que são suas e as que ainda não têm
 * dono — é assim que escolhem trabalho. Mexer, só nas suas: para poderem mexer
 * numa livre têm primeiro de ficar com ela ("Ficar com esta").
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
        return $user->isAdmin()
            || (int) $task->assigned_user_id === (int) $user->id
            || $task->podeSerEscolhida();
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Ver uma tarefa livre não dá para lhe mexer — primeiro fica-se com ela. */
    public function update(User $user, ProjectTask $task): bool
    {
        return $user->isAdmin() || (int) $task->assigned_user_id === (int) $user->id;
    }

    /**
     * Ficar com uma tarefa que ainda não tem dono. É isto que permite ao
     * estagiário escolher trabalho sem ter de esperar que lho distribuam.
     */
    public function claim(User $user, ProjectTask $task): bool
    {
        return $task->podeSerEscolhida();
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
