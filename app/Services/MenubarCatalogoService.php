<?php

namespace App\Services;

use App\Models\MenubarItem;
use App\Models\Module;
use Illuminate\Support\Facades\Route;

class MenubarCatalogoService
{
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
