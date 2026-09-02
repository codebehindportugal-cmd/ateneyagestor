<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * O acesso de estagiário é global: vê a lista toda de projectos e pode abrir
 * qualquer um deles. O que lá vê dentro continua a ser só o trabalho que lhe
 * foi atribuído (ver ProjectTaskPolicy).
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
        return true;
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
