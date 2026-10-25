<?php

namespace App\Http\Middleware;

use App\Services\TeamContext;
use Closure;
use Illuminate\Http\Request;

class AuthorizeRoleTeam
{
    public function handle(Request $request, Closure $next)
    {
        $context = app(TeamContext::class);
        $team = $context->resolve($request);
        $action = match ($request->route()->getActionMethod()) {
            'store', 'create', 'createPermission' => 'create',
            'update', 'edit', 'assignPermission' => 'update',
            'destroy' => 'delete', default => 'read',
        };
        $context->authorize($request->user(), $team, "{$action} roles");

        return $next($request);
    }
}
