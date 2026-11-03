<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\MenubarItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MenubarItemService
{
    public function guardar(?MenubarItem $item, array $fields, User $actor): MenubarItem
    {
        Gate::forUser($actor)->authorize($item ? 'update menubar-items' : 'create menubar-items');

        return DB::transaction(function () use ($item, $fields, $actor) {
            $creating = $item === null;
            $modules = $fields['modules'] ?? [];
            unset($fields['modules']);
            if (is_string($fields['params'] ?? null) && json_validate($fields['params'])) {
                $fields['params'] = json_decode($fields['params'], true);
            }
            $item ??= new MenubarItem;
            $item->fill($fields)->save();
            if (count($modules) && count(array_filter($modules, fn ($module) => isset($module['routes'])))) {
                $item->modules()->sync($modules);
            }
            app(ActivityLogService::class)->log(
                $creating ? ActivityEvent::MenubarItemCreated : ActivityEvent::MenubarItemUpdated,
                $creating ? 'Elemento de menú creado' : 'Elemento de menú actualizado',
                $item, ['changed_fields' => [...array_keys($fields), 'modules'], 'result' => $creating ? 'created' : 'applied'], $actor,
            );

            return $item;
        });
    }

    public function eliminar(MenubarItem $item, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete menubar-items');
        DB::transaction(function () use ($item, $actor) {
            $item->modules()->detach();
            $item->delete();
            app(ActivityLogService::class)->log(ActivityEvent::MenubarItemDeleted,
                'Elemento de menú eliminado', $item, ['result' => 'deleted'], $actor);
        });
    }
}
