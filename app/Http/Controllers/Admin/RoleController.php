<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $teamId = Auth::user()->currentTeam->id;
        $roles = config('permission.models.role')::where(config('permission.column_names.team_foreign_key', 'team_id'), $teamId)->with('permissions.module')->get();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => fn() => $roles,
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
        $fields = $request->validate([
            'team_id' => ['nullable', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            // 'guard_name' => ['nullable', 'string', 'max:255'],
            'add_all_permissions' => ['nullable', 'boolean'],
        ]);

        $fields['guard_name'] = 'web';

        $role = Role::create($fields);

        if ($fields['add_all_permissions']) {
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
        $fields = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
        ]);

        $role->update(['name' => $fields['name']]);

        if ($fields['permissions']) {
            $role->syncPermissions($fields['permissions']);
        }

        return redirect()->back()->with('success', 'Rol actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        DB::transaction(function () use ($role) {
            $permissionsCount = $role->permissions()->count();
            $usersCount = $role->users()->count();

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
}
