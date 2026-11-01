<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Module;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(\App\Http\Middleware\AuthorizeRoleTeam::class)];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $team = app(\App\Services\TeamContext::class)->resolve($request);
        $teamId = $team->id;
        abort_unless($teamId, 403);
        $request->validate(['selected_role_id' => 'nullable|integer|min:1', 'integrantes_page' => 'nullable|integer|min:1',
            'integrantes_buscar' => 'nullable|string|max:100', 'integrantes_por_pagina' => ['nullable', 'integer', \Illuminate\Validation\Rule::in([10, 25, 50])]]);
        $params = app(\App\Services\ListadoParametros::class);
        $filters = ['global' => fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%')];
        $sorts = ['name' => 'name'];
        $values = $params->resolver($request, $filters, $sorts);
        $query = config('permission.models.role')::where(config('permission.column_names.team_foreign_key', 'team_id'), $teamId)->where('guard_name', 'web')->with('permissions.module');
        $page = $params->aplicar(clone $query, $values, $filters, $sorts, 'name')->paginate($values['por_pagina'])->withQueryString();
        $roles = $page->getCollection();
        $id = $request->input('selected_role_id', $roles->first()?->id);
        $selected = $id ? (clone $query)->findOrFail($id) : null;
        $members = $selected ? User::query()->whereIn('id', DB::table('model_has_roles')->select('model_id')
            ->where('team_id', $teamId)->where('role_id', $selected->id)->where('model_type', (new User)->getMorphClass()))
            ->when($request->filled('integrantes_buscar'), fn ($q) => $q->where('name', 'like', '%'.$request->input('integrantes_buscar').'%'))
            ->orderBy('name')->orderBy('id')->paginate($request->integer('integrantes_por_pagina', 25), ['id', 'name'], 'integrantes_page')->withQueryString() : null;
        $membersByRole = $selected ? [$selected->id => $members->getCollection()] : [];

        return Inertia::render('Admin/Roles/Index', [
            'roles' => fn () => $roles,
            'selectedRole' => $selected,
            'listado' => $params->metadata($page, $values),
            'roleMembersListado' => $members ? $params->metadata($members, []) : null,
            'roleMembers' => fn () => $membersByRole,
            'permissions' => Permission::where('guard_name', 'web')->with('module')->get(),
            'modules' => Module::with(['permissions' => fn ($query) => $query->where('guard_name', 'web')])->get(),
            'roleTeam' => $team->only(['id', 'name']),
            'roleCapabilities' => app(\App\Services\TeamContext::class)->run($request->user(), $team, fn () => collect(['create', 'update', 'delete'])->mapWithKeys(fn ($action) => [$action => $request->user()->can("{$action} roles")])),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $role = Role::query()->create([
                'name' => $request->validated('name'),
                'guard_name' => 'web',
                'team_id' => app(\App\Services\TeamContext::class)->resolve($request)->id,
            ]);
            if ($request->boolean('add_all_permissions')) {
                $role->syncPermissions(Permission::where('guard_name', 'web')->get());
            }
        });

        return redirect()->back()->with('success', 'Rol creado');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->ensureRoleTeam($request, $role);
        DB::transaction(function () use ($request, $role) {
            $role->update(['name' => $request->validated('name')]);
            if ($request->has('permissions')) {
                $role->syncPermissions(Permission::whereIn('id', $request->validated('permissions'))->get());
            }
        });

        return redirect()->back()->with('success', 'Rol actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Role $role)
    {
        $this->ensureRoleTeam($request, $role);

        return DB::transaction(function () use ($role) {
            $permissionsCount = $role->permissions()->count();
            $usersCount = DB::table('model_has_roles')->where('role_id', $role->id)->count();

            if ($permissionsCount > 0 || $usersCount > 0) {
                return redirect()->back()->withErrors([
                    'message' => 'El rol tiene permisos o usuarios asociados, no se puede eliminar',
                ]);
            }

            $role->delete();

            return redirect()->route('admin.roles.index', ['team' => $role->team_id])->with('success', 'Rol eliminado');
        });
    }

    public function createPermission(Request $request)
    {
        $fields = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'guard_name' => ['nullable', 'string', 'max:255'],
        ]);

        Permission::create(['name' => $fields['name'], 'guard_name' => $fields['guard_name']]);

        return redirect()->back()->with('success', 'Permiso creado');
    }

    public function assignPermission(Request $request, Role $role)
    {
        $this->ensureRoleTeam($request, $role);
        $fields = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $role->givePermissionTo($fields['permission_id']);

        return redirect()->back()->with('success', 'Permiso asignado');
    }

    private function ensureRoleTeam(Request $request, Role $role): void
    {
        abort_unless($role->guard_name === 'web' && $role->team_id !== null && (int) $role->team_id === (int) app(\App\Services\TeamContext::class)->resolve($request)->id, 404);
    }
}
