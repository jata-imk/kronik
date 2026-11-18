<?php

namespace App\Services;

use App\Models\MenubarItem;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class MenubarService
{
    public function getMenuItems(Request $request, ?array $draft = null, ?int $selectedId = null, bool $preview = false): array
    {
        $actor = $request->user();
        if (! $actor || ! ($name = $request->route()?->getName())) {
            return [];
        }
        $previous = getPermissionsTeamId();
        try {
            setPermissionsTeamId($actor->current_team_id);
            $actor->unsetRelation('roles')->unsetRelation('permissions');

            return $this->construir($request, $name, $draft, $selectedId, $preview);
        } finally {
            setPermissionsTeamId($previous);
            $actor->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    private function construir(Request $request, string $name, ?array $draft, ?int $selectedId, bool $preview): array
    {
        $items = MenubarItem::with('menubarItemModules')->orderBy('sort_order')->orderBy('id')->get();
        if ($draft !== null) {
            $items = $items->reject(fn ($item) => $item->id === $selectedId)->values();
            $item = new MenubarItem($draft);
            $item->id = $selectedId ?? -1;
            $item->setRelation('menubarItemModules', new \Illuminate\Database\Eloquent\Collection(
                collect($draft['modules'] ?? [])->map(fn ($link, $moduleId) => new \App\Models\MenubarItemModule(['module_id' => $moduleId, 'routes' => $link['routes'] ?? []]))->values()->all()
            ));
            $items->push($item);
        }
        $moduleId = collect(app(MenubarCatalogoService::class)->pantallas())->firstWhere('key', $name)['module_id'] ?? null;
        $module = $moduleId ? Module::find($moduleId) : Module::all()
            ->filter(fn ($module) => $name === $module->route_name || str_starts_with($name, $module->route_name.'.'))
            ->sortByDesc(fn ($module) => strlen($module->route_name))->first();
        if (! $module) {
            return [];
        }
        $byParent = $items->sortBy(fn ($item) => sprintf('%010d:%010d', $item->sort_order, $item->id))->groupBy(fn ($item) => $item->parent_id ?? 'root');
        $destinations = app(MenubarDestinoService::class);
        $build = function ($parent, array $ancestors = []) use (&$build, $byParent, $module, $request, $name, $destinations, $preview) {
            $result = [];
            foreach ($byParent->get($parent, []) as $item) {
                if (in_array($item->id, $ancestors, true)) {
                    continue;
                }
                $children = $build($item->id, [...$ancestors, $item->id]);
                $link = $item->menubarItemModules->firstWhere('module_id', $module->id);
                $active = in_array($name, $link?->routes ?? [], true);
                $node = ['key' => 'item:'.$item->id, 'kind' => $children || $item->type === 'menu' ? 'group' : 'action', 'label' => $item->label, 'icon' => $item->icon];
                if ($children) {
                    $node['items'] = $children;
                } elseif ($active && $item->type !== 'menu') {
                    $url = $this->resolveMenubarUrl($item, $request);
                    if ($url !== null) {
                        $node['url'] = $preview ? null : $url;
                        $node['preview'] = $preview;
                $node['native'] = (bool) preg_match('~/documentos-generados/[^/]+/ver(?:[?#]|$)~', $url);
                        $node['external'] = parse_url($url, PHP_URL_HOST) !== null && parse_url($url, PHP_URL_HOST) !== $request->getHost();
                    } elseif ($item->type === 'route:name' && $this->faltaContexto($item, $request)
                        && $destinations->sinContexto((string) $item->value, $request->user())) {
                        $node['disabled'] = true;
                        $node['reason'] = 'Abre el registro correspondiente para usar esta acción.';
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
                $result[] = $node;
            }

            return $result;
        };
        $menu = $build('root');
        if ($preview) {
            return $menu;
        }
        $index = $module->route_name.'.index';
        if (! Route::has($index)) {
            $index = collect(app(MenubarCatalogoService::class)->pantallas())->first(fn ($screen) => $screen['module_id'] === $module->id && str_ends_with($screen['key'], '.index'))['key'] ?? $name;
        }
        $home = $name === $index || $name === 'dashboard' || str_ends_with($name, '.index');
        array_unshift($menu, [
            'key' => 'system:'.($home ? 'home' : 'back'), 'kind' => 'system',
            'label' => $home ? 'Inicio' : 'Regresar', 'icon' => $home ? 'pi pi-home' : 'pi pi-arrow-left',
            'url' => $home ? route('dashboard') : ($this->referer($request) ?? $this->buildRouteUrl($index, null, $request) ?? route('dashboard')),
        ]);

        return $menu;
    }

    private function faltaContexto(MenubarItem $item, Request $request): bool
    {
        $route = Route::getRoutes()->getByName((string) $item->value);
        if (! $route) {
            return false;
        }
        $params = $this->parametros($item->params, $request);

        return collect($route->parameterNames())->contains(fn ($key) => ! str_contains($route->uri(), '{'.$key.'?}') && empty($params[$key]) && $request->route($key) === null);
    }

    protected function resolveMenubarUrl($item, Request $request): ?string
    {
        if ($item->type === 'route:dynamic') {
            $conditions = json_decode((string) $item->value, true);
            if (! is_array($conditions)) {
                return null;
            }
            $default = collect($conditions)->firstWhere('condition_type', 'default');
            if (! $default || empty($default['route_name'])) {
                return null;
            }
            foreach ($conditions as $condition) {
                if (($condition['condition_type'] ?? null) !== 'route_regexp') {
                    continue;
                }
                $trigger = Route::getRoutes()->getByName($condition['condition_value']['route_name'] ?? '');
                $subject = ($condition['condition_value']['pregmatch_subject_type'] ?? null) === 'referer'
                    ? $this->referer($request) : $this->buildRouteUrl($default['route_name'], $default['params'] ?? null, $request);
                if ($trigger && $subject && preg_match($this->laravelPatternToRegex($trigger->uri()), parse_url($subject, PHP_URL_PATH))) {
                    return $this->buildRouteUrl($condition['route_name'] ?? $default['route_name'], $condition['params'] ?? null, $request);
                }
            }

            return $this->buildRouteUrl($default['route_name'], $default['params'] ?? null, $request);
        }

        return match ($item->type) {
            'menu' => null,
            'route:static' => $this->staticUrl($item->value, $request),
            'route:name' => $this->buildRouteUrl((string) $item->value, $item->params, $request),
            'route:referer_fallback' => $this->referer($request) ?? $this->buildRouteUrl((string) $item->value, null, $request),
            default => null,
        };
    }

    private function parametros(mixed $raw, Request $request): array
    {
        $params = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($params)) {
            return [];
        }
        foreach ($params as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            $params[$key] = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($match) use ($request) {
                $resource = $request->route($match[1]);

                return is_object($resource) ? (string) ($resource->getRouteKey() ?? '') : (string) ($resource ?? '');
            }, $value);
        }

        return $params;
    }

    private function buildRouteUrl(string $name, mixed $raw, Request $request): ?string
    {
        $route = Route::getRoutes()->getByName($name);
        if (! $route || ! in_array('GET', $route->methods(), true)) {
            return null;
        }
        $params = $this->parametros($raw, $request);
        foreach ($route->parameterNames() as $key) {
            if (! array_key_exists($key, $params) && $request->route($key) !== null) {
                $value = $request->route($key);
                $params[$key] = is_object($value) ? $value->getRouteKey() : $value;
            }
            if (! str_contains($route->uri(), '{'.$key.'?}') && empty($params[$key])) {
                return null;
            }
        }
        if ($name === 'solicitudes.create' && $request->route('cliente') !== null) {
            $cliente = $request->route('cliente');
            $params['cliente_id'] = is_object($cliente) ? $cliente->getRouteKey() : $cliente;
        }
        if (! app(MenubarDestinoService::class)->permitido($name, $params, $request->user())) {
            return null;
        }

        try {
            return route($name, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    private function referer(Request $request): ?string
    {
        $url = $request->headers->get('referer');

        return $url && parse_url($url, PHP_URL_HOST) === $request->getHost() ? $this->staticUrl($url, $request) : null;
    }

    private function staticUrl(?string $url, Request $request): ?string
    {
        if (! app(MenubarDestinoService::class)->urlSegura($url)) {
            return null;
        }
        if (str_starts_with($url, '/') || parse_url($url, PHP_URL_HOST) === $request->getHost()) {
            try {
                $destination = Request::create($url);
                $route = Route::getRoutes()->match($destination);
                $params = [...$destination->query(), ...$route->parameters()];

                return $route->getName() && app(MenubarDestinoService::class)->permitido($route->getName(), $params, $request->user()) ? $url : null;
            } catch (\Throwable) {
                return null;
            }
        }

        return $url;
    }

    protected function laravelPatternToRegex(string $pattern): string
    {
        return '#^/'.preg_replace('/\\\\\{[^}]+\\\\\}/', '[^/]+', preg_quote($pattern, '#')).'$#';
    }
}
