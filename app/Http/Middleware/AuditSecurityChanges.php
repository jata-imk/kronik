<?php

namespace App\Http\Middleware;

use App\Enums\ActivityEvent;
use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditSecurityChanges
{
    public function handle(Request $request, Closure $next)
    {
        $routes = ['two-factor.enable', 'two-factor.confirm', 'two-factor.disable',
            'api-tokens.store', 'api-tokens.update', 'api-tokens.destroy', 'verification.verify',
            'current-team.update', 'other-browser-sessions.destroy'];
        $recovery = $request->route()?->getActionName() === \Laravel\Fortify\Http\Controllers\RecoveryCodeController::class.'@store';
        if ((! in_array($request->route()?->getName(), $routes, true) && ! $recovery) || ! $request->user()) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next) {
            $user = $request->user();
            $before = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $user->setRawAttributes($before->getAttributes(), true);
            $tokens = $before->tokens()->get()->keyBy('id');
            $teamId = $before->current_team_id;
            $branchId = $before->current_sucursal_id;
            $response = $next($request);
            $after = $before->fresh();
            $events = [];
            if ($request->route()->named('current-team.update') && $teamId !== $after->current_team_id) {
                app(ActivityLogService::class)->log(ActivityEvent::ActiveTeamChanged,
                    ActivityEvent::ActiveTeamChanged->label(), $after,
                    ['result' => 'applied', 'related' => ['type' => 'teams', 'id' => $after->current_team_id]],
                    $user, $teamId, $branchId, inheritActorContext: false);
            }
            if ($request->route()->named('other-browser-sessions.destroy') && $before->password !== $after->password) {
                $events[] = [ActivityEvent::OtherSessionsInvalidated, null];
            }
            if (! $before->two_factor_secret && $after->two_factor_secret) {
                $events[] = [ActivityEvent::TwoFactorEnabled, null];
            } elseif ($before->two_factor_secret && ! $after->two_factor_secret) {
                $events[] = [ActivityEvent::TwoFactorDisabled, null];
            } elseif ($before->two_factor_recovery_codes !== $after->two_factor_recovery_codes) {
                $events[] = [ActivityEvent::RecoveryCodesRegenerated, null];
            }
            if (! $before->two_factor_confirmed_at && $after->two_factor_confirmed_at) {
                $events[] = [ActivityEvent::TwoFactorConfirmed, null];
            }
            if (! $before->email_verified_at && $after->email_verified_at) {
                $events[] = [ActivityEvent::EmailVerified, null];
            }
            $afterTokens = $after->tokens()->get()->keyBy('id');
            foreach ($afterTokens as $id => $token) {
                if (! $tokens->has($id)) {
                    $events[] = [ActivityEvent::ApiTokenCreated, $id];
                } elseif (collect($tokens[$id]->abilities)->sort()->values()->all() !== collect($token->abilities)->sort()->values()->all()) {
                    $events[] = [ActivityEvent::ApiTokenUpdated, $id];
                }
            }
            foreach ($tokens->keys()->diff($afterTokens->keys()) as $id) {
                $events[] = [ActivityEvent::ApiTokenRevoked, $id];
            }
            foreach ($events as [$event, $tokenId]) {
                $metadata = ['result' => 'applied'];
                if ($tokenId !== null) {
                    $metadata['related'] = ['type' => 'personal_access_tokens', 'id' => $tokenId];
                }
                app(ActivityLogService::class)->log($event, $event->label(), $after, $metadata, $user,
                    $teamId, $branchId, inheritActorContext: false);
            }

            return $response;
        });
    }
}
