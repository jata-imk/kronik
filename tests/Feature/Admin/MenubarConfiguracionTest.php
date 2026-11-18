<?php

use App\Models\Cliente;
use App\Models\MenubarItem;
use App\Models\Module;
use App\Models\User;
use App\Services\MenubarItemService;
use Database\Seeders\ModulesAndPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(ModulesAndPermissionsSeeder::class);
    $this->actor = actingAsSuperAdmin();
    $this->actingAs($this->actor);
    $this->module = Module::where('name', 'clientes')->sole();
});

function menuFields($module, array $extra = []): array
{
    return [...['label' => 'Acción sintética', 'type' => 'route:name', 'value' => 'clientes.index', 'params' => '',
        'parent_id' => null, 'sort_order' => 0, 'modules' => [$module->id => ['routes' => ['clientes.index']]]], ...$extra];
}

test('control contextual respeta lectura y creación sin exigir superadministrador', function () {
    $reader = User::factory()->withPersonalTeam()->create();
    $reader->forceFill(['current_team_id' => $reader->ownedTeams()->first()->id])->save();
    grantTeamPermissions($reader, $reader->currentTeam, ['read clientes', 'create menubar-items']);
    $this->actingAs($reader)->get(route('clientes.index'))->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
        ->where('menubarAdmin.canRead', false)->where('menubarAdmin.canCreate', true)->where('menubarAdmin.currentRouteName', 'clientes.index')
        ->where('menubarEstado.status', 'empty'));
    setPermissionsTeamId($reader->current_team_id);
    $reader->revokePermissionTo('create menubar-items');
    $reader->unsetRelation('permissions')->unsetRelation('roles');
    $this->get(route('clientes.index'))->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->where('menubarAdmin', null));
});

test('fallo de resolución se distingue de menú vacío sin exponer el error interno', function () {
    $this->mock(\App\Services\MenubarService::class, fn ($mock) => $mock->shouldReceive('getMenuItems')->once()->andThrow(new \RuntimeException('SECRET-ERROR')));
    $this->get(route('clientes.index'))->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
        ->has('menubarItems', 0)->where('menubarEstado.status', 'unavailable')
        ->where('menubarEstado.message', 'No pudimos cargar las acciones de esta pantalla. Inténtalo de nuevo.'));
});

test('catálogo ofrece pantallas humanas sin escrituras ni búsquedas JSON', function () {
    $this->getJson(route('admin.menubar-items.catalogo'))->assertOk()
        ->assertJsonFragment(['key' => 'clientes.show', 'label' => 'Clientes · Información del cliente', 'module_id' => $this->module->id, 'required' => ['cliente']])
        ->assertJsonMissing(['key' => 'solicitudes.store'])->assertJsonMissing(['key' => 'solicitudes.clientes'])->assertJsonMissing(['key' => 'consultas-sic.clientes']);
});

test('previsualización reemplaza un elemento en memoria sin enlaces ni actividad ni persistencia', function () {
    $item = app(MenubarItemService::class)->guardar(null, menuFields($this->module), $this->actor);
    $before = Activity::count();
    $this->postJson(route('admin.menubar-items.previsualizacion'), ['screen' => 'clientes.index', 'selected_item_id' => $item->id,
        'draft' => menuFields($this->module, ['label' => 'Solo en vista previa'])])->assertOk()
        ->assertJsonPath('items.0.label', 'Solo en vista previa')->assertJsonPath('items.0.url', null)->assertJsonPath('items.0.preview', true);
    expect($item->fresh()->label)->toBe('Acción sintética')->and(MenubarItem::count())->toBe(1)->and(Activity::count())->toBe($before);
});

test('acción nueva conserva el mismo orden en vista previa y después de guardar', function () {
    app(MenubarItemService::class)->guardar(null, menuFields($this->module, ['label' => 'Anterior']), $this->actor);
    $draft = menuFields($this->module, ['label' => 'Nueva']);
    $this->postJson(route('admin.menubar-items.previsualizacion'), ['screen' => 'clientes.index', 'draft' => $draft])
        ->assertOk()->assertJsonPath('items.0.label', 'Anterior')->assertJsonPath('items.1.label', 'Nueva');
    $this->post(route('admin.menubar-items.store'), $draft)->assertRedirect();
    $this->postJson(route('admin.menubar-items.previsualizacion'), ['screen' => 'clientes.index'])
        ->assertOk()->assertJsonPath('items.0.label', 'Anterior')->assertJsonPath('items.1.label', 'Nueva');
});

test('activación histórica en otro módulo conserva la pantalla exacta', function () {
    $documents = Module::where('name', 'documentos')->sole();
    $item = MenubarItem::create(['label' => 'Expediente guardado', 'type' => 'route:name', 'value' => 'clientes.expediente.show', 'params' => ['cliente' => '{cliente}']]);
    $item->modules()->attach($documents, ['routes' => ['clientes.expediente.show']]);
    $this->postJson(route('admin.menubar-items.previsualizacion'), ['screen' => 'clientes.expediente.show'])
        ->assertOk()->assertJsonPath('items.0.label', 'Expediente guardado')->assertJsonPath('items.0.disabled', true);
});

