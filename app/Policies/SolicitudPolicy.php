<?php

namespace App\Policies;

use App\Models\Solicitud;
use App\Models\User;

class SolicitudPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('read solicitudes') && $user->can('read clientes');
    }

    public function view(User $user, Solicitud $solicitud): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->can('create solicitudes');
    }

    public function update(User $user, Solicitud $solicitud): bool
    {
        return $this->view($user, $solicitud) && $user->can('update solicitudes')
            && (int) $user->current_sucursal_id === (int) $solicitud->sucursal_id;
    }

    public function assign(User $user, Solicitud $solicitud): bool
    {
        return $this->view($user, $solicitud) && $user->can('assign solicitudes')
            && (int) $user->current_sucursal_id === (int) $solicitud->sucursal_id;
    }

    public function review(User $user, Solicitud $solicitud): bool
    {
        return $this->view($user, $solicitud) && $user->can('review solicitudes')
            && (int) $user->current_sucursal_id === (int) $solicitud->sucursal_id;
    }

    public function cancel(User $user, Solicitud $solicitud): bool
    {
        return $this->view($user, $solicitud) && $user->can('cancel solicitudes')
            && (int) $user->current_sucursal_id === (int) $solicitud->sucursal_id;
    }

    public function viewEvaluation(User $user, Solicitud $solicitud): bool
    {
        return $this->view($user, $solicitud) && $user->can('read evaluacion-solicitudes');
    }

    public function evaluate(User $user, Solicitud $solicitud): bool
    {
        return $this->viewEvaluation($user, $solicitud) && $user->can('create evaluacion-solicitudes')
            && (int) $user->current_sucursal_id === (int) $solicitud->sucursal_id;
    }

    public function viewCompliance(User $user, Solicitud $solicitud): bool
    {
        return $this->view($user, $solicitud) && $user->can('read cumplimiento');
    }

    public function compliance(User $user, Solicitud $solicitud): bool
    {
        return $this->viewCompliance($user, $solicitud) && $user->can('create cumplimiento')
            && (int) $user->current_sucursal_id === (int) $solicitud->sucursal_id;
    }
}
