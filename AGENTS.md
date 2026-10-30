# Guía del repositorio

Este archivo reúne las instrucciones compartidas por los agentes que trabajan en Kronik. `CLAUDE.md` solo lo referencia; no mantener reglas paralelas.

## Arquitectura y estructura

Kronik usa Laravel 11, Inertia, Vue 3, Jetstream, Sanctum, PrimeVue/Sakai, Tailwind y Vite. El frontend recibe páginas y props mediante Inertia desde `routes/web.php`; `routes/api.php` atiende consumidores externos con Sanctum. El flujo habitual es ruta → controlador → servicio de negocio → `Inertia::render()` → componente Vue.

- Backend: controladores en `app/Http/Controllers/`, validación en `app/Http/Requests/`, lógica de negocio en `app/Services/`, modelos en `app/Models/` y autorización en políticas, middleware y proveedores.
- Frontend: páginas en `resources/js/Pages/`, componentes compartidos en `resources/js/Components/`, servicios HTTP en `resources/js/Services/`, composables en `resources/js/Composables/` y layouts en `resources/js/Layouts/`.
- Estilos y recursos: `resources/css/`, `resources/external/` y `public/`. Minimizar cambios a la plantilla externa `resources/external/sakai-vue/`.
- Datos: migraciones, seeders, factories y catálogos en `database/`. Pruebas en `tests/Feature/`, `tests/Unit/` y `tests/E2E/`; pruebas de componentes junto al frontend.
- Rutas adicionales y tareas programadas: `routes/console.php`.

Mantener controladores delgados y delegar lógica de negocio a servicios. Usar Form Requests para validación y `app/Http/Resources/` para las respuestas API que los utilicen. Los formularios de clientes comparten `BaseClienteRequest` y `ExtendRulesTrait`; revisar ese contrato antes de duplicar reglas.

### Frontend y autorización

`HandleInertiaRequests::share()` expone las capacidades de Jetstream, navegación y `auth.permissions`, evaluados mediante Gate. Los permisos de Spatie dependen del contexto de equipo. Fuera del middleware, establecer `setPermissionsTeamId($teamId)` antes de verificarlos y restaurar el contexto previo si se cambia temporalmente. Para acciones sobre un equipo seleccionado, usar su contexto y autorización, sin asumir que coincide con `current_team_id`. Distinguir la bandera global `is_super_admin` de los roles por equipo y de la responsabilidad del equipo.

Alias de Vite: `@` → `resources/js/`, `@css` → `resources/css/`, `@config` → `resources/config/`, `@services` → `resources/js/Services/`, `@components` → `resources/js/Components/`, `@composables` → `resources/js/Composables/` y `@sakai-vue` → `resources/external/sakai-vue/`. PrimeVue se autoimporta mediante `unplugin-vue-components`.

## Comandos de trabajo

| Objetivo | Comando |
| --- | --- |
| Instalar dependencias | `composer install` y `npm install` (`npm ci` para instalación reproducible con lockfile) |
| Desarrollo completo | `composer dev` |
| Servidor Laravel | `php artisan serve` |
| Vite con recarga | `npm run dev` |
| Compilar frontend | `npm run build` |
| Pruebas backend | `php artisan test` o `vendor/bin/pest` |
| Prueba backend específica | `php artisan test --filter NombrePrueba` |
| Pruebas unitarias frontend | `npm run test:unit` |
| Formatear PHP | `vendor/bin/pint` |
| Formatear frontend y recursos públicos | `npm run format` |
| Procesar cola | `php artisan queue:work` |
| Ejecutar migraciones pendientes | `php artisan migrate` |
| Ejecutar seeders | `php artisan db:seed` |
| Actualizar catálogo SAT CFDI | `php artisan catalog:update-sat-cfdi-v4` |
| Actualizar catálogo Sepomex | `php artisan catalog:update-sepomex` |
| Crear permiso | `php artisan permission:create` |

Confirmar entorno y efectos antes de migraciones o seeders. `php artisan migrate:fresh --seed` elimina datos: no es un paso rutinario para preparar una revisión. Para QA, seguir la guía de reinicio correspondiente. Consultar [comandos Artisan](docs/reference/comandos-artisan.md) y configuración vigente; no asumir drivers de sesiones/colas a partir de un ejemplo de `.env`.

## Estilo y validación

Usar cuatro espacios, LF, UTF-8 y salto de línea final según `.editorconfig`. PHP sigue las convenciones de Laravel: clases StudlyCase, modelos singulares y sufijos `Controller`, `Request` y `Service`. Los componentes Vue usan PascalCase, los composables `useNombre.js` y JavaScript usa comillas dobles según `biome.json`.