test('lectores pueden previsualizar lo guardado pero no modificar borradores', function () {
    $reader = User::factory()->withPersonalTeam()->create();
    $reader->forceFill(['current_team_id' => $reader->ownedTeams()->first()->id])->save();
    grantTeamPermissions($reader, $reader->currentTeam, ['read menubar-items']);
    $this->actingAs($reader)->postJson(route('admin.menubar-items.previsualizacion'), ['screen' => 'clientes.index'])->assertOk();
    $this->postJson(route('admin.menubar-items.previsualizacion'), ['screen' => 'clientes.index', 'draft' => menuFields($this->module)])->assertForbidden();
    $this->postJson(route('admin.menubar-items.store'), menuFields($this->module))->assertForbidden();
});

test('retirar todas las pantallas persiste y audita una sola modificación', function () {
    $service = app(MenubarItemService::class);
    $item = $service->guardar(null, menuFields($this->module), $this->actor);
    $service->guardar($item, menuFields($this->module, ['modules' => []]), $this->actor);
    expect($item->fresh()->modules)->toHaveCount(0)->and(Activity::where('event', 'menubar_item.updated')->count())->toBe(1);
    $service->guardar($item, menuFields($this->module, ['modules' => []]), $this->actor);
    expect(Activity::where('event', 'menubar_item.updated')->count())->toBe(1);
});

