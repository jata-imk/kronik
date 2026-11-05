<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Services\AccessAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;

class TwoFactorSessionController extends TwoFactorAuthenticatedSessionController
{
    public function store(TwoFactorLoginRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $user = $request->challengedUser();
                $locked = $user->newQuery()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                // Fortify memoizes this object: validate the material read under lock.
                $user->setRawAttributes($locked->getAttributes(), true);
                if (! $user->isActive()) {
                    app(AccessAuditService::class)->anonymous(ActivityEvent::LoginRejected);
                    $request->session()->forget(['login.id', 'login.remember']);

                    // Return a response so the anonymous rejection survives this transaction.
                    return back()->withErrors(['email' => 'Tu cuenta no está activa. Solicita ayuda a un administrador.']);
                }
                $response = parent::store($request);
                if ($this->guard->check()) {
                    app(AccessAuditService::class)->authenticated(ActivityEvent::TwoFactorCompleted, $user);
                } else {
                    app(AccessAuditService::class)->anonymous(ActivityEvent::TwoFactorFailed);
                }

                return $response;
            });
        } catch (ValidationException $exception) {
            app(AccessAuditService::class)->anonymous(ActivityEvent::TwoFactorFailed);
            throw $exception;
        }
    }
}
