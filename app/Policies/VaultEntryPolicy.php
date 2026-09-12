<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultEntry;

/**
 * O cofre pessoal é de cada um — inclusive contra o administrador.
 *
 * Não entra na lista AdminOnlyPolicy do AppServiceProvider de propósito: um
 * estagiário tem direito ao seu cofre, e o administrador não tem direito ao
 * cofre do estagiário. De qualquer maneira, sem a master password dele, a
 * senha não se lê nem com acesso à base de dados — isto é só a porta.
 */
class VaultEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VaultEntry $entrada): bool
    {
        return $entrada->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, VaultEntry $entrada): bool
    {
        return $entrada->user_id === $user->id;
    }

    public function delete(User $user, VaultEntry $entrada): bool
    {
        return $entrada->user_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    public function restore(User $user, VaultEntry $entrada): bool
    {
        return $entrada->user_id === $user->id;
    }

    public function forceDelete(User $user, VaultEntry $entrada): bool
    {
        return $entrada->user_id === $user->id;
    }
}
