<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeamsPermission
{

    public function handle(Request $request, Closure $next): Response
    {
        if (!empty($request->user())) {
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($request->user()->current_team_id);

                $request->user()->load('roles.permissions');
            }
        }

        return $next($request);
    }
}
