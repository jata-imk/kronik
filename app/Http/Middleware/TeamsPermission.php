<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamsPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $previous = getPermissionsTeamId();
        $user = $request->user();
        try {
            setPermissionsTeamId($user?->current_team_id);
            $user?->unsetRelation('roles')->unsetRelation('permissions');

            $response = $next($request);
            if ($user) {
                $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
            }

            return $response;
        } finally {
            setPermissionsTeamId($previous);
            $user?->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
