<?php

namespace App\Providers;

use App\Actions\Jetstream\AddTeamMember;
use App\Actions\Jetstream\CreateTeam;
use App\Actions\Jetstream\DeleteTeam;
use App\Actions\Jetstream\DeleteUser;
use App\Actions\Jetstream\InviteTeamMember;
use App\Actions\Jetstream\RemoveTeamMember;
use App\Actions\Jetstream\UpdateTeamName;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Jetstream::createTeamsUsing(CreateTeam::class);                 // spatie/laravel-permission: Validated ✅
        Jetstream::updateTeamNamesUsing(UpdateTeamName::class);         // spatie/laravel-permission: Validated ✅
        Jetstream::addTeamMembersUsing(AddTeamMember::class);           // spatie/laravel-permission: Validated ✅
        Jetstream::inviteTeamMembersUsing(InviteTeamMember::class);     // spatie/laravel-permission: Validated ✅
        Jetstream::removeTeamMembersUsing(RemoveTeamMember::class);     // spatie/laravel-permission: Pending 🕑
        Jetstream::deleteTeamsUsing(DeleteTeam::class);                 // spatie/laravel-permission: Pending 🕑
        Jetstream::deleteUsersUsing(DeleteUser::class);                 // spatie/laravel-permission: Pending 🕑
    }
}
