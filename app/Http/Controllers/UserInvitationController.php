<?php

namespace App\Http\Controllers;

use App\Services\UserInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class UserInvitationController extends Controller
{
    public function show(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->query('email', ''), 'token' => $token, 'invitation' => true,
        ]);
    }

    public function store(Request $request, UserInvitationService $service)
    {
        $input = $request->validate([
            'token' => ['required', 'string'], 'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $input['email'] = Str::lower($input['email']);
        $status = $service->accept([...$input, 'password_confirmation' => $request->input('password_confirmation')]);
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'La invitación no es válida o ya venció. Solicita que te envíen una nueva.']);
        }

        return redirect()->route('login')->with('status', 'Tu cuenta está activa. Ya puedes iniciar sesión.');
    }
}
