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
        return $user->can('update clientes')
            && $user->current_sucursal_id !== null
            && $cliente->sucursal_id !== null
            && (int) $user->current_sucursal_id === (int) $cliente->sucursal_id;
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->can('delete clientes')
            && $user->current_sucursal_id !== null
            && $cliente->sucursal_id !== null
            && (int) $user->current_sucursal_id === (int) $cliente->sucursal_id;
    }
}
