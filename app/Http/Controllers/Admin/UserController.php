<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
        $user = Auth::user();

        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('ownedTeams', 'roles')->get(),
            'roles' => Role::where($teamKey, $user->currentTeam->id)
                ->orWhere(function ($query) use ($teamKey) {
                    $query->where($teamKey, null)->where('name', 'Super Admin');
                })
                ->get(),
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
        //
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
    public function update(Request $request, User $user)
    {
        $teamId = Auth::user()->currentTeam->id;
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');

        if ($request->has('roles')) {
            $roleIds = collect($request->roles)->map(fn ($roleId) => (int) $roleId)->all();
            $roles = Role::whereIn('id', $roleIds)->get();

            $assignsSuperAdmin = $roles->contains(fn ($role) => $role->name === 'Super Admin' && $role->{$teamKey} === null);

            if ($assignsSuperAdmin && ! Auth::user()->hasRole('Super Admin')) {
                abort(403, 'Solo Super Admin puede asignar el rol Super Admin.');
            }

            $invalidRole = $roles->first(fn ($role) => $role->name !== 'Super Admin' && (int) $role->{$teamKey} !== (int) $teamId);
            abort_if($invalidRole, 422, 'No puedes asignar roles de otro equipo.');

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($teamId);
            }

            $user->syncRoles($roles);
        }

        return redirect()->back()->with('success', 'Usuario actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Display users activity logs.
     */
    public function usersActivity(Request $request)
    {
        $activityModel = config('activitylog.activity_model');
        $paginatedActivityLogs = $activityModel::with('causer', 'subject')->paginate(3);

        return Inertia::render('Admin/Logs/UserActivity', [
            'paginatedActivityLogs' => $paginatedActivityLogs,
        ]);
    }
}
