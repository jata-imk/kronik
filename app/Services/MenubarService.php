<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;

class MenubarService
{
    protected array $menuConfig;

    public function getMenuItems(Request $request): array
    {
        $route = Route::current();

        // TODO: Add this to the model in the future
        // $routeController = $route->getController();
        // $routeControllerClass = $route->getControllerClass();

        $routeAction = $route->getAction();
        $routeActionMethod = Str::parseCallback($routeAction['uses'])[1];
        $routeName = $route->getName();
        $routeNameWithoutAction = str_replace('.' . $routeActionMethod, '', $routeName);

        $modules = explode('.', $routeNameWithoutAction);
        $module = Module::where('route_name', $routeNameWithoutAction)->first();

        if (!$module) return [];

        $module->load([
            'menubarItems' => function ($query) use ($module) {
                $query->where('parent_id', null)->with([
                    'menubarItemModules' => function ($q) use ($module) {
                        $q->where('module_id', $module->id);
                    },
                    'children.menubarItemModules' => function ($query) use ($module) {
                        $query->where('module_id', $module->id);
                    },
                    'children.children.menubarItemModules' => function ($query) use ($module) {
                        $query->where('module_id', $module->id);
                    },
                    'children.children.children.menubarItemModules' => function ($query) use ($module) {
                        $query->where('module_id', $module->id);
                    }
                ])->orderBy('sort_order');
            }
        ]);

        return array_values(array_filter(
            $this->buildMenu(
                $routeNameWithoutAction,
                $module->menubarItems,
                $request,
                $routeActionMethod
            ),
            function ($item) {
                return isset($item['items']) && count($item['items']) > 0 || isset($item['url']);
            }
        ));
    }

    protected function buildMenu(string | array $modules, array | Collection $items, Request $request, string $action, $parent = null): array
    {
        $menu = [];
        $module = is_array($modules) ? implode('.', $modules) : $modules;

        if ($action === 'index' && !$parent) {
            $menu[] = [
                'label' => 'Inicio',
                'icon' => 'pi pi-fw pi-home',
                'url' => route('dashboard'),
            ];
        } else if (!$parent) {
            $menu[] = [
                'label' => 'Regresar',
                'icon' => 'pi pi-arrow-left',
                'iconPos' => 'left',
                'url' => ($request->headers->has('referer')) ? $request->headers->get('referer') : route($module . '.index'),
            ];
        }

        foreach ($items as $item) {
            $actions = $item->menubarItemModules->first()?->routes ?? [];

            if (!in_array($module . '.' . $action, $actions)) {
                continue;
            }

            $url = $this->resolveMenubarUrl($item, $request);

            if (!$url && (!($item->children && $item->children->count()))) {
                continue;
            }

            $menuItem = [
                'label' => $item->label,
            ];

            if ($url) {
                $menuItem['icon'] = $item->icon;
                $menuItem['url'] = $url;
            }

            if ($item->children && $item->children->count()) {
                $menuItem['items'] = $this->buildMenu($modules, $item->children, $request, $action, $item);
            }

            $menu[] = $menuItem;
        }

        return $menu;
    }

    function resolveMenubarUrl($item, Request $request)
    {
        if ($item->type == 'route:dynamic') {
            $conditionValues = json_decode($item->value);
            $conditionDefault = collect($conditionValues)->where('condition_type', 'default')->first();

            if ($conditionDefault) {
                foreach ($conditionValues as $condition) {
                    if ($condition->condition_type !== 'route_regexp') continue;

                    $subject = $condition->condition_value->pregmatch_subject_type === 'referer'
                        ? $request->headers->get('referer', '')
                        : route($conditionDefault->route_name);

                    $triggerRoute = Route::getRoutes()->getByName($condition->condition_value->route_name);
                    if (!$triggerRoute) continue;

                    if (preg_match($this->laravelPatternToRegex($triggerRoute->uri), $subject)) {
                        return $this->buildRouteUrl($condition->route_name ?? $conditionDefault->route_name, $condition->params ?? null, $request);
                    }
                }

                return $this->buildRouteUrl($conditionDefault->route_name, $conditionDefault->params ?? null, $request);
            }
        }

        switch ($item->type) {
            case 'menu':
                return null;
            case 'route:static':
                return $item->value;
            case 'route:name':
                return $this->buildRouteUrl($item->value, $item->params, $request);
            case 'route:referer_fallback':
                return $request->headers->get('referer') ?? route($item->value);
            default:
                return route('dashboard');
        }
    }

    private function buildRouteUrl(string $routeName, $rawParams, Request $request): string
    {
        $params = collect((array) ($rawParams ?? []))->mapWithKeys(function ($value, $key) use ($request) {
            $param = $request->route($key);
            $resolved = is_object($param) ? ($param->id ?? 'null') : ($param ?? 'null');
            return [$key => str_replace('{' . $key . '}', $resolved, $value)];
        })->toArray();

        return route($routeName, $params);
    }

    function laravelPatternToRegex(string $pattern): string
    {
        // Escapa los caracteres especiales y convierte {param} en [^/]+
        $regex = preg_quote($pattern, '#');

        // Reemplaza los parámetros tipo \{param\} por [^/]+
        $regex = preg_replace('/\\\\\{[^}]+\\\\\}/', '[^/]+', $regex);

        // Devuelve el regex listo para usar
        return '#/' . $regex . '$#';
    }
}
