<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class ActivityLogService
{
    private const ALLOWED_METADATA = [
        'changed_fields',
        'related',
        'state',
        'provider',
        'product',
        'result',
    ];

    public function log(
        ActivityEvent $event,
        string $description,
        ?Model $subject = null,
        array $metadata = [],
        ?User $causer = null,
        ?int $teamId = null,
    ): ?ActivityContract {
        $teamId ??= $causer?->current_team_id;
        $properties = [
            ...$this->requestMetadata(),
            ...$this->sanitizeMetadata($metadata),
        ];

        $logger = activity()
            ->event($event->value)
            ->withProperties($properties)
            ->tap(function ($activity) use ($teamId): void {
                $activity->team_id = $teamId;
            });

        if ($subject) {
            $logger->performedOn($subject);
        }

        if ($causer) {
            $logger->causedBy($causer);
        }

        return $logger->log($description);
    }

    /**
     * @return array<int, string>
     */
    public function fieldNames(array $data): array
    {
        return array_values(array_keys(Arr::dot($data)));
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $metadata = Arr::only($metadata, self::ALLOWED_METADATA);
        $sanitized = [];

        if (isset($metadata['changed_fields']) && is_array($metadata['changed_fields'])) {
            $sanitized['changed_fields'] = collect($metadata['changed_fields'])
                ->filter(fn ($field) => is_string($field) && $field !== '')
                ->map(fn ($field) => Str::limit($field, 150, ''))
                ->unique()
                ->take(100)
                ->values()
                ->all();
        }

        if (isset($metadata['related']) && is_array($metadata['related'])) {
            $type = $metadata['related']['type'] ?? null;
            $id = $metadata['related']['id'] ?? null;

            if (is_string($type) && (is_int($id) || is_string($id))) {
                $sanitized['related'] = [
                    'type' => Str::limit($type, 100, ''),
                    'id' => is_string($id) ? Str::limit($id, 100, '') : $id,
                ];
            }
        }

        foreach (['state', 'provider', 'product', 'result'] as $key) {
            if (isset($metadata[$key]) && is_string($metadata[$key])) {
                $sanitized[$key] = Str::limit($metadata[$key], 100, '');
            }
        }

        return $sanitized;
    }

    /**
     * @return array{ip?: string|null, user_agent?: string|null, request_id?: string}
     */
    private function requestMetadata(): array
    {
        if (! app()->bound('request')) {
            return [];
        }

        $request = request();

        if (! $request instanceof Request) {
            return [];
        }

        $requestId = $request->attributes->get('activity_request_id');

        if (! is_string($requestId) || $requestId === '') {
            $headerRequestId = $request->header('X-Request-ID');
            $requestId = is_string($headerRequestId) && $headerRequestId !== ''
                ? Str::limit($headerRequestId, 100, '')
                : (string) Str::uuid();
            $request->attributes->set('activity_request_id', $requestId);
        }

        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $requestId,
        ];
    }
}
