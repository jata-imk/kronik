<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $teamId = Auth::user()->currentTeam->id;
        $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');
        $roles = config('permission.models.role')::where($teamsKey, $teamId)
            ->orWhere(function ($query) use ($teamsKey) {
                $query->where($teamsKey, null)->where('name', 'Super Admin');
            })
            ->with(['permissions.module'])
            ->get();

        $roles = $this->loadRoleUsers($roles, $teamId);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => fn () => $roles,
            'permissions' => Permission::with('module')->get(),
            'modules' => Module::with('permissions')->get(),
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
    public function store(Request $request)
    {
        $teamId = Auth::user()->currentTeam->id;
        $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');

        $fields = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(fn ($query) => $query
                    ->where($teamsKey, $teamId)
                    ->where('guard_name', 'web')),
            ],
            'add_all_permissions' => ['nullable', 'boolean'],
        ]);

        $addAllPermissions = (bool) ($fields['add_all_permissions'] ?? false);
        unset($fields['add_all_permissions']);

        $fields['guard_name'] = 'web';
        $fields[$teamsKey] = $teamId;

        $role = Role::create($fields);

        if ($addAllPermissions) {
            $permissions = Permission::all();
            $role->givePermissionTo($permissions);
        }

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
    public function update(Request $request, Role $role)
    {
        $this->authorizeRoleInCurrentTeam($role);

        $fields = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->ignore($role->id)
                    ->where(fn ($query) => $query
                        ->where(config('permission.column_names.team_foreign_key', 'team_id'), Auth::user()->currentTeam->id)
                        ->where('guard_name', 'web')),
            ],
            'permissions' => ['nullable', 'array'],
        ]);

        $role->update(['name' => $fields['name']]);

        if (array_key_exists('permissions', $fields)) {
            $role->syncPermissions(Permission::whereIn('id', $fields['permissions'] ?? [])->get());
        }

        return redirect()->back()->with('success', 'Rol actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $this->authorizeRoleInCurrentTeam($role);

        return DB::transaction(function () use ($role) {
            $permissionsCount = $role->permissions()->count();
            $usersCount = DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                ->where(app(\Spatie\Permission\PermissionRegistrar::class)->pivotRole, $role->id)
                ->where(config('permission.column_names.team_foreign_key', 'team_id'), Auth::user()->currentTeam->id)
                ->count();

            if ($permissionsCount > 0 || $usersCount > 0) {
                return redirect()->back()->withErrors([
                    'message' => 'El rol tiene permisos o usuarios asociados, no se puede eliminar',
                ]);
            }

            $role->delete();

            return redirect()->back()->with('success', 'Rol eliminado');
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
        $fields = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        $role->givePermissionTo($fields['permission_id']);

        return redirect()->back()->with('success', 'Permiso asignado');
    }

    private function authorizeRoleInCurrentTeam(Role $role): void
    {
        $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');

        if ($role->name === 'Super Admin' && $role->{$teamsKey} === null) {
            abort(403, 'El rol Super Admin global no se edita desde la GUI.');
        }

        abort_if((int) $role->{$teamsKey} !== (int) Auth::user()->currentTeam->id, 404);
    }

    private function loadRoleUsers($roles, int $teamId)
    {
        $rolePivotKey = app(\Spatie\Permission\PermissionRegistrar::class)->pivotRole;
        $modelMorphKey = config('permission.column_names.model_morph_key', 'model_id');
        $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');

        $roleUsers = DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
            ->whereIn($rolePivotKey, $roles->pluck('id'))
            ->where(function ($query) use ($teamsKey, $teamId) {
                $query->where($teamsKey, $teamId)->orWhereNull($teamsKey);
            })
            ->where('model_type', User::class)
            ->get()
            ->groupBy($rolePivotKey);

        $users = User::whereIn('id', $roleUsers->flatten()->pluck($modelMorphKey)->unique())
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        return $roles->map(function ($role) use ($roleUsers, $modelMorphKey, $users) {
            $assignedUsers = ($roleUsers->get($role->id) ?? collect())
                ->map(fn ($pivot) => $users->get($pivot->{$modelMorphKey}))
                ->filter()
                ->values();

            $role->setRelation('users', $assignedUsers);

            return $role;
        });
    }
}
