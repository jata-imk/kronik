<?php

namespace App\Services;

use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AdministracionListadoService
{
    public function __construct(private ListadoParametros $params) {}

    private function texto(array $fields): \Closure
    {
        return fn ($q, $v) => $q->where(function ($q) use ($fields, $v) {
            foreach ($fields as $field) {
                $q->orWhere($field, 'like', '%'.$v.'%');
            }
        });
    }

    private function unir(array $parts): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? implode(' || ', array_map(fn ($part) => '('.$part.')', $parts))
            : 'concat('.implode(', ', $parts).')';
    }

    private function jsonTexto(string $column, array $keys): string
    {
        $parts = [];
        foreach ($keys as $key) {
            if ($parts) {
                $parts[] = "' '";
            }
            $parts[] = 'coalesce('.DB::connection()->getQueryGrammar()->wrap($column.'->'.$key).", '')";
        }

        $text = $this->unir($parts);
        for ($i = 0; $i < 3; $i++) {
            $text = 'replace('.$text.", '  ', ' ')";
        }

        return 'case when trim('.$text.") = '' then '".($column === 'domicilio' ? 'Sin domicilio' : 'Sin horario')."' else trim(".$text.') end';
    }

    public function usuarios(Request $request): array
    {
        $name = $this->texto(['name', 'email']);
        $teams = fn ($q, $v) => $q->where(fn ($q) => $q->whereHas('teams', fn ($t) => $t->where('name', 'like', '%'.$v.'%'))
            ->orWhereHas('ownedTeams', fn ($t) => $t->where('name', 'like', '%'.$v.'%')));
        $branches = fn ($q, $v) => $q->where(function ($q) use ($v) {
            $q->where(fn ($q) => $q->where('is_super_admin', false)->whereHas('sucursales', fn ($b) => $b->where('nombre', 'like', '%'.$v.'%')));
            if (str_contains(mb_strtolower('Acceso global'), mb_strtolower($v))) {
                $q->orWhere('is_super_admin', true);
            }
        });
        $status = fn ($q, $v) => $q->where('status', ['Activo' => 'active', 'Inactivo' => 'inactive', 'Pendiente' => 'pending'][$v] ?? $v);
        $filters = ['user_search' => $name, 'team_search' => $teams, 'sucursal_search' => $branches, 'status_label' => $status];
        $filters['global'] = fn ($q, $v) => $q->where(function ($q) use ($name, $teams, $branches, $v) {
            $q->where(fn ($q) => $name($q, $v))->orWhere(fn ($q) => $teams($q, $v))
                ->orWhere(fn ($q) => $branches($q, $v))->orWhereIn('status', array_keys(array_filter(['active' => 'Activo', 'pending' => 'Pendiente', 'inactive' => 'Inactivo'], fn ($label) => str_contains(mb_strtolower($label), mb_strtolower($v)))));
        });
        $sorts = ['user_search' => 'name', 'name' => 'name', 'status_label' => 'status'];
        $values = $this->params->resolver($request, $filters, $sorts);
        $query = User::with(['ownedTeams', 'teams', 'sucursales', 'sucursalPrincipal', 'currentSucursal']);
        $page = $this->params->aplicar($query, $values, $filters, $sorts, 'name')->paginate($values['por_pagina'])->withQueryString();

        return ['users' => app(UserAdministrationData::class)->users($page->getCollection())->values(), 'listado' => $this->params->metadata($page, $values)];
    }

    public function equipos(Request $request): array
    {
        $name = $this->texto(['name']);
        $owner = fn ($q, $v) => $q->whereHas('owner', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$v.'%')->orWhere('email', 'like', '%'.$v.'%')));
        $summarySql = $this->unir(['(select count(distinct user_id) from team_user where team_id = teams.id and user_id != teams.user_id) + 1', "' miembros '", '(select count(*) from roles where team_id = teams.id)', "' roles '", '(select count(*) from users where current_team_id = teams.id)', "' actuales'"]);
        $summary = fn ($q, $v) => $q->whereRaw($summarySql.' like ?', ['%'.$v.'%']);
        $status = fn ($q, $v) => in_array($v, ['Activo', 'Inactivo'], true) ? $q->where('activo', $v === 'Activo') : $q->whereRaw('1 = 0');
        $filters = ['name' => $name, 'owner_display' => $owner, 'summary_search' => $summary, 'status_label' => $status];
        $filters['global'] = fn ($q, $v) => $q->where(fn ($q) => $q->where(fn ($q) => $name($q, $v))->orWhere(fn ($q) => $owner($q, $v))->orWhere(fn ($q) => $summary($q, $v))
            ->orWhereIn('activo', array_keys(array_filter([1 => 'Activo', 0 => 'Inactivo'], fn ($label) => str_contains(mb_strtolower($label), mb_strtolower($v)))))
            ->orWhereIn('personal_team', array_keys(array_filter([1 => 'Personal', 0 => 'Institucional'], fn ($label) => str_contains(mb_strtolower($label), mb_strtolower($v))))));
        $sorts = ['name' => 'name', 'owner_display' => User::select('name')->whereColumn('users.id', 'teams.user_id')->limit(1)];
        $values = $this->params->resolver($request, $filters, $sorts, ['status_label' => 'Activo']);
        $query = Team::select('teams.*')->with('owner:id,name,email,profile_photo_path')->withCount(['users', 'ownedRoles', 'currentUsers'])
            ->addSelect(['non_owner_members_count' => DB::table('team_user')->selectRaw('count(distinct user_id)')->whereColumn('team_id', 'teams.id')->whereColumn('user_id', '!=', 'teams.user_id')]);
        $page = $this->params->aplicar($query, $values, $filters, $sorts, 'name')->paginate($values['por_pagina'])->withQueryString();

        return ['teams' => $page->getCollection()->map(fn ($team) => [
            'id' => $team->id, 'name' => $team->name, 'activo' => $team->activo, 'personal_team' => $team->personal_team,
            'status_label' => $team->activo ? 'Activo' : 'Inactivo', 'type_label' => $team->personal_team ? 'Personal' : 'Institucional',
            'owner' => $team->owner, 'members_count' => (int) $team->non_owner_members_count + 1, 'roles_count' => $team->owned_roles_count,
            'current_users_count' => $team->current_users_count,
        ]), 'listado' => $this->params->metadata($page, $values)];
    }

    public function sucursales(Request $request): array
    {
        $request->validate(['selected_sucursal_id' => 'nullable|integer|min:1']);
        $name = $this->texto(['nombre', 'telefono', 'email']);
        $addressSql = $this->jsonTexto('domicilio', ['calle', 'numero_exterior', 'numero_interior', 'colonia', 'municipio', 'estado', 'codigo_postal']);
        $hoursSql = $this->jsonTexto('horario', ['lunes_viernes', 'sabado', 'domingo']);
        $address = fn ($q, $v) => $q->whereRaw($addressSql.' like ?', ['%'.$v.'%']);
        $hours = fn ($q, $v) => $q->whereRaw($hoursSql.' like ?', ['%'.$v.'%']);
        $summarySql = $this->unir(['(select count(*) from sucursal_user where sucursal_id = sucursales.id)', "' usuarios '", '(select count(*) from clientes where sucursal_id = sucursales.id)', "' clientes'"]);
        $summary = fn ($q, $v) => $q->whereRaw($summarySql.' like ?', ['%'.$v.'%']);
        $filters = ['sucursal_search' => $name, 'domicilio_display' => $address, 'horario_search' => $hours,
            'summary_search' => $summary, 'estado_label' => fn ($q, $v) => in_array($v, ['Activa', 'Inactiva'], true) ? $q->where('activa', $v === 'Activa') : $q->whereRaw('1 = 0')];
        $filters['global'] = fn ($q, $v) => $q->where(fn ($q) => $q->where(fn ($q) => $name($q, $v))->orWhere(fn ($q) => $address($q, $v))->orWhere(fn ($q) => $hours($q, $v))->orWhere(fn ($q) => $summary($q, $v))
            ->orWhereIn('activa', array_keys(array_filter([1 => 'Activa', 0 => 'Inactiva'], fn ($label) => str_contains(mb_strtolower($label), mb_strtolower($v))))));
        $sorts = ['sucursal_search' => 'nombre', 'nombre' => 'nombre'];
        $values = $this->params->resolver($request, $filters, $sorts, ['estado_label' => 'Activa']);
        $query = Sucursal::withCount(['users', 'clientes']);
        $page = $this->params->aplicar($query, $values, $filters, $sorts, 'nombre')->paginate($values['por_pagina'])->withQueryString();

        return ['sucursalSeleccionada' => $request->filled('selected_sucursal_id') ? (function () use ($request) {
            $selected = Sucursal::withCount(['users', 'clientes'])->findOrFail($request->integer('selected_sucursal_id'));

            return [...$selected->toArray(), 'folios' => app(FolioService::class)->configuracion($selected)];
        })() : null, 'sucursales' => $page->getCollection()->map(fn ($sucursal) => [...$sucursal->toArray(), 'folios' => app(FolioService::class)->configuracion($sucursal)]),
            'listado' => $this->params->metadata($page, $values)];
    }
}
