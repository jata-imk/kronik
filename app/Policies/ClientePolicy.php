<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('read clientes');
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->can('read clientes');
    }

    public function create(User $user): bool
    {
        return $user->can('create clientes');
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->can('update clientes');
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->can('delete clientes');
    }
}
