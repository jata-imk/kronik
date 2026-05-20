<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        if (!empty($request->user()) && function_exists('setPermissionsTeamId')) {
            // set actual team_id to spatie/laravel-permission package
            setPermissionsTeamId($request->user()->current_team_id);

            $request->user()->setRelation('permissions', $request->user()->getAllPermissions());
            $request->user()->load(['roles.permissions']);
        } else if (empty($request->user())) {
            return parent::share($request);
        }

        $roleModel = config('permission.models.role');
        $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');
        $teamRolesPermissions = $roleModel::where($teamsKey, $request->user()->current_team_id)->with('permissions')->get()->pluck('permissions')->flatten();

        // TODO: Deshacer la relacion de permissions ya que sobrecarga el objeto user
        // para lo anterior hay que guardar el arreglo que se devuelve en una variable
        // y luego investigar como deshacer la relacion
        return array_merge(parent::share($request), [
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
                        ...$teamRolesPermissions->map(function ($permission) use ($request) {
                            return [
                                "key" => str_replace(' ', '-', $permission->name),
                                "value" => Gate::check($permission->name, $request->user()),
                            ];
                        })->pluck("value", "key"),
                    ],
                ]
            ),
        ]);
    }
}
