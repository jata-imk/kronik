<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class SolicitudResponsablesService
{
    public function buscar(Solicitud $solicitud, User $actor, string $search = '')
    {
        abort_unless($actor->operaEnSucursal($solicitud->sucursal_id) && $actor->currentTeam, 403, 'Selecciona la sucursal responsable y un equipo autorizado.');

        return app(TeamContext::class)->run($actor, $actor->currentTeam, function () use ($solicitud, $search, $actor) {
            abort_unless(Gate::forUser($actor)->allows('assign', $solicitud) || Gate::forUser($actor)->allows('review', $solicitud), 403);
            $permissions = Permission::whereIn('name', ['read clientes', 'read solicitudes'])->where('guard_name', 'web')->count() === 2;

            return User::where('status', 'active')->whereHas('sucursales', fn ($q) => $q->whereKey($solicitud->sucursal_id))
                ->where(function ($q) use ($permissions) {
                    $q->where('is_super_admin', true);
                    if ($permissions) {
                        $q->orWhere(fn ($q) => $q->permission('read clientes')->permission('read solicitudes'));
                    }
                })
                ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')->orderBy('id')->limit(20)->get(['id', 'name']);
        });
    }
}
