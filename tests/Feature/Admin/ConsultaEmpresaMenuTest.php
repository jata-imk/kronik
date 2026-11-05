<?php

use App\Models\EmpresaConfiguracion;
use App\Models\MenubarItem;
use App\Models\Module;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\MenubarItemService;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->withoutVite();
});

function lectorEmpresaMenu(): User
{
    $actor = User::factory()->withPersonalTeam()->create();
    $actor->forceFill(['current_team_id' => $actor->ownedTeams()->first()->id])->save();
    grantTeamPermissions($actor, $actor->currentTeam, ['read configuracion-empresa', 'read menubar-items']);

    return $actor;
}

test('lectura de empresa ausente no crea singleton y oculta secretos en la configurada', function () {
    $actor = lectorEmpresaMenu();
    $before = Activity::count();
    $this->actingAs($actor)->get(route('admin.configuracion-empresa.index', ['modo' => 'edicion']))
        ->assertInertia(fn (Assert $p) => $p->where('configurada', false)->where('puedeEditar', false)->where('modoEmpresa', 'consulta'));
    expect(EmpresaConfiguracion::count())->toBe(0)->and(Activity::count())->toBe($before);
    $company = EmpresaConfiguracion::create(['singleton_key' => 'default', 'razon_social' => 'Guardada', 'estatus' => 'borrador', 'integraciones' => ['circulo_credito_api_key' => 'SECRET-NEVER-EXPOSE']]);
    $this->get(route('admin.configuracion-empresa.index'))->assertInertia(fn (Assert $p) => $p->where('configuracion.razon_social', 'Guardada')
        ->where('configuracion.integraciones.circulo_credito_api_key_configurada', true)->missing('configuracion.integraciones.circulo_credito_api_key'));
    $this->putJson(route('admin.configuracion-empresa.update'), ['razon_social' => 'Alterada'])->assertForbidden();
    expect($company->fresh()->razon_social)->toBe('Guardada')->and(Activity::count())->toBe($before);
});

test('lector de menú consulta ID exacto descendiente fuera de filtros sin capacidades de mutación', function () {
    $actor = lectorEmpresaMenu();
    $parent = MenubarItem::create(['label' => 'Padre', 'type' => 'menu']);
    $child = MenubarItem::create(['label' => 'Hijo', 'type' => 'route:dynamic', 'value' => '[{"condition_type":"default","route_name":"clientes.show"}]', 'params' => ['cliente' => '{cliente}'], 'parent_id' => $parent->id]);
    $before = Activity::count();
    $this->actingAs($actor)->get(route('admin.menubar-items.index', ['selected_item_id' => $child->id, 'modo' => 'edicion', 'buscar' => 'excluido', 'page' => 2]))
        ->assertInertia(fn (Assert $p) => $p->where('menubarSeleccionado.id', $child->id)->where('menubarSeleccionado.params.cliente', '{cliente}')
            ->where('menubarSeleccionado.parent.id', $parent->id)->where('modoMenu', 'consulta')->has('menubarCatalogo', 0));
    $this->putJson(route('admin.menubar-items.update', $child), ['label' => 'Alterado'])->assertForbidden();
    $this->deleteJson(route('admin.menubar-items.destroy', $child))->assertForbidden();
    $this->postJson(route('admin.menubar-items.store'), [])->assertForbidden();
    expect(Activity::count())->toBe($before)->and($child->fresh()->label)->toBe('Hijo');
    $this->get(route('admin.menubar-items.index', ['selected_item_id' => 999999]))->assertNotFound();
    $other = User::factory()->withPersonalTeam()->create();
    $this->actingAs($other)->get(route('admin.menubar-items.index', ['selected_item_id' => $child->id]))->assertForbidden();
});

test('gestión de menú audita actor contexto y resultado sin copiar rutas ni parámetros', function (bool $super) {
    $actor = $super ? actingAsSuperAdmin() : lectorEmpresaMenu();
    if (! $super) {
        grantTeamPermissions($actor, $actor->currentTeam, ['create menubar-items', 'update menubar-items', 'delete menubar-items']);
    }
    $module = Module::create(['name' => 'clientes', 'route_name' => 'clientes']);
    $fields = ['label' => 'PRIVATE-LABEL', 'type' => 'route:name', 'value' => 'clientes.index', 'params' => '{"reserved":"PRIVATE-PARAMETER"}', 'modules' => [$module->id => ['routes' => ['clientes.index']]]];
    $this->actingAs($actor)->post(route('admin.menubar-items.store'), $fields)->assertSessionHasNoErrors();
    $item = MenubarItem::where('label', 'PRIVATE-LABEL')->sole();
    $this->put(route('admin.menubar-items.update', $item), $fields)->assertSessionHasNoErrors();
    expect(Activity::where('event', 'menubar_item.updated')->count())->toBe(0);
    $this->put(route('admin.menubar-items.update', $item), [...$fields, 'label' => 'PRIVATE-UPDATED'])->assertSessionHasNoErrors();
    $this->put(route('admin.menubar-items.update', $item), [...$fields, 'label' => 'PRIVATE-UPDATED'])->assertSessionHasNoErrors();
    $this->delete(route('admin.menubar-items.destroy', $item))->assertSessionHasNoErrors();
    foreach (['created', 'updated', 'deleted'] as $suffix) {
        $row = Activity::where('event', 'menubar_item.'.$suffix)->sole();
        expect($row->causer_id)->toBe($actor->id)->and($row->subject_id)->toBe($item->id)
            ->and($row->team_id)->toBe($actor->current_team_id)->and($row->sucursal_id)->toBe($actor->current_sucursal_id)
            ->and($row->properties['result'])->toBe($suffix === 'updated' ? 'applied' : $suffix);
    }
    expect(Activity::where('event', 'like', 'menubar_item.%')->get()->toJson())->not->toContain('PRIVATE-LABEL', 'PRIVATE-PARAMETER', 'PRIVATE-UPDATED', 'clientes.index');
})->with([false, true]);

test('fallo de auditoría revierte alta de menú y vínculos', function () {
    $actor = actingAsSuperAdmin();
    $before = MenubarItem::count();
    $module = Module::create(['name' => 'clientes', 'route_name' => 'clientes']);
    $this->mock(ActivityLogService::class)->shouldReceive('log')->once()->andThrow(new RuntimeException('fallo de prueba'));
    expect(fn () => app(MenubarItemService::class)->guardar(null, ['label' => 'No persiste', 'type' => 'menu', 'modules' => [$module->id => ['routes' => ['clientes.index']]]], $actor))->toThrow(RuntimeException::class);
    expect(MenubarItem::count())->toBe($before)->and(\Illuminate\Support\Facades\DB::table('menubar_item_module')->count())->toBe(0);
});
