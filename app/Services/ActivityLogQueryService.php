<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ActivityLogQueryService
{
    public function filters(User $actor, array $values): array
    {
        $teamId = ! empty($values['team_id']) ? (int) $values['team_id'] : ($actor->is_super_admin ? null : $actor->current_team_id);
        $this->authorize($actor, $teamId);

        return [
            'search' => '', 'user_id' => null, 'event' => null, 'subject_type' => null,
            'date_from' => '', 'date_to' => '', 'per_page' => 20,
            ...$values,
            'team_id' => $teamId,
            'sucursal_id' => ! empty($values['sucursal_id']) ? (int) $values['sucursal_id'] : null,
            'user_id' => ! empty($values['user_id']) ? (int) $values['user_id'] : null,
            'per_page' => (int) ($values['per_page'] ?? 20),
        ];
    }

    private function authorize(User $actor, ?int $teamId): void
    {
        if ($teamId) {
            app(TeamContext::class)->authorize($actor, Team::findOrFail($teamId), 'read activity-log');
        } else {
            abort_unless($actor->is_super_admin, 403);
            Gate::forUser($actor)->authorize('read activity-log');
        }
    }

    private function scope(User $actor, ?int $teamId, ?int $sucursalId = null): Builder
    {
        $this->authorize($actor, $teamId);
        $query = config('activitylog.activity_model')::query()->with(['causer', 'team:id,name', 'sucursal:id,nombre,clave']);
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        if ($sucursalId) {
            $query->where('sucursal_id', $sucursalId);
        }

        return $query;
    }

    public function paginate(User $actor, array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $logs = $this->query($actor, $filters)->paginate($filters['per_page'])->withQueryString();
        $records = $logs->getCollection();
        $folios = DB::table('folio_asignaciones')
            ->whereIn('modelo', $records->pluck('subject_type')->filter()->unique())
            ->whereIn('registro_id', $records->pluck('subject_id')->filter()->unique())
            ->get()->keyBy(fn ($folio) => $folio->modelo.'|'.$folio->registro_id);
        $logs->through(function ($record) use ($folios) {
            $payload = $this->payload($record);
            $payload['subject']['folio'] = $folios[$record->subject_type.'|'.$record->subject_id]->folio ?? null;

            return $payload;
        });

        return $logs;
    }

    public function query(User $actor, array $filters): Builder
    {
        $query = $this->scope($actor, $filters['team_id'], $filters['sucursal_id'])->latest();

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

    public function payload($activity): array
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

    public function options(User $actor, array $filters): array
    {
        $activityQuery = $this->scope($actor, $filters['team_id'], $filters['sucursal_id']);
        $branchIds = $this->scope($actor, $filters['team_id'])->whereNotNull('sucursal_id')->distinct()->pluck('sucursal_id');
        $teams = $actor->is_super_admin ? Team::orderBy('name')->get(['id', 'name'])
            : $actor->allTeams()->filter(fn ($team) => app(TeamContext::class)->allows($actor, $team, 'read activity-log'))->sortBy('name')->values();
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
            'teams' => $teams->map->only(['id', 'name'])->values(),
            'allowAllTeams' => (bool) $actor->is_super_admin,
            'sucursales' => Sucursal::whereKey($branchIds)->orderBy('clave')->get(['id', 'clave', 'nombre'])
                ->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->clave.' · '.$branch->nombre]),
            'users' => User::query()->whereKey($userIds)->orderBy('name')->get(['id', 'name', 'email']),
            'events' => $events->sortBy('label')->values(),
            'subjectTypes' => (clone $activityQuery)->whereNotNull('subject_type')->distinct()->pluck('subject_type')
                ->map(fn ($type) => ['value' => $type, 'label' => class_basename($type)])->values(),
        ];
    }

    public function csvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        return preg_match('/^[\\s\\x{FEFF}]*[=+\\-@]/u', $value) ? "'{$value}" : $value;
    }
}