Todos los errores expuestos al usuario deben estar en español claro. Nunca mostrar claves como `validation.required`, `validation.integer` o `validation.in`. Al cambiar validaciones, revisar `lang/es/validation.php`, mensajes y nombres de atributos del dominio, y añadir una aserción de regresión para los flujos afectados. Ver [validación de formularios](docs/reference/validacion-formularios.md).

## Pruebas y entregas

Usar Pest con helpers de Laravel. Las pruebas HTTP, autenticación, equipos y flujos pertenecen a `tests/Feature/`; la lógica aislada, a `tests/Unit/`. Nombrar las pruebas por comportamiento. Añadir o ajustar pruebas al cambiar controladores, requests, políticas, servicios, comandos, migraciones o flujos Inertia; cubrir lógica y permisos del frontend con las herramientas disponibles. Los E2E críticos deben usar datos y una BD aislados, sin depender de proveedores externos para resultados deterministas.

Usar Conventional Commits, preferentemente en español: `feat(modulo): ...`, `fix(modulo): ...`, `refactor: ...`, `docs: ...`. La descripción de la PR debe explicar problema y comportamiento final, enlazar la tarea cuando exista, registrar validaciones y límites, indicar migraciones/seeders/configuración y aportar capturas si cambia la UI. Aplicar la [definición de terminado](docs/reference/definicion-terminado.md) y el [flujo de tareas, pruebas y PR](docs/reference/flujo-tareas-pruebas-pr.md).

Cada PR requiere `/review medium` y resolución de hallazgos antes de integrarse.
Una funcionalidad puede abarcar varias PR y sesiones: antes de cerrarla, hacer un
barrido conjunto de todas sus PR contra la base funcional y registrar la evidencia.
Notion y la Agent Note mantienen el traspaso entre sesiones; no cerrar la tarea
solo porque una PR esté integrada.

## Seguridad e integraciones

No versionar secretos de `.env`; documentar nuevas variables en `.env.example` y en su referencia. Usar `env()` en archivos de configuración y `config()` en el resto del código para respetar el caché de configuración. Tratar SIC/Círculo de Crédito, geocodificación y scrapers como superficies sensibles; no fijar credenciales o endpoints en código.

Las consultas SIC se coordinan mediante los servicios actuales de `app/Services/SICs/`; no asumir que los archivos históricos de `app/Actions/CirculoDeCredito/` implementan el flujo vigente. MapLibre se usa para mapas; `GeocodingService` implementa `GeocodingServiceInterface`; los catálogos SAT/Sepomex tienen sus comandos y scrapers. Para PDF, consultar `config/documentos.php` y la documentación de renderizado: el motor predeterminado es Browsershot y sus dependencias incluyen Node y Chrome; no asumir que LibreOffice, wkhtmltopdf o Pandoc son requisitos generales.

## Cobertura obligatoria de auditoría

Toda creación o modificación de un módulo debe revisar y mantener sus eventos auditables dentro de la misma entrega, sin esperar otro recordatorio del usuario. Implementar los eventos correspondientes mediante `App\Enums\ActivityEvent` y `App\Services\ActivityLogService`, actualizar [la matriz de eventos](docs/reference/auditoria-eventos.md) y añadir o ajustar pruebas de comportamiento. Para cambios solo visuales o sin nuevas acciones auditables, justificar por qué no hacen falta eventos adicionales; evitar registros duplicados o sin significado.

Cubrir acciones críticas de negocio, ciclo de vida de cuentas, equipos e integrantes, roles/permisos y acciones administrativas, incluidas las de Super Admin. En cuentas, distinguir creación pendiente, envío/reenvío y fallo operativo de invitación, activación al establecer la contraseña inicial, recuperación y cambio de contraseña. No confundir aceptación por el transporte de correo con entrega efectiva. Conservar actor, recurso afectado, resultado y contexto original de equipo/sucursal. Las colas y procesos automáticos deben distinguir al sistema ejecutor del usuario iniciador y no reconstruir el contexto original desde preferencias mutables del usuario.

Respetar la lista de metadatos permitidos del contrato de auditoría. Nunca registrar contraseñas, hashes de contraseña, tokens, enlaces de invitación/recuperación, secretos, respuestas SIC, contenido documental ni valores sensibles anteriores/posteriores. Conservar motivos reservados y evidencias en registros de dominio protegidos, referenciados por identificador. Probar emisión, actor/sujeto, aislamiento de equipo/sucursal, resultados, ausencia de datos sensibles y reintentos cuando apliquen. Un caso del enum o un evento de Jetstream no demuestra por sí solo que se persistió actividad auditable.

