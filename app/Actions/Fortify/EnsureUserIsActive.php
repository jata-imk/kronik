<?php

namespace App\Actions\Fortify;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isActive()) {
            return $next($request);
        }

        Auth::guard(config('fortify.guard'))->logout();

        throw ValidationException::withMessages([
            Fortify::username() => 'Tu cuenta no está activa. Solicita ayuda a un administrador.',
        ]);
    }
}
