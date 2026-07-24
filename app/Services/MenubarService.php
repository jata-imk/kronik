<?php

namespace App\Services;

use App\Models\Module;
use App\Models\MenubarItemModule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class MenubarService
{
    public function getMenuItems(Request $request): array
    {
        $route = Route::current();
        $routeName = $route?->getName();

        if (!$routeName) {
            return [];
        }

        $module = MenubarItemModule::query()
            ->whereJsonContains('routes', $routeName)
            ->with('module')
            ->get()
            ->pluck('module')
            ->filter()
            ->sortByDesc(fn (Module $candidate) => strlen((string) $candidate->route_name))
            ->first();

        $module ??= Module::query()
            ->get()
            ->filter(fn (Module $candidate) => $routeName === $candidate->route_name || str_starts_with($routeName, (string) $candidate->route_name.'.'))
            ->sortByDesc(fn (Module $candidate) => strlen((string) $candidate->route_name))
            ->first();

        if (!$module) {
            return [];
        }

        $moduleIndexRoute = $this->resolveModuleIndexRoute($module, $routeName);

        $module->load([
            'menubarItems' => function ($query) use ($module) {
                $query->where('parent_id', null)->with([
                    'menubarItemModules' => fn ($q) => $q->where('module_id', $module->id),
                    'children.menubarItemModules' => fn ($q) => $q->where('module_id', $module->id),
                    'children.children.menubarItemModules' => fn ($q) => $q->where('module_id', $module->id),
                    'children.children.children.menubarItemModules' => fn ($q) => $q->where('module_id', $module->id),
                ])->orderBy('sort_order');
            },
        ]);

        return array_values(array_filter(
            $this->buildMenu(
                $routeName,
                $moduleIndexRoute,
                $module->menubarItems,
                $request,
            ),
            fn (array $item) => (isset($item['items']) && count($item['items']) > 0) || isset($item['url']),
        ));
    }

    protected function buildMenu(
        string $currentRouteName,
        string $moduleIndexRoute,
        array|Collection $items,
        Request $request,
        mixed $parent = null,
    ): array {
        $menu = [];

        if (!$parent) {
            $menu[] = $currentRouteName === $moduleIndexRoute
                ? [
                    'label' => 'Inicio',
                    'icon' => 'pi pi-fw pi-home',
                    'url' => route('dashboard'),
                ]
                : [
                    'label' => 'Regresar',
                    'icon' => 'pi pi-arrow-left',
                    'iconPos' => 'left',
                    'url' => $request->headers->get('referer') ?? $this->buildRouteUrl($moduleIndexRoute, null, $request),
                ];
        }

        foreach ($items as $item) {
            $routes = $item->menubarItemModules->first()?->routes ?? [];
            $children = $item->children?->count()
                ? $this->buildMenu($currentRouteName, $moduleIndexRoute, $item->children, $request, $item)
                : [];

            $isAvailableOnRoute = in_array($currentRouteName, $routes, true);

            if (!$isAvailableOnRoute && !$children) {
                continue;
            }

            $url = $isAvailableOnRoute
                ? $this->resolveMenubarUrl($item, $request)
                : null;

            if (!$url && !$children) {
                continue;
            }

            $menuItem = ['label' => $item->label];

            if ($url) {
                $menuItem['icon'] = $item->icon;
                $menuItem['url'] = $url;
            }

            if ($children) {
                $menuItem['items'] = $children;
            }

            $menu[] = $menuItem;
        }

        return $menu;
    }

    private function resolveModuleIndexRoute(Module $module, string $currentRouteName): string
    {
        $configuredIndexRoute = $module->route_name ? $module->route_name.'.index' : null;

        if ($configuredIndexRoute && Route::has($configuredIndexRoute)) {
            return $configuredIndexRoute;
        }

        if (str_ends_with($currentRouteName, '.index')) {
            return $currentRouteName;
        }

        return MenubarItemModule::query()
            ->where('module_id', $module->id)
            ->get()
            ->flatMap(fn (MenubarItemModule $itemModule) => $itemModule->routes ?? [])
            ->first(fn (string $routeName) => str_ends_with($routeName, '.index') && Route::has($routeName))
            ?? $currentRouteName;
    }

    protected function resolveMenubarUrl($item, Request $request): ?string
    {
        if ($item->type === 'route:dynamic') {
            $conditions = json_decode($item->value);
            $default = collect($conditions)->firstWhere('condition_type', 'default');

            if (!$default) {
                return null;
            }

            foreach ($conditions as $condition) {
                if ($condition->condition_type !== 'route_regexp' || empty($condition->condition_value?->route_name)) {
                    continue;
                }

                $triggerRoute = Route::getRoutes()->getByName($condition->condition_value->route_name);
                if (!$triggerRoute) {
                    continue;
                }

                $subject = $condition->condition_value->pregmatch_subject_type === 'referer'
                    ? $request->headers->get('referer', '')
                    : $this->buildRouteUrl($default->route_name, $default->params ?? null, $request);

                if ($subject && preg_match($this->laravelPatternToRegex($triggerRoute->uri), $subject)) {
                    return $this->buildRouteUrl(
                        $condition->route_name ?? $default->route_name,
                        $condition->params ?? null,
                        $request,
                    );
                }
            }

            return $this->buildRouteUrl($default->route_name, $default->params ?? null, $request);
        }

        return match ($item->type) {
            'menu' => null,
            'route:static' => $item->value,
            'route:name' => $this->buildRouteUrl($item->value, $item->params, $request),
            'route:referer_fallback' => $request->headers->get('referer') ?? $this->buildRouteUrl($item->value, null, $request),
            default => route('dashboard'),
        };
    }

    private function buildRouteUrl(string $routeName, mixed $rawParams, Request $request): ?string
    {
        if (!Route::has($routeName)) {
            return null;
        }

        $params = is_string($rawParams) ? json_decode($rawParams, true) : $rawParams;
        $params = is_array($params) ? $params : [];

        foreach ($params as $key => $value) {
            $routeParam = $request->route($key);
            $resolved = is_object($routeParam) ? ($routeParam->id ?? null) : $routeParam;

            if ($value === '{'.$key.'}' && $resolved === null) {
                return null;
            }

            $params[$key] = is_string($value)
                ? str_replace('{'.$key.'}', (string) $resolved, $value)
                : $value;
        }

        try {
            return route($routeName, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function laravelPatternToRegex(string $pattern): string
    {
        $regex = preg_quote($pattern, '#');
        $regex = preg_replace('/\\\\\{[^}]+\\\\\}/', '[^/]+', $regex);

        return '#/'.$regex.'$#';
    }
}