Mantener una única matriz en `docs/reference/auditoria-eventos.md`: usar el catálogo `modules` como inventario y cruzarlo con enum, emisores reales y pruebas. Incluir autenticación, perfil y procesos transversales aunque no tengan módulo propio. Registrar módulo, acción/disparador, código, actor/iniciador, sujeto, resultado, contexto, metadatos, permisos de consulta, estado, emisor y evidencia de pruebas; justificar lo no aplicable. La auditabilidad técnica aporta evidencia y no certifica por sí sola cumplimiento legal; retención e integridad deben seguir políticas aprobadas.

## Coordinación del proyecto

El [Notion de Kronik](https://app.notion.com/p/1a261db7db7f800c809bf96e22e0d05a) define alcance, orden y prioridades del producto. Los backlogs locales son contexto de apoyo, no una segunda lista de prioridades. El código y las pruebas del commit correspondiente acreditan el comportamiento implementado. Comenzar a buscar documentación en [el índice general](docs/index.md) o [el índice por temas](docs/topic/index.md).

Los backlogs 02 (PR #11), 01.5 (PR #12), 03 (PR #14) y 03.5 (PR #15) están completos. P0–P8 de la iniciativa coordinada 04–07 están integrados para QA (PR #36), incluido SIC sandbox/historial y anticipos/liquidación/cierre; seguir ADR 0022/0023 y sus guías. SIC productivo permanece como entrega posterior validada; 2FA y revisión manual están aprobados por el usuario. La [matriz de auditoría y actividad](https://app.notion.com/p/21461db7db7f80d5b813c2899522a72b), referencia histórica 02.5, fue repriorizada el 26/10/2026 para el 31/10/2026 y su cobertura sigue pendiente. No habilitar SIC productivo ni operaciones con dinero real sin validar proveedor y políticas financieras, fiscales y de cumplimiento. Contrastar estos estados con la evidencia vigente al retomar cada tema.

## Documentación viva obligatoria

- **Diátaxis:** aprendizaje en `docs/tutorials/`, procedimientos en `docs/how-to/`, contratos/configuración/comandos exactos en `docs/reference/` y contexto/decisiones en `docs/explanation/`. Mantener un documento canónico por comportamiento y enlazarlo desde los demás.
- **Navegación temática:** cada ficha `docs/topic/<tema>.md` explica brevemente el tema y agrupa enlaces descriptivos. Registrarla en `docs/topic/index.md`. Cada documento Diátaxis debe ser accesible desde su índice de sección y al menos una ficha temática; los compartidos pueden aparecer en varias. Las fichas orientan: no duplican guías, especificaciones o estados. Actualizar índices y enlaces entrantes al crear, mover o renombrar documentos.
- **ADR:** registrar decisiones arquitectónicas en `docs/explanation/adr-*.md` cuando afecten modelos de dominio, límites de módulos, integraciones, seguridad, despliegue o elecciones técnicas duraderas. Conservar contexto, decisión, consecuencias y estado; enlazar el ADR que sustituya una decisión y preservar su justificación histórica. Los ajustes documentales rutinarios no requieren ADR.
- **Agent Notes:** mantener un archivo por backlog o tarea transversal en `docs/agent-notes/`, siguiendo [la plantilla](docs/agent-notes/template.md). Registrar rama/PR, estado fechado, evidencia, bloqueos y siguiente paso. Actualizar ante traspasos, PR/integración, despliegue y resultados de QA. Al cerrar, condensar la nota en evidencia final y pendientes reales. No guardar secretos ni duplicar especificaciones completas de Notion o instrucciones operativas permanentes.
- **Cuándo actualizar:** todo cambio de comportamiento, permisos, comandos, seeders, migraciones, variables, integraciones o UI exige revisar sus documentos antes de cerrar. Integración/despliegue y confirmaciones posteriores del usuario también requieren actualizar notas, resúmenes y Notion afectados. No dejar como pendiente un caso ya confirmado; registrar los resultados ya autorizados sin solicitar otra confirmación.
- **Evidencia y alcance:** distinguir implementado, integrado, desplegado y validado. Enlazar commits/PR/pruebas e identificar QA reportado por el usuario. Fechar la evidencia y registrar entorno/cliente cuando se conozcan; no inventar versiones, escenarios o preparación productiva. Los conteos de pruebas pertenecen al commit que los produjo.
- **Cierre:** actualizar documentación, índices, Agent Note y Notion correspondientes; verificar enlaces locales y cobertura temática. Hooks/CI solo comprueban presencia de cambios documentales, no exactitud ni vigencia. No satisfacerlos editando documentación ajena al cambio. Para entregas exclusivamente documentales, revisar contenido y enlaces; no hacen falta pruebas funcionales nuevas. Consultar [documentación viva](docs/reference/documentacion-viva.md).

Para detalles del dominio, leer solo lo pertinente: [equipos Jetstream](docs/equipos-jetstream.md), [roles y permisos](docs/reference/roles-y-permisos.md), [navegación dinámica](docs/menubar-items.md) y las fichas del índice temático.
