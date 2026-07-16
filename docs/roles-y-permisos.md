---
type: explanation
area: authorization
status: active
---

# Roles y Permisos

## Stack de autorizacion

Esta aplicacion usa dos sistemas de roles en paralelo:

| Sistema | Paquete | Proposito |
|---------|---------|-----------|
| Roles Jetstream | `laravel/jetstream` | Rol descriptivo dentro del equipo, guardado en `team_user.role` |
| Roles + permisos Spatie | `spatie/laravel-permission` | Control de acceso real, acotado por `team_id` |

La autorizacion funcional se basa en Spatie. El rol Jetstream no debe usarse para permitir o denegar acciones sensibles.

## Modelo de datos

`config/permission.php` tiene `teams => true`, `team_foreign_key => team_id` y `models.user => App\Models\User::class` para que `Role::users()` funcione en la GUI.

| Tabla | Descripcion |
|-------|-------------|
| `permissions` | Catalogo global de permisos; cada permiso puede pertenecer a un `module_id` |
| `roles` | Roles globales plantilla o roles scoped por equipo |
| `role_has_permissions` | Pivot rol-permiso |
| `model_has_roles` | Asignacion usuario-rol, incluye `team_id` |
| `model_has_permissions` | Permiso directo a usuario, incluye `team_id` |
| `modules` | Agrupador funcional para la UI de permisos |

Los permisos son globales. Los roles operativos pertenecen a un equipo. El rol `Super Admin` es global (`team_id = null`).

## Super Admin

`Super Admin` funciona como superusuario real mediante `Gate::before()` en `AppServiceProvider`.

Reglas:

- Pasa cualquier `Gate::check()` y middleware `permission:*`.
- No necesita permisos explicitos en `role_has_permissions`.
- No se edita ni elimina desde la GUI de roles.
- Solo otro `Super Admin` puede asignarlo a usuarios.
- `DevelopmentSeeder` asigna `Super Admin` a `test@example.com`.

## Permisos y modulos

Los permisos siguen el patron `accion recurso`, por ejemplo:

- `access admin`
- `read users`
- `create clientes`
- `update roles`
- `delete menubar-items`

Modulos registrados actualmente:

| Modulo | Permisos |
|--------|----------|
| `dashboard` | `read dashboard` |
| `admin` | `access admin` |
| `users` | `create/read/update/delete users` |
| `roles` | `create/read/update/delete roles` |
| `menubar-items` | `create/read/update/delete menubar-items` |
| `activity-log` | `read activity-log` |
| `clientes` | `create/read/update/delete clientes` |
| `historial-crediticio` | `read historial-crediticio` |
| `circulo-credito` | `create/read circulo-credito` |
| `teams` | `create/delete/update/add members/remove members/update members teams` |

## Backend

Archivos clave:

| Archivo | Proposito |
|---------|-----------|
| `app/Providers/AppServiceProvider.php` | Registra `Gate::before()` para `Super Admin` y define gates por permiso |
| `app/Http/Middleware/TeamsPermission.php` | Establece `setPermissionsTeamId($current_team_id)` por request |
| `app/Http/Middleware/HandleInertiaRequests.php` | Comparte `auth.permissions` y datos de admin al frontend |
| `routes/web.php` | Protege rutas admin con `permission:access admin` y permisos por recurso |
| `app/Http/Controllers/Admin/RoleController.php` | CRUD de roles por equipo y sincronizacion de permisos |
| `app/Http/Controllers/Admin/UserController.php` | Asignacion de roles a usuarios |
| `database/seeders/ModulesAndPermissionsSeeder.php` | Crea modulos y permisos globales |
| `database/seeders/RolesSeeder.php` | Crea roles globales plantilla y permisos base |
| `database/seeders/DevelopmentSeeder.php` | Crea usuario demo, equipo demo, roles por equipo y datos demo |
| `database/seeders/MenubarItemsSeeder.php` | Crea accesos de navegacion, incluyendo administracion |

Las rutas `admin/*` no deben depender solo de la visibilidad del menu. La proteccion real vive en middleware y controllers.

## Frontend

La GUI principal esta en:

- `resources/js/Pages/Admin/Roles/Index.vue`
- `resources/js/Components/Role/*`
- `resources/js/Pages/Admin/Users/Index.vue`
- `resources/js/Pages/Admin/Users/Form.vue`

Capacidades actuales:

- Crear roles para el equipo activo.
- Seleccionar permisos agrupados por modulo.
- Ver usuarios asignados a un rol.
- Asignar roles a usuarios desde administracion de usuarios.
- Bloquear acciones de edicion/eliminacion sobre `Super Admin` global.
- Navegar a Usuarios, Roles, Menubar y Actividad desde la seccion de administracion del menu sembrado.

El menubar soporta rutas tipo recurso (`admin.roles.index`) y rutas nombradas custom (`admin.users.activity`). Al crear un modulo nuevo hay que registrar su `route_name` en `ModulesAndPermissionsSeeder` y asociar sus rutas visibles en `MenubarItemsSeeder`.

## Seeders

`SystemSeeder` corre:

1. `ModulesAndPermissionsSeeder`
2. `RolesSeeder`
3. `MenubarItemsSeeder`
4. `SicsSeeder`

`RolesSeeder` define roles plantilla globales y asigna permisos base. `DevelopmentSeeder` replica esos roles al equipo demo con sus permisos y asigna `Super Admin` al usuario demo.

Cuando se crea un equipo desde Jetstream, `CreateTeam` replica los roles globales no-superadmin al nuevo equipo y copia sus permisos.

## Flujo operativo

Para reconstruir datos de desarrollo sin borrar SAT/SEPOMEX:

```bash
php artisan dev:reset-data
```

Ese comando limpia usuarios, equipos, roles scoped por equipo, pivots Spatie y datos volatiles. Conserva permisos, modulos, roles globales y catalogos pesados; luego re-ejecuta `SystemSeeder` y `DevelopmentSeeder`.

## Notas importantes

- En jobs y comandos Artisan se debe llamar `setPermissionsTeamId($teamId)` antes de verificar permisos scoped por equipo.
- Si los permisos no reflejan cambios recientes, usar `php artisan permission:cache-reset`.
- Crear permisos nuevos exige actualizar `ModulesAndPermissionsSeeder` y esta documentacion.
- Crear rutas nuevas exige decidir modulo, permiso y middleware.

Relacionado:

- [Seeders](reference/seeders.md)
- [Reset de datos de desarrollo](how-to/reset-datos-desarrollo.md)
- [Decisiones tecnicas](explanation/decisiones.md)
