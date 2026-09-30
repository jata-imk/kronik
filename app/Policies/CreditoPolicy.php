<?php

namespace App\Policies;

use App\Models\Credito;
use App\Models\User;

class CreditoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('read creditos') && $user->can('read solicitudes') && $user->can('read clientes');
    }

    public function view(User $user, Credito $credito): bool
    {
        return $this->viewAny($user);
    }

    public function pay(User $user, Credito $credito): bool
    {
        return $this->view($user, $credito) && $user->can('pay creditos')
            && (int) $user->current_sucursal_id === (int) $credito->sucursal_id;
    }

    public function reversePayment(User $user, Credito $credito): bool
    {
        return $this->view($user, $credito) && $user->can('reverse payments creditos')
            && (int) $user->current_sucursal_id === (int) $credito->sucursal_id;
    }
}
