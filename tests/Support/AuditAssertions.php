<?php

use App\Models\Activity;
use App\Models\User;

function assertDomainAudit(array $codes, User $actor, ?int $branchId, array $reserved = []): void
{
    foreach ($codes as $code) {
        $events = Activity::where('event', $code)->get();
        expect($events->count(), $code.' debe persistirse')->toBeGreaterThan(0);
        foreach ($events as $event) {
            expect($event->causer_id, $code)->toBe($actor->id)
                ->and($event->team_id)->toBe($actor->current_team_id)
                ->and($event->sucursal_id)->toBe($branchId)
                ->and($event->subject_id)->not->toBeNull();
            $properties = $event->properties->all();
            expect(array_diff(array_keys($properties), ['ip', 'user_agent', 'request_id', 'changed_fields', 'related', 'state', 'provider', 'product', 'result']))->toBe([]);
            foreach ($reserved as $value) {
                expect($event->toJson())->not->toContain($value);
            }
        }
    }
}
