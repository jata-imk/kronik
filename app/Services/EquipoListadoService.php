<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class EquipoListadoService
{
    public function miembros(Team $team)
    {
        return User::query()->where(fn ($q) => $q->whereKey($team->user_id)->orWhereHas('teams', fn ($q) => $q->whereKey($team->id)));
    }

    public function paginas(Request $request, Team $team): array
    {
        $request->validate(['invitaciones_page' => 'nullable|integer|min:1', 'invitaciones_buscar' => 'nullable|string|max:100',
            'invitaciones_por_pagina' => ['nullable', 'integer', Rule::in([10, 25, 50])]]);
        $params = app(ListadoParametros::class);
        $filters = [
            'name' => fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%'),
            'email' => fn ($q, $v) => $q->where('email', 'like', '%'.$v.'%'),
            'status_label' => fn ($q, $v) => $q->where('status', ['Activo' => 'active', 'Pendiente' => 'pending', 'Inactivo' => 'inactive'][$v] ?? $v),
            'roles_search' => fn ($q, $v) => $q->where(function ($q) use ($team, $v) {
                $q->whereIn('users.id', DB::table('model_has_roles')->join('roles', 'roles.id', '=', 'role_id')
                    ->select('model_id')->where('model_has_roles.team_id', $team->id)->where('model_type', (new User)->getMorphClass())->where('roles.name', 'like', '%'.$v.'%'));
                if (str_contains(mb_strtolower('Super Admin'), mb_strtolower($v))) {
                    $q->orWhere('is_super_admin', true);
                }
            }),
            'sucursales_search' => fn ($q, $v) => $q->whereHas('sucursales', fn ($q) => $q->where(fn ($q) => $q->where('nombre', 'like', '%'.$v.'%')->orWhere('clave', 'like', '%'.$v.'%'))),
        ];
        $filters['global'] = fn ($q, $v) => $q->where(function ($q) use ($filters, $v) {
            foreach ($filters as $filter) {
                $q->orWhere(fn ($q) => $filter($q, $v));
            }
        });
        $sorts = ['name' => 'name', 'email' => 'email', 'status_label' => 'status'];
        $values = $params->resolver($request, $filters, $sorts);
        $base = $this->miembros($team);
        $members = $params->aplicar((clone $base)->where('status', '!=', 'pending'), $values, $filters, $sorts, 'name')
            ->paginate($values['por_pagina'])->withQueryString();
        $pending = (clone $base)->where('status', 'pending')->when($request->filled('invitaciones_buscar'), fn ($q) => $q->where(fn ($q) => $q
            ->where('name', 'like', '%'.$request->input('invitaciones_buscar').'%')->orWhere('email', 'like', '%'.$request->input('invitaciones_buscar').'%')))
            ->orderBy('name')->orderBy('id')->paginate($request->integer('invitaciones_por_pagina', 25), ['*'], 'invitaciones_page')->withQueryString();
        $activeRoleCounts = DB::table('model_has_roles')->where('team_id', $team->id)->where('model_type', (new User)->getMorphClass())
            ->whereIn('model_id', (clone $base)->where('status', 'active')->select('users.id'))
            ->groupBy('role_id')->selectRaw('role_id, count(distinct model_id) as members_count')->pluck('members_count', 'role_id');

        return [$members, $pending, (clone $base)->count(), $activeRoleCounts, $params->metadata($members, $values), $params->metadata($pending, [])];
    }
}
