<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class MenubarService
{
    protected array $menuConfig;

    public function getMenuItems(Request $request): array
    {
        $route = Route::current();
        $actionMethod = $route->getActionMethod();
        $routeName = $route->getName();
        $module = explode('.', $routeName)[0]; // 'clientes', 'creditos', etc.

        $mod = Module::where('name', $module)->first();
        $mod->load([
            'menubarItems' => function ($query) use ($mod) {
                $query->where('parent_id', null)->with([
                    'children.menubarItemModules' => function ($query) use ($mod) {
                        $query->where('module_id', $mod->id);
                    },
                    'children.children.menubarItemModules' => function ($query) use ($mod) {
                        $query->where('module_id', $mod->id);
                    },
                    'children.children.children.menubarItemModules' => function ($query) use ($mod) {
                        $query->where('module_id', $mod->id);
                    }
                ])->orderBy('sort_order');
            }
        ]);

        if (!$mod) return [];

        return array_filter($this->buildMenu($module, $mod->menubarItems, $request, $actionMethod), function ($item) {
            return isset($item['items']) && count($item['items']) > 0 || isset($item['url']);
        });
    }

    protected function buildMenu(string $module, array | Collection $items, Request $request, string $action, $parent = null): array
    {
        $menu = [];

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
            $actions = $item->menubarItemModule->routes ?? $item->menubarItemModules[0]->routes ?? [];

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
                $menuItem['items'] = $this->buildMenu($module, $item->children, $request, $action, $item);
            }

            $menu[] = $menuItem;
        }

        return $menu;
    }

    function resolveMenubarUrl($item, Request $request)
    {
        if ($item->type == 'route:dynamic') {
            $conditionValues = json_decode($item->value);


            $conditionDefault = collect($conditionValues)->where('condition_type', 'default')->first() ?? $conditionValues->where('condition_type', 'default')->first();
            foreach ($conditionValues as $condition) {
                if ($condition->condition_type == 'route_regexp') {
                    $preg_match_subject = $condition->condition_value->pregmatch_subject_type == 'referer' ? $request->headers->get('referer') : route($conditionDefault->route_name);
                    if (!preg_match($this->laravelPatternToRegex(Route::getRoutes()->getByName($condition->condition_value->route_name)->uri), $preg_match_subject)) {
                        $params = collect($conditionDefault->params ?? [])->mapWithKeys(function ($value, $key) use ($request) {
                            return [$key => str_replace('{' . $key . '}', $request->route($key)->id, $value)];
                        })->toArray();

                        return route($conditionDefault->route_name, $params);
                    }
                }
            }
        }

        switch ($item->type) {
            case 'menu':
                return null;
            case 'route:static':
                return $item->value;

            case 'route:name':
                $params = collect($item->params ?? [])->mapWithKeys(function ($value, $key) use ($request) {
                    return [$key => str_replace('{' . $key . '}', $request->route($key)->id ?? '', $value)];
                })->toArray();


                return route($item->value, $params);

            case 'route:referer_fallback':
                return $request->headers->get('referer') ?? route($item->value);

            default:
                return route('dashboard');
        }
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
