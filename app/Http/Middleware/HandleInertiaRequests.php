<?php

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Sucursal;
use App\Services\MenubarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Middleware;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (! empty($request->user()) && function_exists('setPermissionsTeamId')) {
            // set actual team_id to spatie/laravel-permission package
            setPermissionsTeamId($request->user()->current_team_id);

            $request->user()->setRelation('permissions', $request->user()->getAllPermissions());
            $request->user()->load(['roles.permissions']);
            $request->user()->load(['sucursalPrincipal', 'currentSucursal']);
            $request->user()->setRelation(
                'sucursales',
                $request->user()->is_super_admin
                    ? Sucursal::query()->where('activa', true)->orderBy('nombre')->get()
                    : $request->user()->sucursales()->where('activa', true)->orderBy('nombre')->get(),
            );
        } elseif (empty($request->user())) {
            return parent::share($request);
        }

        // Evaluate the complete permission catalogue through Gate. Limiting the
        // catalogue to roles in the current team made global administrators lose
        // navigation entries after switching teams.
        $permissions = Permission::query()->orderBy('name')->get();

        return array_merge(parent::share($request), [
            'menubarItems' => function () use ($request) {
                try {
                    return app(MenubarService::class)->getMenuItems($request);
                } catch (\Throwable $e) {
                    return [];
                }
            },
            'menubarAdmin' => function () use ($request) {
                if (! $request->user()?->is_super_admin) {
                    return null;
                }

                return [
                    'modules' => Module::select(['id', 'name', 'route_name'])->get(),
                    'currentRouteName' => Route::current()?->getName(),
                ];
            },
            'jetstream' => [
                'canManageTwoFactorAuthentication' => Features::canManageTwoFactorAuthentication(),
                'canUpdatePassword' => Features::enabled(Features::updatePasswords()),
                'canUpdateProfileInformation' => Features::canUpdateProfileInformation(),
                'hasEmailVerification' => Features::enabled(Features::emailVerification()),
                'flash' => $request->session()->get('flash', []),
                'hasAccountDeletionFeatures' => Jetstream::hasAccountDeletionFeatures(),
                'hasApiFeatures' => Jetstream::hasApiFeatures(),
                'hasTeamFeatures' => Jetstream::hasTeamFeatures(),
                'hasTermsAndPrivacyPolicyFeature' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
                'managesProfilePhotos' => Jetstream::managesProfilePhotos(),
            ],
            'auth' => array_merge(
                Inertia::getShared()['auth'] ?? [],
                [
                    'permissions' => [
                        ...$permissions->map(function ($permission) use ($request) {
                            return [
                                'key' => str_replace(' ', '-', $permission->name),
                                'value' => Gate::check($permission->name, $request->user()),
                            ];
                        })->pluck('value', 'key'),
                    ],
                    'is_super_admin' => (bool) $request->user()->is_super_admin,
                ]
            ),
        ]);
    }
}
