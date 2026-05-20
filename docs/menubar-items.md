# Sistema MenubarItems

## Cómo funciona

El sistema genera dinámicamente la barra de navegación superior en función de la ruta activa. Los items son configurables desde la BD por un administrador. El flujo es:

```
Request HTTP
    │
    ▼
HandleInertiaRequests::share()
    │  app(MenubarService::class)->getMenuItems($request)
    │  → detecta módulo y acción de la ruta actual
    │  → carga items filtrados por módulo
    │  → resuelve URLs
    │  retorna [] si no hay módulo asociado
    ▼
page.props.menubarItems  (disponible en todas las páginas)
    ▼
AppLayout.vue
    │  computed(() => page.props.menubarItems)
    │  <Menubar :model="menubarItems">
    │  slot #item renderiza <a href> para garantizar links reales
    ▼
PrimeVue Menubar renderizado
```

## Módulos

Cada sección de la app tiene un `Module` en BD con `route_name` que coincide con el prefijo de las rutas Laravel. Ejemplos:

| Module.route_name | Rutas que cubre |
|---|---|
| `clientes` | clientes.index, clientes.show, clientes.create, clientes.edit |
| `clientes.historial-crediticio` | clientes.historial-crediticio.show |
| `historial-crediticio` | historial-crediticio.index |

`MenubarService::getMenuItems()` extrae el módulo de la ruta actual quitando el último segmento:
- `clientes.show` → módulo `clientes`
- `clientes.historial-crediticio.show` → módulo `clientes.historial-crediticio`

## Tipos de MenubarItem (campo `type`)

| Tipo | Label UI | Valor (`value`) | Params |
|---|---|---|---|
| `menu` | Carpeta / Agrupador | null | — |
| `route:name` | Página de la aplicación | nombre de ruta Laravel (`clientes.show`) | JSON `{"cliente": "{cliente}"}` |
| `route:static` | URL externa | URL completa (`/clientes`) | — |
| `route:referer_fallback` | Botón Regresar | ruta de fallback si no hay referer | — |
| `route:dynamic` | Condicional avanzado | JSON con condiciones (ver abajo) | — |

### Params dinámicos (`route:name`)

El campo `params` es un JSON que mapea parámetros de ruta:
```json
{"cliente": "{cliente}"}
```
Al resolver la URL, `MenubarService` reemplaza `{cliente}` con `$request->route('cliente')->id`.

### route:dynamic (avanzado)

Lógica condicional basada en regex sobre la URL actual o el referer. Estructura del JSON en `value`:
```json
[
  {
    "condition_type": "default",
    "route_name": "clientes.show",
    "params": {"cliente": "{cliente}"}
  },
  {
    "condition_type": "route_regexp",
    "condition_value": {
      "pregmatch_subject_type": "referer",
      "route_name": "clientes.historial-crediticio.show"
    }
  }
]
```

⚠️ La lógica `!preg_match()` en `MenubarService::resolveMenubarUrl()` línea ~125 tiene comportamiento posiblemente invertido. Documentar caso de uso antes de agregar más condiciones de este tipo.

## Pivot: MenubarItemModule

Cada `MenubarItem` se vincula a uno o más módulos mediante la tabla `menubar_item_module`. El pivot incluye:

```json
{
  "routes": ["clientes.index", "clientes.create"]
}
```

Esto controla en **cuáles acciones del módulo** aparece el item. Si el item tiene `routes: ["clientes.index"]`, solo aparece cuando la acción actual es `index` del módulo `clientes`.

El administrador selecciona las acciones desde el formulario con estas opciones:
- `index` → Listado / Vista principal
- `create` → Formulario de creación
- `show` → Vista de detalle
- `edit` → Formulario de edición

## Items automáticos (no configurables en BD)

`MenubarService::buildMenu()` agrega automáticamente al inicio:
- **Página de inicio**: Si acción actual es `index` → link a `dashboard`
- **Regresar**: En cualquier otra acción → link al referer o al `.index` del módulo

## Archivos clave

| Archivo | Rol |
|---|---|
| `app/Services/MenubarService.php` | Motor: detecta módulo, filtra items, resuelve URLs |
| `app/Models/MenubarItem.php` | Modelo: campos type, value, params, parent_id, sort_order |
| `app/Models/MenubarItemModule.php` | Pivot: menubar_item_id, module_id, routes (JSON) |
| `app/Models/Module.php` | Módulos del sistema con route_name |
| `app/Http/Middleware/HandleInertiaRequests.php` | Comparte menubarItems en cada request |
| `app/Http/Controllers/Admin/MenubarItemController.php` | CRUD admin de items |
| `resources/external/sakai-vue/layout/AppLayout.vue` | Renderiza el Menubar (computed reactivo + slot #item) |
| `resources/js/Pages/Admin/MenubarItems/Index.vue` | UI de configuración (TreeTable) |
| `resources/js/Pages/Admin/MenubarItems/Form.vue` | Formulario de creación/edición |
| `config/permission.php` | No relacionado directamente; los items no tienen permiso propio aún |

## TODO

- [ ] **Cache de menubar items** — actualmente se regenera en cada request vía `HandleInertiaRequests`. Agregar cache por `module_id` con invalidación al guardar items.
- [ ] **Permisos por item** — mostrar/ocultar items según permiso Spatie del usuario actual. Necesita columna `permission_name` en `menubar_items` y verificación en `buildMenu()`.
- [ ] **Validación backend** — verificar que `route:name` apunte a una ruta existente al guardar. Actualmente no valida y crashea en runtime.
- [ ] **Tests de integración** para `MenubarService` — cubrir: módulo no encontrado, items sin pivot, params dinámicos, route:referer_fallback.
- [ ] **Mejorar formulario de configuración** — selector visual de íconos PrimeVue, AutoComplete de rutas disponibles (requiere endpoint `GET /admin/menubar-items/routes`), builder visual para `route:dynamic`.
- [ ] **Quick-add desde cualquier página** — botón FAB visible para usuarios con permiso `configure menubar`; abre formulario simplificado pre-rellenado con el módulo actual.
- [ ] **Aclarar lógica de route:dynamic** — revisar si `!preg_match()` en `MenubarService.php:~125` está invertido y documentar el comportamiento esperado.
- [ ] **Soporte para submenu en items dinámicos** — actualmente `buildMenu()` solo agrega `items` si el item tiene `children` cargados desde BD, no desde condiciones dinámicas.
