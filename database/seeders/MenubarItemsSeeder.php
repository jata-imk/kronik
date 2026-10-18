<?php

namespace Database\Seeders;

use App\Models\MenubarItem;
use App\Models\MenubarItemModule;
use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class MenubarItemsSeeder extends Seeder
{
    // Only navigable screens/viewers: writes and JSON helper endpoints are not menu destinations.
    private const CATALOG = [
        'dashboard' => ['Inicio y cuenta', [
            'dashboard' => 'Panel principal', 'profile.show' => 'Mi perfil y seguridad', 'api-tokens.index' => 'Tokens API',
        ]],
        'clientes' => ['Clientes', [
            'clientes.index' => 'Listado de clientes', 'clientes.create' => 'Nuevo cliente',
            'clientes.show' => 'Información del cliente', 'clientes.edit' => 'Editar cliente',
            'clientes.expedientes.index' => 'Expedientes de clientes', 'clientes.expediente.show' => 'Abrir expediente',
            'solicitudes.create' => 'Nueva solicitud',
        ]],
        'solicitudes' => ['Solicitudes', [
            'solicitudes.index' => 'Listado de solicitudes', 'solicitudes.trabajo' => 'Mi trabajo',
            'solicitudes.clientes' => 'Clientes para solicitudes', 'solicitudes.create' => 'Nueva solicitud',
            'solicitudes.show' => 'Detalle de solicitud', 'solicitudes.edit' => 'Editar solicitud',
            'solicitudes.paquete.show' => 'Paquete contractual', 'solicitudes.desembolso.create' => 'Preparar desembolso QA',
        ]],
        'evaluacion-solicitudes' => ['Evaluación de solicitudes', ['evaluacion-solicitudes.index' => 'Bandeja de evaluación']],
        'cumplimiento' => ['Cumplimiento', ['cumplimiento.index' => 'Bandeja de cumplimiento']],
        'creditos' => ['Créditos y pagos', [
            'creditos.index' => 'Cartera de créditos', 'creditos.show' => 'Detalle del crédito',
            'creditos.pagos.create' => 'Registrar pago QA', 'creditos.anticipos.create' => 'Anticipo o liquidación QA',
            'creditos.pagos.show' => 'Recibo de pago',
        ]],
        'productos-crediticios' => ['Productos crediticios', [
            'productos-crediticios.index' => 'Productos y simulador', 'originacion-politicas.show' => 'Política de originación',
        ]],
        'plantillas-documentos' => ['Plantillas de documentos', ['plantillas-documentos.index' => 'Plantillas, versiones y recursos']],
        'documentos' => ['Documentos', [
            'clientes.expediente.show' => 'Documentos del cliente', 'documentos-generados.view' => 'Ver PDF generado',
        ]],
        'historial-crediticio' => ['Historial crediticio', [
            'clientes.historial-crediticio.index' => 'Listado de consultas', 'clientes.historial-crediticio.show' => 'Historial del cliente',
            'consultas-sic.show' => 'Reporte y gráficas SIC',
        ]],
        'circulo-credito' => ['Círculo de Crédito', [
            'circulo-credito.index' => 'Consulta SIC', 'circulo-credito.create' => 'Nueva consulta SIC',
            'consultas-sic.clientes' => 'Clientes para consulta SIC',
        ]],
        'admin' => ['Administración', ['admin.dashboard' => 'Panel de administración']],
        'users' => ['Usuarios', ['admin.users.index' => 'Usuarios e invitaciones']],
        'teams' => ['Equipos', ['admin.teams.index' => 'Gestión de equipos', 'teams.show' => 'Centro del equipo']],
        'sucursales' => ['Sucursales', ['admin.sucursales.index' => 'Gestión de sucursales']],
        'roles' => ['Roles y permisos', ['admin.roles.index' => 'Gestión de roles y permisos']],
        'menubar-items' => ['Menú contextual', ['admin.menubar-items.index' => 'Menú contextual por módulos']],
        'activity-log' => ['Actividad y auditoría', ['admin.users.activity' => 'Actividad de usuarios']],
        'configuracion-empresa' => ['Empresa', ['admin.configuracion-empresa.index' => 'Configuración de empresa']],
    ];

    public function run(): void
    {
        $modules = Module::whereIn('name', array_keys(self::CATALOG))->get()->keyBy('name');
        foreach (self::CATALOG as $moduleName => [$label, $pages]) {
            if (! $modules->has($moduleName)) {
                throw new RuntimeException("Falta el módulo {$moduleName}. Ejecuta ModulesAndPermissionsSeeder antes de cargar el menú.");
            }
            foreach ($pages as $routeName => $pageLabel) {
                if (! Route::has($routeName) || ! in_array('GET', Route::getRoutes()->getByName($routeName)->methods(), true)) {
                    throw new RuntimeException("La página {$routeName} no está disponible. Revisa el catálogo del menú antes de sembrarlo.");
                }
            }
        }

        DB::transaction(function () use ($modules) {
            foreach (self::CATALOG as $moduleName => [$label, $pages]) {
                $module = $modules->get($moduleName);
                // Menu values are ignored by the renderer; this stable key permits renaming a folder.
                $parent = MenubarItem::firstOrCreate(['type' => 'menu', 'value' => 'catalogo:'.$moduleName], [
                    'label' => $label, 'icon' => 'pi pi-'.preg_replace('/^pi-/', '', $module->icon ?? 'folder'),
                    'parent_id' => null, 'sort_order' => (int) MenubarItem::max('sort_order') + 1,
                ]);
                $this->linkWithoutActivation($parent, $module);
                foreach ($pages as $routeName => $pageLabel) {
                    $params = [];
                    $route = Route::getRoutes()->getByName($routeName);
                    foreach ($route->parameterNames() as $parameter) {
                        if (! str_contains($route->uri(), '{'.$parameter.'?}')) {
                            $params[$parameter] = '{'.$parameter.'}';
                        }
                    }
                    $item = MenubarItem::where('type', 'route:name')->where('value', $routeName)
                        ->whereHas('menubarItemModules', fn ($query) => $query->where('module_id', $module->id))
                        ->orderBy('id')->first();
                    $item ??= MenubarItem::create([
                        'parent_id' => $parent->id, 'type' => 'route:name', 'value' => $routeName,
                        'label' => $pageLabel, 'icon' => 'pi pi-arrow-right', 'params' => $params ?: null,
                        'sort_order' => (int) $parent->children()->max('sort_order') + 1,
                    ]);
                    $this->linkWithoutActivation($item, $module);
                }
            }
        });
    }

    private function linkWithoutActivation(MenubarItem $item, Module $module): void
    {
        // Re-running the seeder preserves activation routes chosen later by the administrator.
        MenubarItemModule::firstOrCreate(['menubar_item_id' => $item->id, 'module_id' => $module->id], ['routes' => []]);
    }
}
