<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Um estagiário vê a lista de projectos, mas só os projectos onde tem tarefas
 * suas — a lista é limitada em ProjectResource::getEloquentQuery(), e esta
 * política é a rede de segurança para quem tentar chegar lá pelo URL.
 * Criar, editar e apagar projectos é só do administrador.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $project->tasks()->where('assigned_user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function replicate(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }
}
