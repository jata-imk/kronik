# Equipos de Jetstream

## ¿Qué es un Equipo en Jetstream?

Jetstream introduce el concepto de **equipos** (`teams`) como una forma de agrupar usuarios bajo un contexto compartido. Cada usuario puede pertenecer a múltiples equipos, pero solo tiene **un equipo activo a la vez** (`current_team_id`). Todo el acceso a datos, roles y permisos se evalúa en función de ese equipo activo.

### Tablas involucradas

| Tabla | Propósito |
|-------|-----------|
| `teams` | Registro de cada equipo (`id`, `name`, `user_id` dueño, `personal_team`) |
| `team_user` (pivot `memberships`) | Relación usuario↔equipo con columna `role` (rol Jetstream, no Spatie) |
| `team_invitations` | Invitaciones pendientes por correo |

### Equipo personal

Al registrarse, cada usuario recibe automáticamente un **equipo personal** (`personal_team = true`). No se puede eliminar. Sirve como espacio de trabajo individual antes de ser asignado a un equipo institucional.

---

## Cómo aplica en esta aplicación financiera

En el contexto del ERP financiero, **un equipo representa una organización o sucursal**. Ejemplos de uso:

- **Institución financiera principal** → equipo raíz con acceso total
- **Sucursal regional** → equipo propio, solo ve sus clientes y operaciones
- **Equipo de cobranza** → acceso limitado a cartera vencida
- **Auditoría interna** → acceso de solo lectura a logs y reportes

Los usuarios se agregan a equipos y reciben **roles Spatie acotados a ese equipo**. Cambiar de equipo activo cambia los permisos disponibles en tiempo real.

---

## Archivos clave

### Backend

| Archivo | Propósito |
|---------|-----------|
| `app/Models/Team.php` | Extiende `JetstreamTeam`; dispara eventos `TeamCreated/Updated/Deleted` |
| `app/Models/Membership.php` | Extiende `JetstreamMembership`; tabla pivot `team_user` |
| `app/Models/TeamInvitation.php` | Modelo de invitaciones pendientes |
| `app/Models/User.php` | Usa el trait `HasTeams` de Jetstream + `HasRoles` de Spatie |
| `app/Actions/Jetstream/CreateTeam.php` | Lógica al crear un equipo |
| `app/Actions/Jetstream/AddTeamMember.php` | Agrega miembro; verifica permiso `add members teams` vía Gate |
| `app/Actions/Jetstream/InviteTeamMember.php` | Envía invitación por correo |
| `app/Actions/Jetstream/RemoveTeamMember.php` | Elimina miembro del equipo |
| `app/Actions/Jetstream/DeleteTeam.php` | Elimina equipo completo |
| `app/Actions/Jetstream/UpdateTeamName.php` | Renombra equipo |
| `app/Http/Middleware/TeamsPermission.php` | **Crucial**: llama `setPermissionsTeamId()` en cada petición para que Spatie use el equipo activo del usuario |
| `app/Http/Middleware/HandleInertiaRequests.php` | También llama `setPermissionsTeamId()`; inyecta permisos del equipo activo en los props de Inertia |
| `app/Providers/JetstreamServiceProvider.php` | Registra las Actions de Jetstream |
| `config/jetstream.php` | Configuración de features (teams, 2FA, profile photos, etc.) |

### Frontend

Las páginas de gestión de equipos las provee Jetstream directamente via Inertia. Están en el vendor pero se pueden publicar con `php artisan vendor:publish --tag=jetstream-views`.

---

## Flujo de contexto de equipo

```
Request HTTP
    │
    ▼
Middleware: TeamsPermission
    │  setPermissionsTeamId($user->current_team_id)
    │  $user->getAllPermissions()  ← cargado con scope del equipo activo
    ▼
Middleware: HandleInertiaRequests::share()
    │  Construye auth.permissions (mapa clave→bool) para el frontend
    ▼
Controlador → Servicio → Respuesta Inertia
    │
    ▼
Vue Page (recibe auth.permissions como prop compartido)
```

---

## Cambiar de equipo activo

Jetstream provee la ruta `PUT /current-team` que actualiza `current_team_id` en el usuario. Al hacer el siguiente request, el middleware recarga los permisos del nuevo equipo. No hay lógica adicional que implementar.

---

## Notas importantes

- `team_user.role` es el **rol de Jetstream** (string libre, ej. `"admin"`, `"editor"`). Es independiente de los roles de Spatie.
- Los **roles de Spatie** (con permisos reales) se asignan por separado desde `Admin/Roles` y se guardan en `model_has_roles` con `team_id`.
- Nunca verificar permisos sin antes haber llamado `setPermissionsTeamId()`. El middleware lo hace automáticamente en peticiones HTTP, pero en comandos Artisan o Jobs hay que llamarlo manualmente.
- Para agregar un miembro a un equipo, el usuario ejecutor necesita el permiso Spatie `add members teams`.
