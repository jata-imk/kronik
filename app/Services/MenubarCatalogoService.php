<?php

namespace App\Services;

use App\Models\MenubarItem;
use App\Models\Module;
use Illuminate\Support\Facades\Route;

class MenubarCatalogoService
{
    public function completarArbol(\Illuminate\Database\Eloquent\Collection $roots): \Illuminate\Database\Eloquent\Collection
    {
        $items = MenubarItem::with('modules')->orderBy('sort_order')->orderBy('id')->get()->groupBy(fn ($item) => $item->parent_id ?? 'root');
        $build = function ($item, array $seen = []) use (&$build, $items) {
            $node = clone $item;
            $node->setRelation('children', new \Illuminate\Database\Eloquent\Collection(
                collect($items->get($item->id, []))->reject(fn ($child) => in_array($child->id, [...$seen, $item->id], true))
                    ->map(fn ($child) => $build($child, [...$seen, $item->id]))->all()
            ));

            return $node;
        };

        return $roots->map(fn ($root) => $build($root));
    }

    public function raicesCoincidentes(string $search): array
    {
        $parents = MenubarItem::pluck('parent_id', 'id');
        $roots = [];
        foreach (MenubarItem::where('label', 'like', '%'.$search.'%')->pluck('id') as $id) {
            $seen = [];
            while (isset($parents[$id]) && ! in_array($id, $seen, true)) {
                $seen[] = $id;
                $id = $parents[$id];
            }
            if (! in_array($id, $seen, true)) {
                $roots[] = $id;
            }
        }

        return array_values(array_unique($roots));
    }

    public function acciones(): array
    {
        $actions = [];
        foreach (config('menubar') as $module => [$group, $pages]) {
            foreach ($pages as $name => $label) {
                $route = Route::getRoutes()->getByName($name);
                if (! $route || ! in_array('GET', $route->methods(), true) || (isset($actions[$name]) && ! str_starts_with($name, $module.'.'))) {
                    continue;
                }
                $required = array_values(array_filter($route->parameterNames(), fn ($key) => ! str_contains($route->uri(), '{'.$key.'?}')));
                $actions[$name] = [
                    'key' => $name, 'label' => $label, 'group' => $group, 'module' => $module,
                    'required' => $required,
                    'params' => $required ? array_combine($required, array_map(fn ($key) => '{'.$key.'}', $required)) : null,
                ];
            }
        }

        return array_values($actions);
    }

    public function pantallas(): array
    {
        $modules = Module::all()->keyBy('name');

        return collect($this->acciones())->map(function ($action) use ($modules) {
            $module = $modules->get($action['module']);

            return ['key' => $action['key'], 'label' => $action['group'].' · '.$action['label'], 'module_id' => $module?->id, 'required' => $action['required']];
        })->filter(fn ($screen) => $screen['module_id'] !== null)->values()->all();
    }

    public function datos(bool $includeGroups = true): array
    {
        return [
            'acciones' => $this->acciones(),
            'pantallas' => $this->pantallas(),
            'grupos' => $includeGroups ? MenubarItem::where('type', 'menu')->orderBy('sort_order')->orderBy('id')->get(['id', 'label', 'parent_id'])->toArray() : [],
        ];
    }
}
