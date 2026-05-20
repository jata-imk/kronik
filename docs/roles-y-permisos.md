# Roles y Permisos

## Stack de autorización

Esta aplicación usa **dos sistemas de roles en paralelo**:

| Sistema | Paquete | Propósito |
|---------|---------|-----------|
| Roles Jetstream | `laravel/jetstream` | Rol dentro del equipo (string libre en pivot `team_user.role`) |
| Roles + Permisos Spatie | `spatie/laravel-permission` | Control de acceso real; acotado por `team_id` |

La autorización funcional (qué puede hacer el usuario) se basa **exclusivamente en Spatie**. El rol Jetstream es solo una etiqueta descriptiva en la UI de gestión de equipo.

---

## Modelo de datos (Spatie con teams)

`config/permission.php` tiene `'teams' => true` y `'team_foreign_key' => 'team_id'`.

### Tablas

| Tabla | Descripción |
|-------|-------------|
| `permissions` | Catálogo global de permisos; tiene `module_id` (FK a `modules`) |
| `roles` | Roles; cada uno pertenece a un `team_id` específico |
| `role_has_permissions` | Pivot rol↔permiso |
| `model_has_roles` | Asignación usuario↔rol, incluye `team_id` |
| `model_has_permissions` | Permiso directo a usuario (sin pasar por rol), incluye `team_id` |
| `modules` | Agrupador de permisos (ej. "Clientes", "Cobranza", "Admin") |

### Convención de nombres de permisos

Los permisos siguen el patrón `"acción recurso"`:
- `"create users"` → acción: `create`, recurso: `users`
- `"view clientes"` → acción: `view`, recurso: `clientes`
- `"add members teams"` → acción: `add`, recurso: `members teams`

El modelo `Permission` expone los accessors `$permission->action` y `$permission->resource` que extraen estas partes automáticamente.

---

## Archivos clave

### Backend

| Archivo | Propósito |
|---------|-----------|
| `app/Models/Permission.php` | Extiende `SpatiePermission`; agrega relación `module()` y accessors `action`/`resource` |
| `app/Models/Module.php` | Agrupa permisos; tiene `name`, `icon`, `route_name`, jerarquía (`parent_id`) |
| `app/Models/User.php` | Usa trait `HasRoles` de Spatie |
| `app/Http/Middleware/TeamsPermission.php` | Llama `setPermissionsTeamId($current_team_id)` antes de cada request → activa el scope de equipo en Spatie |
| `app/Http/Middleware/HandleInertiaRequests.php` | Repite el `setPermissionsTeamId()`; construye `auth.permissions` (mapa plano `clave→bool`) para el frontend |
| `app/Http/Controllers/Admin/RoleController.php` | CRUD de roles; lista/asigna/sincroniza permisos; filtra roles por `current_team_id` |
| `app/Http/Controllers/Admin/UserController.php` | Asigna roles a usuarios con `syncRoles()` |
| `config/permission.php` | `teams: true`, modelos custom (`App\Models\Permission`), tabla names |
| `database/seeders/` | Seeders de roles y permisos iniciales |

### Frontend

| Archivo | Propósito |
|---------|-----------|
| `resources/js/Pages/Admin/Roles/Index.vue` | Vista principal de gestión de roles; usa `RolePanel` |
| `resources/js/Pages/Admin/Roles/CreateRoleModal.vue` | Modal para crear nuevo rol |
| `resources/js/Components/Role/RolePanel.vue` | Componente principal: lista roles, muestra permisos agrupados por módulo, permite editar |
| `resources/js/Pages/Admin/Users/Index.vue` | Lista usuarios; permite asignar roles |
| `resources/js/Pages/Admin/Users/Form.vue` | Formulario de edición de usuario con selector de roles |

---

## Flujo de verificación de permisos

### En el backend (PHP)

```php
// Middleware TeamsPermission se ejecuta primero:
setPermissionsTeamId($user->current_team_id);

// Luego en cualquier controlador/servicio:
$user->can('create clientes');       // via Gate (Spatie lo registra)
$user->hasPermissionTo('view logs'); // directo Spatie
$user->hasRole('Administrador');     // verificar rol
Gate::check('add members teams', $user); // como en AddTeamMember.php
```

### En el frontend (Vue/Inertia)

`HandleInertiaRequests::share()` inyecta en cada página:

```js
// Disponible en cualquier página via usePage():
const page = usePage();
const permissions = page.props.auth.permissions;
// { "create-clientes": true, "view-logs": false, ... }

// Los espacios en el nombre se convierten a guiones:
// "create clientes" → key "create-clientes"
```

---

## CRUD de roles (flujo completo)

1. **Crear rol** → `POST /admin/roles` → `RoleController::store()`
   - Valida nombre único, `team_id` del equipo activo
   - Opción `add_all_permissions` asigna todos los permisos de una vez
   - Guard forzado a `web`

2. **Editar permisos de un rol** → `PUT /admin/roles/{role}` → `RoleController::update()`
   - Usa `$role->syncPermissions($permissions)` — reemplaza completamente los permisos

3. **Eliminar rol** → `DELETE /admin/roles/{role}` → `RoleController::destroy()`
   - Protegido: falla si el rol tiene permisos o usuarios asociados

4. **Asignar rol a usuario** → `PUT /admin/users/{user}` → `UserController::update()`
   - Usa `$user->syncRoles($roles)` — reemplaza todos los roles del usuario

---

## Módulos

Los `Module` agrupan permisos para facilitar la UI. Un módulo puede tener sub-módulos (`parent_id`). La vista de roles muestra los permisos organizados por módulo (ver `RolePanel.vue`).

```
Módulo: Clientes
  ├── create clientes
  ├── view clientes
  ├── edit clientes
  └── delete clientes

Módulo: Admin
  ├── create users
  ├── view users
  └── add members teams
```

---

## Notas importantes

- Los permisos (`permissions` table) son **globales** — no pertenecen a un equipo. Los **roles sí** tienen `team_id`.
- Un usuario puede tener el mismo nombre de rol en distintos equipos porque los roles son únicos por `name + team_id`.
- En Jobs y comandos Artisan: llamar `setPermissionsTeamId($teamId)` manualmente antes de verificar permisos.
- El caché de permisos de Spatie puede causar datos obsoletos. En desarrollo usar `php artisan permission:cache-reset` si los permisos no reflejan cambios recientes.
- `UserController::index()` incluye `orWhere('name', 'Super Admin')` — el rol `Super Admin` es transversal a todos los equipos.
