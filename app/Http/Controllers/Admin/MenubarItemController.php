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

    public function index(\Illuminate\Http\Request $request)
    {
        $request->validate(['selected_item_id' => 'nullable|integer|min:1', 'modo' => 'nullable|in:consulta,edicion']);
        $params = app(\App\Services\ListadoParametros::class);
        $match = fn ($q, $v) => $q->where('label', 'like', '%'.$v.'%');
        $filters = ['global' => fn ($q, $v) => $q->where(fn ($q) => $q->where(fn ($q) => $match($q, $v))
            ->orWhereHas('children', fn ($q) => $match($q, $v))
            ->orWhereHas('children.children', fn ($q) => $match($q, $v)))];
        $sorts = ['label' => 'label', 'sort_order' => 'sort_order'];
        $values = $params->resolver($request, $filters, $sorts);
        $query = MenubarItem::with(['modules', 'children.modules', 'children.children.modules'])->whereNull('parent_id');
        $page = $params->aplicar($query, $values, $filters, $sorts, 'sort_order')->paginate($values['por_pagina'])->withQueryString();

        return Inertia::render('Admin/MenubarItems/Index', [
            'modules' => fn () => Module::with('menubarItems')->get(),
            'menubarCatalogo' => $page->getCollection(),
            'menubarSeleccionado' => $request->filled('selected_item_id') ? MenubarItem::with(['modules', 'parent'])->findOrFail($request->integer('selected_item_id')) : null,
            'modoMenu' => $request->user()->can('update menubar-items') && $request->input('modo') === 'edicion' ? 'edicion' : 'consulta',
            'listado' => $params->metadata($page, $values),
        ]);
    }

    public function store(MenubarItemRequest $request)
    {
        app(\App\Services\MenubarItemService::class)->guardar(null, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Item creado');
    }

    public function update(MenubarItemRequest $request, MenubarItem $menubarItem)
    {

        app(\App\Services\MenubarItemService::class)->guardar($menubarItem, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Item actualizado');
    }

    public function destroy(MenubarItem $menubarItem)
    {
        app(\App\Services\MenubarItemService::class)->eliminar($menubarItem, request()->user());

        return redirect()->back()->with('success', 'Item eliminado');
    }
}
