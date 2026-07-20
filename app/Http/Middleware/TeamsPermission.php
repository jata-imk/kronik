<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamsPermission
{

    public function handle(Request $request, Closure $next): Response
    {
        if (!empty($request->user()) && function_exists('setPermissionsTeamId')) {
            // set actual team_id to spatie/laravel-permission package
            setPermissionsTeamId($request->user()->current_team_id);

            $request->user()->setRelation('permissions', $request->user()->getAllPermissions());
            $request->user()->load(['roles.permissions']);
        }

        return $next($request);
    }
}
