<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenubarItemRequest;
use App\Models\MenubarItem;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
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
        $routes = collect(app(\App\Services\MenubarCatalogoService::class)->acciones())->pluck('key')->sort()->values();

        return response()->json($routes);
    }

    public function catalogo(\Illuminate\Http\Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['read menubar-items', 'create menubar-items', 'update menubar-items']), 403);

        return response()->json(app(\App\Services\MenubarCatalogoService::class)->datos($request->user()->can('read menubar-items')))
            ->header('Cache-Control', 'private, no-store');
    }

    public function previsualizacion(\Illuminate\Http\Request $request): JsonResponse
    {
        Gate::authorize('read menubar-items');
        $data = $request->validate([
            'screen' => ['required', \Illuminate\Validation\Rule::in(array_column(app(\App\Services\MenubarCatalogoService::class)->pantallas(), 'key'))],
            'selected_item_id' => 'nullable|integer|exists:menubar_items,id', 'draft' => 'nullable|array',
        ], [], ['screen' => 'pantalla de vista previa', 'selected_item_id' => 'elemento seleccionado', 'draft' => 'configuración de vista previa']);
        $item = isset($data['selected_item_id']) ? MenubarItem::findOrFail($data['selected_item_id']) : null;
        if (isset($data['draft'])) {
            Gate::authorize($item ? 'update menubar-items' : 'create menubar-items');
            $form = new MenubarItemRequest;
            $draft = \Illuminate\Support\Facades\Validator::make($data['draft'], $form->rules(), [], $form->attributes())->validate();
            app(\App\Services\MenubarItemService::class)->validar($draft, $item);
        } else {
            Gate::authorize('read menubar-items');
            $draft = null;
        }
        $context = \Illuminate\Http\Request::create('/');
        $context->setUserResolver(fn () => $request->user());
        $route = clone LaravelRoute::getRoutes()->getByName($data['screen']);
        $route->bind($context);
        $context->setRouteResolver(fn () => $route);

        return response()->json(['items' => app(\App\Services\MenubarService::class)->getMenuItems($context, $draft, $item?->id, true),
            'message' => 'Vista previa sin guardar. Las acciones no se ejecutan; las que necesitan un registro se habilitan al abrirlo.'])
            ->header('Cache-Control', 'private, no-store');
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $request->validate(['selected_item_id' => 'nullable|integer|min:1', 'modo' => 'nullable|in:consulta,edicion']);
        $params = app(\App\Services\ListadoParametros::class);
        $catalogo = app(\App\Services\MenubarCatalogoService::class);
        $filters = ['global' => fn ($q, $v) => $q->whereIn('id', $catalogo->raicesCoincidentes($v))];
        $sorts = ['label' => 'label', 'sort_order' => 'sort_order'];
        $values = $params->resolver($request, $filters, $sorts);
        $query = MenubarItem::with('modules')->whereNull('parent_id');
        $page = $params->aplicar($query, $values, $filters, $sorts, 'sort_order')->paginate($values['por_pagina'])->withQueryString();

        return Inertia::render('Admin/MenubarItems/Index', [
            'modules' => fn () => Module::with('menubarItems')->get(),
            'menubarCatalogo' => $catalogo->completarArbol($page->getCollection()),
            'catalogoMenu' => fn () => $catalogo->datos(),
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
