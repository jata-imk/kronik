<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenubarItemRequest;
use App\Models\MenubarItem;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Route as LaravelRoute;
use Inertia\Inertia;

class MenubarItemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:read menubar-items', only: ['index', 'show', 'availableRoutes']),
            new Middleware('permission:create menubar-items', only: ['create', 'store']),
            new Middleware('permission:update menubar-items', only: ['edit', 'update']),
            new Middleware('permission:delete menubar-items', only: ['destroy']),
        ];
    }

    public function availableRoutes(): JsonResponse
    {
        $routes = collect(LaravelRoute::getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter()
            ->sort()
            ->values();

        return response()->json($routes);
    }

    public function index()
    {
        return Inertia::render('Admin/MenubarItems/Index', [
            'modules' => fn () => Module::with('menubarItems')->get(),
            'menubarItems' => fn () => MenubarItem::with(
                [
                    'modules',
                    'children.modules',
                    'children.children.modules',
                ]
            )->whereNull('parent_id')->get(),
        ]);
    }

    public function store(MenubarItemRequest $request)
    {
        $fields = $request->validated();
        $fields['params'] = gettype($fields['params']) == 'string' && json_validate($fields['params']) ? json_decode($fields['params']) : $fields['params'];

        $menubarItem = MenubarItem::create($fields);

        if (
            isset($fields['modules'])
            && count($fields['modules'])
            && count(array_filter($fields['modules'], function ($module) {
                return isset($module['routes']);
            }))
        ) {
            $menubarItem->modules()->attach($fields['modules']);
        }

        return redirect()->back()->with('success', 'Item creado');
    }

    public function update(MenubarItemRequest $request, MenubarItem $menubarItem)
    {

        $fields = $request->validated();
        $fields['params'] = gettype($fields['params']) == 'string' && json_validate($fields['params']) ? json_decode($fields['params']) : $fields['params'];

        $menubarItem->update($fields);

        if (
            isset($fields['modules'])
            && count($fields['modules'])
            && count(array_filter($fields['modules'], function ($module) {
                return isset($module['routes']);
            }))
        ) {
            $menubarItem->modules()->sync($fields['modules']);
        }

        return redirect()->back()->with('success', 'Item actualizado');
    }

    public function destroy(MenubarItem $menubarItem)
    {
        $menubarItem->modules()->detach();
        $menubarItem->delete();

        return redirect()->back()->with('success', 'Item eliminado');
    }
}
