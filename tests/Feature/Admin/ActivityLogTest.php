<?php

use Inertia\Testing\AssertableInertia as Assert;

test('super admin can inspect and export real activity logs', function () {
    $user = actingAsSuperAdmin();

    activity()
        ->causedBy($user)
        ->event('login')
        ->withProperties(['ip' => '127.0.0.1'])
        ->log('Inicio de sesion exitoso');

    $this->actingAs($user)
        ->get(route('admin.users.activity', ['event' => 'login']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Logs/UserActivity')
            ->where('activityLogs.data.0.event', 'login')
            ->where('activityLogs.data.0.causer.id', $user->id)
            ->where('activityLogs.data.0.ip', '127.0.0.1')
        );

    $this->actingAs($user)
        ->get(route('admin.users.activity.export', ['event' => 'login']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertSee('Inicio de sesion exitoso');
});
