# CLAUDE.md

Este archivo proporciona orientación a Claude Code (claude.ai/code) al trabajar con el código de este repositorio.

## Documentación temática

Documentos detallados sobre temas específicos de la aplicación. Leer solo cuando sea relevante para la tarea en curso.

| Documento | Contenido |
|-----------|-----------|
| [Equipos de Jetstream](docs/equipos-jetstream.md) | Cómo funcionan los equipos, su rol en el ERP financiero, archivos involucrados, flujo de contexto de equipo |
| [Roles y Permisos](docs/roles-y-permisos.md) | Stack Spatie + Jetstream, modelo de datos, archivos backend/frontend, flujo de verificación, módulos |
| [MenubarItems](docs/menubar-items.md) | Cómo funciona el sistema de navegación dinámica, tipos de items, módulos, pivot routes, archivos clave, TODO pendientes |

---

## Comandos

### Frontend
```bash
npm run dev        # Servidor Vite con HMR
npm run build      # Compilación para producción
npm run format     # Formatear con Biome (solo resources/ y public/)
```

### Backend
```bash
php artisan serve                          # Servidor local de desarrollo
php artisan migrate                        # Ejecutar migraciones pendientes
php artisan migrate:fresh --seed           # Reset completo + seeders
php artisan db:seed                        # Seeders sin reset
php artisan make:controller Nombre         # Generar controlador
```

### Pruebas
```bash
php artisan test                           # Todas las pruebas
php artisan test --filter NombrePrueba    # Prueba específica
php artisan test tests/Feature/Auth       # Archivo/directorio específico
./vendor/bin/pest                         # Pest directamente
```

### Comandos de Catálogos
```bash
php artisan catalog:update-sat-cfdi-v4    # Actualizar catálogo SAT CFDI
php artisan catalog:update-sepomex        # Actualizar catálogo de códigos postales
php artisan permission:create             # Crear nuevo permiso
```

## Arquitectura

### Stack
Laravel 11 + Inertia.js + Vue 3. No hay API separada para el frontend — Inertia renderiza páginas del lado del servidor y pasa props directamente. `routes/web.php` sirve el SPA de Vue. `routes/api.php` es para consumidores externos (protegido con Sanctum).

### Ciclo de Vida de una Petición
1. La petición llega a una ruta de Laravel (`routes/web.php`)
2. El controlador llama a `app/Services/` para la lógica de negocio
3. El controlador retorna `Inertia::render('NombrePagina', $props)`
4. El componente Vue en `resources/js/Pages/` recibe los props

### Datos Compartidos de Inertia
`HandleInertiaRequests::share()` inyecta en cada página:
- `jetstream.*` — flags de características de Jetstream
- `auth.permissions` — mapa plano clave→booleano de permisos del equipo actual (resuelto vía Spatie + Gate por petición)

Los permisos están acotados a `current_team_id` — el contexto de equipo de Spatie se establece en el middleware antes de cada verificación de permisos.

### Autorización
Spatie `laravel-permission` con equipos habilitado. Roles y permisos están acotados por equipo. Siempre llamar `setPermissionsTeamId($teamId)` antes de verificar permisos fuera del middleware. Los Gates están registrados pero los permisos fluyen a través de Spatie.

### Capa de Servicios
La lógica de negocio pesada vive en `app/Services/`. Servicios clave:
- `ClienteService` — CRUD de clientes + validaciones
- `GeocodingService` — geocodificación de direcciones (implementa `GeocodingServiceInterface`)
- `SICs/CirculoDeCredito/` — integraciones con Círculo de Crédito (FicoScore v2, FintechScore, RCFicoScore); invocados vía `app/Actions/CirculoDeCredito/`
- `Scrapers/` — scrapers de catálogos SAT CFDI v4 y Sepomex (ejecutados vía comandos Artisan)

### Estructura del Frontend
- `resources/js/Pages/` — componentes de página Inertia (mapeo 1:1 con rutas)
- `resources/js/Components/` — componentes Vue reutilizables
- `resources/js/Services/` — servicios cliente basados en axios
- `resources/js/Composables/` — composables Vue 3 (utilidades MapLibre)
- `resources/external/sakai-vue/` — plantilla admin PrimeVue Sakai (tratar como externo; minimizar ediciones)

### Alias de Rutas (Vite)
| Alias | Resuelve a |
|-------|-----------|
| `@` | `resources/js/` |
| `@css` | `resources/css/` |
| `@config` | `resources/config/` |
| `@services` | `resources/js/Services/` |
| `@components` | `resources/js/Components/` |
| `@composables` | `resources/js/Composables/` |
| `@sakai-vue` | `resources/external/sakai-vue/` |

Los componentes PrimeVue se auto-importan vía `unplugin-vue-components` — no se necesitan importaciones manuales en los SFCs de Vue.

### Modelos de Datos
Modelos principales: `Cliente`, `User`, `Team` (equipos de Jetstream), `MenubarItem` (navegación dinámica).  
Modelos de permisos gestionados por Spatie: `Role`, `Permission` (acotados por equipo).  
Registro de actividad vía `spatie/laravel-activitylog` — modelos con el trait `LogsActivity` registran cambios automáticamente.

### Integraciones Externas
- **Círculo de Crédito** — buró de crédito; tres productos: FicoScore v2, FintechScore, Reporte de Crédito (RCFicoScore). Credenciales y endpoints en `.env`.
- **SAT** — catálogo de facturación electrónica CFDI v4; se actualiza vía comando Artisan + scraper.
- **Sepomex** — códigos postales de México; se actualiza vía comando Artisan + scraper.
- **MapLibre** — mapas de domicilio de clientes en el frontend.
- **LibreOffice / wkhtmltopdf / Pandoc** — binarios del sistema requeridos para generación de documentos; deben instalarse en el servidor.

### Linting / Formateo
- **PHP**: Laravel Pint (`./vendor/bin/pint`) — PSR-12
- **JS/Vue/CSS**: Biome (`npm run format`) — indentación 4 espacios, comillas dobles, acotado a `resources/` y `public/`

### Colas y Sesiones
Sesiones y colas usan el driver `database` (configurado en `.env`). Ejecutar `php artisan queue:work` para trabajos asíncronos.

## Convenciones Clave
- Controladores delgados — delegar lógica a `app/Services/`.
- Validación de formularios vía `app/Http/Requests/` (formularios de cliente extienden `BaseClienteRequest` usando `ExtendRulesTrait`).
- Respuestas API usan `app/Http/Resources/` (recursos estilo JSON:API).
- Llamadas a `env()` solo permitidas dentro de archivos `config/` — usar `config('clave')` en el resto del código (requerido para que el caché de configuración funcione).
