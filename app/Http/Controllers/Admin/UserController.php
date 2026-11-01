<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityLogRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityLogQueryService;
use App\Services\UserInvitationService;
use App\Services\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:read users', only: ['index', 'options']),
            new Middleware('permission:create users', only: ['store', 'resendInvitation']),
            new Middleware('permission:update users', only: ['update']),
            new Middleware('permission:delete users', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $prefill = $request->validate([
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'edit_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'invite' => ['nullable', 'boolean'],
        ]);
        foreach (['team_id', 'sucursal_id', 'role_id', 'edit_user_id'] as $key) {
            if (isset($prefill[$key])) {
                $prefill[$key] = (int) $prefill[$key];
            }
        }
        if (isset($prefill['invite'])) {
            $prefill['invite'] = (bool) $prefill['invite'];
        }

        if (isset($prefill['team_id'], $prefill['role_id']) && ! \Spatie\Permission\Models\Role::whereKey($prefill['role_id'])->where('team_id', $prefill['team_id'])->exists()) {
            $prefill['role_id'] = null;
        }
        $data = app(\App\Services\UserAdministrationData::class);

        return Inertia::render('Admin/Users/Index', [
            ...app(\App\Services\AdministracionListadoService::class)->usuarios($request),
            'selectedUser' => isset($prefill['edit_user_id']) ? $data->user(User::findOrFail($prefill['edit_user_id'])->load(['ownedTeams', 'teams', 'sucursales', 'sucursalPrincipal', 'currentSucursal'])) : null,
            ...$data->formOptions($request->user(), isset($prefill['edit_user_id']) ? User::findOrFail($prefill['edit_user_id']) : null, $prefill),
            'prefill' => $prefill,
        ]);
    }

    public function options(Request $request)
    {
        $data = $request->validate([
            'tipo' => ['required', \Illuminate\Validation\Rule::in(['teams', 'roles', 'sucursales'])],
            'buscar' => 'nullable|string|max:100', 'usuario_id' => 'nullable|integer|exists:users,id',
            'team_id' => 'nullable|integer|exists:teams,id', 'incluidos' => 'nullable|array|max:100', 'incluidos.*' => 'integer|min:1',
        ]);

        return app(\App\Services\UserAdministrationData::class)->opciones($data['tipo'], $data['buscar'] ?? '', $data['incluidos'] ?? [],
            isset($data['usuario_id']) ? User::findOrFail($data['usuario_id']) : null, $data['team_id'] ?? null);
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
    public function store(StoreUserRequest $request, UserManagementService $service)
    {
        $user = $service->create($request->validated(), $request->user());
        app(UserInvitationService::class)->send($user, $request->user());

        return back()->with('success', 'Usuario creado e invitación enviada');
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
    public function update(UpdateUserRequest $request, User $user, UserManagementService $service)
    {
        $service->update($user, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Usuario actualizado');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        app(UserManagementService::class)->deactivate($user, $request->user());

        return back()->with('success', 'Usuario desactivado');
    }

    public function resendInvitation(Request $request, User $user)
    {
        app(UserInvitationService::class)->send($user, $request->user());

        return back()->with('success', 'Invitación reenviada');
    }

    /**
     * Display users activity logs.
     */
    public function usersActivity(ActivityLogRequest $request, ActivityLogQueryService $activity)
    {
        $filters = $activity->filters($request->user(), $request->validated());
        $activityLogs = $activity->paginate($request->user(), $filters);

        return Inertia::render('Admin/Logs/UserActivity', [
            'activityLogs' => $activityLogs,
            'filters' => $filters,
            'filterOptions' => $activity->options($request->user(), $filters),
            'activityTeam' => $filters['team_id'] ? Team::findOrFail($filters['team_id'])->only(['id', 'name']) : null,
        ]);
    }

    public function exportActivity(ActivityLogRequest $request, ActivityLogQueryService $activity)
    {
        $actor = $request->user();
        $filters = $activity->filters($actor, $request->validated());
        $filename = 'actividad-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($actor, $activity, $filters) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['ID', 'Fecha', 'Evento', 'Descripción', 'Usuario', 'Email', 'Equipo', 'Sucursal', 'Sujeto', 'ID sujeto', 'IP', 'Propiedades']);

            $activity->query($actor, $filters)->cursor()->each(function ($record) use ($stream, $activity) {
                $payload = $activity->payload($record);

                fputcsv($stream, array_map($activity->csvValue(...), [
                    $payload['id'],
                    $payload['created_at'],
                    $payload['event'],
                    $payload['description'],
                    $payload['causer']['name'],
                    $payload['causer']['email'],
                    $payload['team']['name'],
                    $payload['sucursal']['name'],
                    $payload['subject']['type'],
                    $payload['subject']['id'],
                    $payload['ip'],
                    json_encode($payload['properties'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                ]));
            });

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
