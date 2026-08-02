<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityEvent;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role_or_permission:Super Admin|read activity-log', only: ['usersActivity', 'exportActivity']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teamKey = config('permission.column_names.team_foreign_key', 'team_id');
        $user = Auth::user();

        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('ownedTeams', 'roles')->get(),
            'roles' => Role::where($teamKey, $user->currentTeam->id)->orWhere('name', 'Super Admin')->get(),
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
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
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
            fputcsv($stream, ['ID', 'Fecha', 'Evento', 'Descripción', 'Usuario', 'Email', 'Sujeto', 'ID sujeto', 'IP', 'Propiedades']);

            $this->activityQuery($filters)->cursor()->each(function ($activity) use ($stream) {
                $payload = $this->activityPayload($activity);

                fputcsv($stream, array_map($this->csvValue(...), [
                    $payload['id'],
                    $payload['created_at'],
                    $payload['event'],
                    $payload['description'],
                    $payload['causer']['name'],
                    $payload['causer']['email'],
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
        $query = $activityModel::query()->with('causer');
        $user = Auth::user();

        if ($user?->hasRole('Super Admin')) {
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
