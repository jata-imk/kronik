<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityEvent;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:read users', only: ['index']),
            new Middleware('permission:create users', only: ['store', 'resendInvitation']),
            new Middleware('permission:update users', only: ['update']),
            new Middleware('permission:delete users', only: ['destroy']),
            new Middleware('permission:read activity-log', only: ['usersActivity', 'exportActivity']),
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

        return Inertia::render('Admin/Users/Index', [
            'users' => fn () => User::query()
                ->with(['ownedTeams', 'teams', 'sucursales', 'sucursalPrincipal', 'currentSucursal'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => $this->userPayload($user)),
            'teams' => fn () => Team::query()->where('activo', true)->orderBy('name')->get(['id', 'name']),
            'roles' => fn () => Role::query()
                ->whereNotNull('team_id')
                ->where('name', '!=', 'Super Admin')
                ->orderBy('name')
                ->get(['id', 'name', 'team_id']),
            'sucursales' => fn () => Sucursal::query()->where('activa', true)->orderBy('nombre')->get(['id', 'nombre', 'clave']),
            'statusOptions' => collect(UserStatus::cases())->map(fn (UserStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'canManageSuperAdmin' => (bool) Auth::user()->is_super_admin,
            'prefill' => $prefill,
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
    public function store(StoreUserRequest $request, UserManagementService $service)
    {
        $user = $service->create($request->validated(), $request->user());
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => 'La cuenta fue creada, pero no se pudo enviar la invitación. Puedes reenviarla desde el listado.',
            ]);
        }

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
        if ($user->is_super_admin && ! $request->user()->is_super_admin) {
            abort(403, 'Solo un Super Admin global puede desactivar otra cuenta global.');
        }

        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'No puedes desactivar tu propia cuenta.',
            ]);
        }

        $user->forceFill(['status' => UserStatus::Inactive])->save();
        $user->tokens()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return back()->with('success', 'Usuario desactivado');
    }

    public function resendInvitation(Request $request, User $user)
    {
        abort_unless($user->status === UserStatus::Pending, 422, 'Sólo se pueden reenviar invitaciones pendientes.');

        $status = Password::sendResetLink(['email' => $user->email]);
        throw_if($status !== Password::RESET_LINK_SENT, ValidationException::withMessages([
            'email' => 'No se pudo enviar la invitación. Revisa la configuración de correo.',
        ]));

        $user->forceFill(['invited_at' => now()])->save();

        return back()->with('success', 'Invitación reenviada');
    }

    private function userPayload(User $user): array
    {
        $teamRoles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->id)
            ->where('roles.name', '!=', 'Super Admin')
            ->get(['model_has_roles.team_id', 'roles.id as role_id'])
            ->groupBy('team_id');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'status_label' => $user->status->label(),
            'is_super_admin' => (bool) $user->is_super_admin,
            'current_team_id' => $user->current_team_id,
            'sucursal_principal_id' => $user->sucursal_principal_id,
            'current_sucursal_id' => $user->current_sucursal_id,
            'sucursal_ids' => $user->sucursales->pluck('id')->all(),
            'team_names' => $user->allTeams()->pluck('name')->values()->all(),
            'sucursal_names' => $user->sucursales->pluck('nombre')->values()->all(),
            'team_search' => $user->allTeams()->pluck('name')->join(' '),
            'sucursal_search' => $user->sucursales->pluck('nombre')->join(' '),
            'team_roles' => $user->allTeams()->map(fn (Team $team) => [
                'team_id' => $team->id,
                'role_ids' => $teamRoles->get($team->id, collect())->pluck('role_id')->all(),
            ])->values(),
            'invited_at' => $user->invited_at?->toIso8601String(),
        ];
    }

    /**
     * Display users activity logs.
     */
    public function usersActivity(Request $request)
    {
        $filters = $this->validatedActivityFilters($request);
        $activityLogs = $this->activityQuery($filters)
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString()
            ->through(fn ($activity) => $this->activityPayload($activity));

        return Inertia::render('Admin/Logs/UserActivity', [
            'activityLogs' => $activityLogs,
            'filters' => $filters,
            'filterOptions' => $this->activityFilterOptions(),
        ]);
    }

    public function exportActivity(Request $request)
    {
        $filters = $this->validatedActivityFilters($request);
        $filename = 'actividad-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['ID', 'Fecha', 'Evento', 'Descripción', 'Usuario', 'Email', 'Equipo', 'Sucursal', 'Sujeto', 'ID sujeto', 'IP', 'Propiedades']);

            $this->activityQuery($filters)->cursor()->each(function ($activity) use ($stream) {
                $payload = $this->activityPayload($activity);

                fputcsv($stream, array_map($this->csvValue(...), [
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

    private function validatedActivityFilters(Request $request): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'event' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);

        return [
            'search' => '',
            'user_id' => null,
            'event' => null,
            'subject_type' => null,
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
            ...$filters,
        ];
    }

    private function activityQuery(array $filters): Builder
    {
        $query = $this->scopedActivityQuery()->latest();

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($builder) use ($search) {
                $builder->where('description', 'like', $search)
                    ->orWhere('event', 'like', $search)
                    ->orWhere('subject_type', 'like', $search)
                    ->orWhereHasMorph('causer', [User::class], fn ($causer) => $causer
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search));
            });
        }

        if (! empty($filters['user_id'])) {
            $query->where('causer_type', User::class)->where('causer_id', $filters['user_id']);
        }

        if ($filters['event'] === 'legacy.unclassified') {
            $query->whereNull('event');
        } elseif (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function scopedActivityQuery(): Builder
    {
        $activityModel = config('activitylog.activity_model');
        $query = $activityModel::query()->with(['causer', 'team:id,name', 'sucursal:id,nombre,clave']);
        $user = Auth::user();

        if ($user?->is_super_admin) {
            return $query;
        }

        if (! $user?->current_team_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('team_id', $user->current_team_id);
    }

    private function activityPayload($activity): array
    {
        $properties = $activity->properties;
        $properties = is_object($properties) && method_exists($properties, 'toArray')
            ? $properties->toArray()
            : (array) $properties;
        $causer = $activity->causer;
        $event = $activity->event
            ? ActivityEvent::tryFrom($activity->event)
            : null;
        $eventValue = $activity->event ?: 'legacy.unclassified';

        return [
            'id' => $activity->id,
            'event' => $eventValue,
            'event_label' => $activity->event
                ? ($event?->label() ?? $activity->event)
                : 'Sin clasificar (histórico)',
            'event_severity' => $event?->severity() ?? 'secondary',
            'event_icon' => $event?->icon() ?? ($activity->event ? 'pi-circle' : 'pi-history'),
            'description' => $activity->description,
            'causer' => [
                'id' => $causer?->id,
                'name' => $causer?->name ?? 'Sistema',
                'email' => $causer?->email ?? '',
            ],
            'subject' => [
                'type' => $activity->subject_type ? class_basename($activity->subject_type) : '—',
                'id' => $activity->subject_id,
            ],
            'team' => [
                'id' => $activity->team_id,
                'name' => $activity->team?->name ?? 'Sin equipo',
            ],
            'sucursal' => [
                'id' => $activity->sucursal_id,
                'clave' => $activity->sucursal?->clave,
                'name' => $activity->sucursal?->nombre ?? 'Sin sucursal',
            ],
            'ip' => $properties['ip'] ?? null,
            'properties' => $properties,
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    private function activityFilterOptions(): array
    {
        $activityQuery = $this->scopedActivityQuery();
        $events = collect(ActivityEvent::options())->keyBy('value');
        $unknownEvents = (clone $activityQuery)
            ->whereNotNull('event')
            ->distinct()
            ->pluck('event')
            ->reject(fn ($event) => $events->has($event))
            ->mapWithKeys(fn ($event) => [
                $event => ['value' => $event, 'label' => $event],
            ]);
        $events = $events->merge($unknownEvents);

        if ((clone $activityQuery)->whereNull('event')->exists()) {
            $events->put('legacy.unclassified', [
                'value' => 'legacy.unclassified',
                'label' => 'Sin clasificar (histórico)',
            ]);
        }

        $userIds = (clone $activityQuery)
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        return [
            'users' => User::query()->whereKey($userIds)->orderBy('name')->get(['id', 'name', 'email']),
            'events' => $events->sortBy('label')->values(),
            'subjectTypes' => (clone $activityQuery)->whereNotNull('subject_type')->distinct()->pluck('subject_type')
                ->map(fn ($type) => ['value' => $type, 'label' => class_basename($type)])->values(),
        ];
    }

    private function csvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        return preg_match('/^[\\s\\x{FEFF}]*[=+\\-@]/u', $value) ? "'{$value}" : $value;
    }
}
