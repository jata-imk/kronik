<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\MenubarItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class MenubarItemService
{
    public function guardar(?MenubarItem $item, array $fields, User $actor): MenubarItem
    {
        $previous = getPermissionsTeamId();
        try {
            setPermissionsTeamId($actor->current_team_id);
            $actor->unsetRelation('roles')->unsetRelation('permissions');
            Gate::forUser($actor)->authorize($item ? 'update menubar-items' : 'create menubar-items');

            return DB::transaction(function () use ($item, $fields, $actor) {
                MenubarItem::orderBy('id')->lockForUpdate()->get(['id']);
                $creating = $item === null;
                if ($item) {
                    $item = MenubarItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
                }
                $modules = $fields['modules'] ?? [];
                $this->validar($fields, $item);
                unset($fields['modules']);
                if (is_string($fields['params'] ?? null) && json_validate($fields['params'])) {
                    $fields['params'] = json_decode($fields['params'], true);
                }
                $item ??= new MenubarItem;
                $beforeModules = $item->menubarItemModules()->orderBy('module_id')->get(['module_id', 'routes'])->toArray();
                $item->fill($fields);
                $changed = array_keys($item->getDirty());
                $item->save();
                $item->modules()->sync($modules);
                if ($beforeModules !== $item->menubarItemModules()->orderBy('module_id')->get(['module_id', 'routes'])->toArray()) {
                    $changed[] = 'modules';
                }
                if (! $creating && $changed === []) {
                    return $item;
                }
                app(ActivityLogService::class)->log(
                    $creating ? ActivityEvent::MenubarItemCreated : ActivityEvent::MenubarItemUpdated,
                    $creating ? 'Elemento de menú creado' : 'Elemento de menú actualizado',
                    $item, ['changed_fields' => $changed, 'result' => $creating ? 'created' : 'applied'], $actor,
                );

                return $item;
            });
        } finally {
            setPermissionsTeamId($previous);
            $actor->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    public function eliminar(MenubarItem $item, User $actor): void
    {
        $previous = getPermissionsTeamId();
        try {
            setPermissionsTeamId($actor->current_team_id);
            $actor->unsetRelation('roles')->unsetRelation('permissions');
            Gate::forUser($actor)->authorize('delete menubar-items');
            DB::transaction(function () use ($item, $actor) {
                MenubarItem::orderBy('id')->lockForUpdate()->get(['id']);
                $item = MenubarItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
                if ($item->children()->exists()) {
                    throw ValidationException::withMessages(['parent_id' => 'Mueve o elimina los elementos de este grupo antes de eliminarlo.']);
                }
                $item->modules()->detach();
                $item->delete();
                app(ActivityLogService::class)->log(ActivityEvent::MenubarItemDeleted,
                    'Elemento de menú eliminado', $item, ['result' => 'deleted'], $actor);
            });
        } finally {
            setPermissionsTeamId($previous);
            $actor->unsetRelation('roles')->unsetRelation('permissions');
        }
    }

    public function validar(array $fields, ?MenubarItem $item = null): void
    {
        $fail = fn ($key, $message) => throw ValidationException::withMessages([$key => $message]);
        $parentId = $fields['parent_id'] ?? null;
        $visited = $item ? [$item->id] : [];
        while ($parentId !== null) {
            if (in_array((int) $parentId, $visited, true)) {
                $fail('parent_id', 'Un grupo no puede contenerse a sí mismo ni a uno de sus grupos superiores.');
            }
            $parent = MenubarItem::find($parentId);
            if (! $parent || ($parent->type !== 'menu' && (int) $parentId !== (int) $item?->parent_id)) {
                $fail('parent_id', 'Selecciona un grupo de menú disponible.');
            }
            $visited[] = (int) $parentId;
            $parentId = $parent->parent_id;
        }
        $screens = collect(app(MenubarCatalogoService::class)->pantallas())->keyBy('key');
        foreach ($fields['modules'] ?? [] as $moduleId => $link) {
            if (! \App\Models\Module::whereKey($moduleId)->exists()) {
                $fail('modules', 'Selecciona un módulo disponible.');
            }
            foreach ($link['routes'] ?? [] as $screen) {
                $legacy = $item?->menubarItemModules()->where('module_id', $moduleId)->first()?->routes ?? [];
                if ((! isset($screens[$screen]) || (int) $screens[$screen]['module_id'] !== (int) $moduleId) && ! in_array($screen, $legacy, true)) {
                    $fail('modules', 'Selecciona una pantalla válida para el módulo indicado.');
                }
            }
        }
        $type = $fields['type'] ?? $item?->type;
        $value = $fields['value'] ?? null;
        $params = $fields['params'] ?? null;
        $decoded = is_string($params) && json_validate($params) ? json_decode($params, true) : $params;
        $unchanged = $item && $item->type === $type && $item->value === $value && $item->params == $decoded;
        if ($unchanged || $type === 'menu') {
            return;
        }
        if ($params !== null && $params !== '' && (! is_string($params) || ! json_validate($params) || ! is_array(json_decode($params, true)))) {
            $fail('params', 'Los parámetros deben ser un objeto JSON válido.');
        }
        $supported = array_column(app(MenubarCatalogoService::class)->acciones(), 'key');
        $routeValid = fn ($name) => is_string($name) && in_array($name, $supported, true);
        if (in_array($type, ['route:name', 'route:referer_fallback'], true) && ! $routeValid($value)) {
            $fail('value', 'Selecciona una página de destino disponible.');
        }
        if ($type === 'route:static' && ! app(MenubarDestinoService::class)->urlSegura($value)) {
            $fail('value', 'Escribe una dirección web segura o una ruta que comience con /.');
        }
        if ($type === 'route:static' && (str_starts_with($value, '/') || parse_url($value, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST))) {
            try {
                $destination = Route::getRoutes()->match(\Illuminate\Http\Request::create($value));
                $supportedDestination = $routeValid($destination->getName());
            } catch (\Throwable) {
                $supportedDestination = false;
            }
            if (! $supportedDestination) {
                $fail('value', 'Selecciona un destino interno incluido en el catálogo de acciones.');
            }
        }
        if ($type === 'route:dynamic') {
            $conditions = is_string($value) && json_validate($value) ? json_decode($value, true) : null;
            if (! is_array($conditions) || ! collect($conditions)->contains(fn ($condition) => ($condition['condition_type'] ?? null) === 'default')) {
                $fail('value', 'La configuración condicional necesita un destino predeterminado válido.');
            }
            foreach ($conditions as $condition) {
                if (! is_array($condition) || ! in_array($condition['condition_type'] ?? null, ['default', 'route_regexp'], true)
                    || ! $routeValid($condition['route_name'] ?? null)
                    || (($condition['condition_type'] ?? null) === 'route_regexp' && (! $routeValid($condition['condition_value']['route_name'] ?? null)
                        || ! in_array($condition['condition_value']['pregmatch_subject_type'] ?? null, ['referer', 'route'], true)))) {
                    $fail('value', 'Revisa los destinos y las condiciones de la configuración avanzada.');
                }
            }
        }
    }
}