test('jerarquía impide ciclos y eliminación en cascada de grupos con contenido', function () {
    $service = app(MenubarItemService::class);
    $parent = $service->guardar(null, menuFields($this->module, ['type' => 'menu', 'value' => null]), $this->actor);
    $child = $service->guardar(null, menuFields($this->module, ['type' => 'menu', 'value' => null, 'parent_id' => $parent->id]), $this->actor);
    $this->putJson(route('admin.menubar-items.update', $parent), menuFields($this->module, ['type' => 'menu', 'value' => null, 'parent_id' => $child->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    $this->deleteJson(route('admin.menubar-items.destroy', $parent))->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    expect(MenubarItem::count())->toBe(2)->and($parent->fresh()->parent_id)->toBeNull();
});

test('rutas parámetros activaciones y orden inválidos producen mensajes españoles', function (array $extra, string $field) {
    $response = $this->postJson(route('admin.menubar-items.store'), menuFields($this->module, $extra))->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($response->getContent())->not->toContain('validation.');
    expect(MenubarItem::count())->toBe(0);
})->with([
    [['value' => 'ruta.inexistente'], 'value'], [['value' => 'solicitudes.store'], 'value'],
    [['value' => 'componentes'], 'value'], [['value' => 'documentos-generados.download'], 'value'],
    [['params' => '{invalid'], 'params'], [['sort_order' => -1], 'sort_order'],
    [['type' => 'route:static', 'value' => 'javascript:alert(1)'], 'value'],
    [['type' => 'route:static', 'value' => '/componentes'], 'value'],
    [['modules' => [999999 => ['routes' => ['clientes.index']]]], 'modules'],
]);

test('contexto faltante se explica sin inventar un cliente y contexto existente usa el ID exacto', function () {
    app(MenubarItemService::class)->guardar(null, menuFields($this->module, ['value' => 'clientes.show', 'params' => '{"cliente":"{cliente}"}',
        'modules' => [$this->module->id => ['routes' => ['clientes.index', 'clientes.edit']]]]), $this->actor);
    $this->get(route('clientes.index'))->assertInertia(fn ($page) => $page->where('menubarItems.1.disabled', true)->where('menubarItems.1.reason', 'Abre el registro correspondiente para usar esta acción.')->missing('menubarItems.1.url'));
    $cliente = Cliente::factory()->create(['sucursal_id' => $this->actor->current_sucursal_id]);
    $this->get(route('clientes.edit', $cliente))->assertInertia(fn ($page) => $page->where('menubarItems.1.url', route('clientes.show', $cliente)));
});

test('servicio restaura contexto de permisos y no hereda permisos de otro equipo', function () {
    $actor = User::factory()->withPersonalTeam()->create();
    $team = $actor->ownedTeams()->first();
    $actor->forceFill(['current_team_id' => $team->id])->save();
    grantTeamPermissions($actor, $team, ['create menubar-items']);
    setPermissionsTeamId(123456);
    app(MenubarItemService::class)->guardar(null, menuFields($this->module), $actor);
    expect(getPermissionsTeamId())->toBe(123456);
    $other = \App\Models\Team::factory()->create(['user_id' => $actor->id]);
    $actor->forceFill(['current_team_id' => $other->id])->save();
    expect(fn () => app(MenubarItemService::class)->guardar(null, menuFields($this->module), $actor))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    expect(getPermissionsTeamId())->toBe(123456);
});

test('jerarquía completa conserva grupos sin activaciones propias y orden estable', function () {
    $parent = null;
    for ($level = 0; $level < 6; $level++) {
        $parent = MenubarItem::create(['type' => 'menu', 'label' => 'Grupo '.$level, 'parent_id' => $parent?->id]);
    }
    app(MenubarItemService::class)->guardar(null, menuFields($this->module, ['parent_id' => $parent->id]), $this->actor);
    $this->get(route('clientes.index'))->assertInertia(fn ($page) => $page
        ->where('menubarItems.1.items.0.items.0.items.0.items.0.items.0.items.0.label', 'Acción sintética'));
});

test('condición avanzada conserva contenido al editar etiqueta y resuelve coincidencia positiva', function () {
    $cliente = Cliente::factory()->create(['sucursal_id' => $this->actor->current_sucursal_id]);
    $value = json_encode([
        ['condition_type' => 'default', 'route_name' => 'clientes.index'],
        ['condition_type' => 'route_regexp', 'condition_value' => ['route_name' => 'clientes.edit', 'pregmatch_subject_type' => 'referer'],
            'route_name' => 'clientes.show', 'params' => ['cliente' => $cliente->id]],
    ]);
    $fields = menuFields($this->module, ['type' => 'route:dynamic', 'value' => $value]);
    $item = app(MenubarItemService::class)->guardar(null, $fields, $this->actor);
    $this->putJson(route('admin.menubar-items.update', $item), [...$fields, 'label' => 'Renombrada'])->assertRedirect();
    expect($item->fresh()->value)->toBe($value);
    $this->withHeader('referer', route('clientes.edit', $cliente))->get(route('clientes.index'))
        ->assertInertia(fn ($page) => $page->where('menubarItems.1.url', route('clientes.show', $cliente)));
});

test('un destino guardado no concede permisos ni anuncia páginas reservadas', function () {
    app(MenubarItemService::class)->guardar(null, menuFields($this->module, ['value' => 'admin.users.index', 'label' => 'Reservada']), $this->actor);
    $reader = User::factory()->withPersonalTeam()->create();
    $reader->forceFill(['current_team_id' => $reader->ownedTeams()->first()->id])->save();
    grantTeamPermissions($reader, $reader->currentTeam, ['read clientes']);
    $this->actingAs($reader)->get(route('clientes.index'))->assertInertia(fn ($page) => $page->has('menubarItems', 1));
    $this->get(route('admin.users.index'))->assertForbidden();
});

test('creación sin lectura recibe metadatos pero no el árbol guardado ni vista previa', function () {
    app(MenubarItemService::class)->guardar(null, menuFields($this->module, ['type' => 'menu', 'value' => null]), $this->actor);
    $creator = User::factory()->withPersonalTeam()->create();
    $creator->forceFill(['current_team_id' => $creator->ownedTeams()->first()->id])->save();
    grantTeamPermissions($creator, $creator->currentTeam, ['create menubar-items']);
    $this->actingAs($creator)->getJson(route('admin.menubar-items.catalogo'))->assertOk()->assertJsonCount(0, 'grupos');
    $this->postJson(route('admin.menubar-items.previsualizacion'), ['screen' => 'clientes.index', 'draft' => menuFields($this->module)])->assertForbidden();
});

test('destinos de roles usan el equipo seleccionado y restauran el contexto anterior', function () {
    $actor = User::factory()->withPersonalTeam()->create();
    $teamA = $actor->ownedTeams()->first();
    $teamB = \App\Models\Team::factory()->create(['user_id' => $actor->id]);
    $teamC = \App\Models\Team::factory()->create(['user_id' => $actor->id]);
    $actor->forceFill(['current_team_id' => $teamA->id])->save();
    grantTeamPermissions($actor, $teamA, ['read clientes']);
    grantTeamPermissions($actor, $teamB, ['read roles']);
    $item = app(MenubarItemService::class)->guardar(null, menuFields($this->module, [
        'value' => 'admin.roles.index', 'params' => json_encode(['team' => $teamB->id]),
    ]), $this->actor);
    $this->actingAs($actor)->get(route('clientes.index'))->assertInertia(fn ($page) => $page
        ->where('menubarItems.1.url', route('admin.roles.index', ['team' => $teamB->id])));
    $this->get(route('admin.roles.index', ['team' => $teamB->id]))->assertOk();
    grantTeamPermissions($actor, $teamA, ['read roles']);
    $item->update(['params' => ['team' => $teamC->id]]);
    $this->get(route('clientes.index'))->assertInertia(fn ($page) => $page->has('menubarItems', 1));
    $this->get(route('admin.roles.index', ['team' => $teamC->id]))->assertForbidden();
    setPermissionsTeamId(345678);
    expect(app(\App\Services\MenubarDestinoService::class)->permitido('admin.roles.index', ['team' => $teamB->id], $actor))->toBeTrue();
    expect(getPermissionsTeamId())->toBe(345678);
});
