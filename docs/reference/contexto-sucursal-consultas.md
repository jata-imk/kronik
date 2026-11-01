# Contexto de sucursal y consultas

## Contrato

Equipo actual determina permisos; sucursal activa determina contexto operativo.
Los equipos no son tenants de solicitudes o créditos. La pertenencia a sucursales
no sustituye los permisos de consulta institucional documentados en ADR 0005.

Las bandejas de Solicitudes, Mi trabajo, Evaluación, Cumplimiento, Créditos y SIC
usan `scope=current` por defecto. `scope=all` mantiene la consulta institucional
ya autorizada por sus policies y permisos específicos. `sucursal_id` acota esa
consulta sin modificar preferencias. Los enlaces antiguos de Créditos que solo
incluyen `sucursal_id` mantienen su alcance explícito. Sin sucursal activa, el
alcance actual produce una colección vacía y un aviso, nunca un consolidado.
El tablero deja de presentar automáticamente un consolidado a Super Admin sin
activa. El historial contextual de cliente conserva `all` por defecto para
mostrar su historia completa, incluidas consultas anteriores al traslado.

Clientes/Expedientes usa paginador Laravel (`clientes.data`, `total`, `per_page`,
`current_page` y enlaces). Búsqueda, filtros de columnas, orden y paginación se
resuelven antes de serializar. `ClienteListadoService` admite únicamente campos
permitidos; las columnas llegan como JSON en `columnas`, no como SQL. Los botones
de importación/exportación sin endpoint no se ofrecen. No se implementa XLSX.
La API v1 conserva la colección histórica sin parámetros; `scope`, `sucursal_id`,
`buscar` o `por_pagina` solicitan consulta paginada. `scope=current` sin activa
produce validación española. Sanctum establece el equipo Spatie antes de Gates
y restaura el contexto anterior al salir, incluido un rechazo.

## Operaciones e historia

Solicitudes, contratos, SIC y operaciones de crédito conservan sus comprobaciones
de sucursal responsable en servicios, incluso con Super Admin. La excepción
administrativa documentada de Clientes no se extiende a esos módulos.
Conciliar SIC exige la sucursal original del intento aunque se traslade el cliente;
se mantienen los permisos funcionales existentes, sin convertir el equipo original
del intento en propietario exclusivo. Un contexto histórico desconocido no se
reconstruye desde preferencias mutables. La consulta de evidencia SIC y de un
PDF generado conserva la sucursal almacenada del recurso en su evento.

Cambiar sucursal registra `user.active_branch_changed` solo si cambia el valor,
en la misma transacción, con sucursal anterior y equipo iniciador capturados,
usuario como sujeto y destino por identificador. Filtrar/paginar no genera eventos.
Los workers SIC y PDF continúan usando sus contextos persistidos.

## Excepciones y seguimiento

Productos, plantillas, catálogos e institución son globales. Roles usa equipo
seleccionado y `TeamContext`; responsabilidad del equipo no concede permisos.
La bitácora mantiene [ADR 0031](../explanation/adr-0031-consulta-actividad-equipo-sucursal.md):
sucursales como filtros del equipo autorizado, incluidas no asignadas e inactivas.

Usuarios, Equipos, Roles, Sucursales, Productos, Plantillas y menú filtran y
paginan en servidor. Bitácora y biblioteca de imágenes ya lo hacían: se reutilizan.
Los arrays administrativos conservan su nombre y añaden `listado` con `total`,
`current_page`, `per_page` y `filters`; Clientes usa su paginador nativo.
`ListadoParametros` solo valida parámetros y aplica mapas explícitos de cada
servicio: no autoriza, no infiere columnas y no impone aislamiento universal.

## Parámetros, selección y recarga

Los listados admiten `buscar`, `columnas` (objeto JSON con claves permitidas),
`orden`, `direccion`, `por_pagina` (10/25/50) y `page`. Usuarios admite selección
independiente mediante `edit_user_id`; Sucursales usa `selected_sucursal_id` para
folios; Roles usa `team` y `selected_role_id`. Un ID de rol ajeno al equipo o a su
guard produce 404, incluso con Super Admin. Eliminar el seleccionado limpia su
selección y conserva el equipo autorizado.

Productos usa `producto_id`; Plantillas usa `plantilla_id` y `version_id`, siempre
validado dentro de la plantilla. El detalle se entrega aparte de la página del
catálogo (`productoSeleccionado` / `plantillaSeleccionada`). Sus versiones usan
`versiones_page`, `versiones_por_pagina` y `versionesListado`. La última versión
de producto y la preferida de plantilla no se calculan desde la página visible.
Los totales de borradores, plantillas activas y documentos abarcan la consulta
filtrada completa. Crear o duplicar selecciona el nuevo recurso/borrador.

Integrantes e invitaciones de equipo tienen páginas independientes; el responsable
cuenta una sola vez aunque también figure en `team_user`. Las invitaciones usan
`invitaciones_*`, las tarjetas de roles `roles_*`, los roles para invitar
`invitar_roles_*` y los integrantes de un rol `integrantes_*`. El sufijo
`por_pagina` cambia el tamaño; `page` indica la página. Los parámetros de búsqueda
son `invitaciones_buscar`, `invitar_roles_buscar` e `integrantes_buscar`.

Los selectores remotos de usuarios/equipos/roles/sucursales y responsables devuelven
hasta 20 sugerencias, conservando por separado las asignaciones seleccionadas,
incluidas las históricas inactivas. No se elimina una asignación por estar fuera
de página. El selector institucional de personas vinculables busca remotamente;
los propietarios de garantías siguen limitados a relaciones admitidas por el dominio.
El menú pagina raíces y conserva sus subárboles de edición. Matrices de permisos,
variables de plantillas, catálogos finitos de configuración y tablas calculadas de
simulación se entregan completos para editar/calcular; no son bandejas operativas.

`contextoOperativo` se comparte con `Inertia::always`, también en recargas parciales.
`GET /contexto-operativo` permite comprobar únicamente equipo/sucursal del usuario
sin depender de los permisos de la página abierta. Las respuestas autenticadas se
marcan privadas y sin caché. Pestañas, foco, restauración histórica y cambios de
marcador revalidan el contexto. Si cambió, se cancela la visita anterior y se hace
una navegación completa: módulos operativos vuelven a su bandeja, sin filtros ni
formularios anteriores; un cambio de equipo vuelve al tablero. Los recursos
globales conservan su URL al cambiar de sucursal. Las respuestas tardías de
selectores se descartan por revisión/abortado. El servidor vuelve a autorizar cada
petición aunque el navegador aún muestre un contexto anterior.

Los eventos de expediente sin sucursal propia usan la responsabilidad actual del
cliente; el traslado conserva la sucursal de origen en su evento. SIC y PDF
versionados usan siempre su sucursal persistida, incluso si es desconocida (`null`).
No se rellena historia ni se modifica la sucursal de créditos/documentos por trasladar
un cliente. No hay migraciones, seeders, variables ni permisos nuevos.

## Matriz transversal

Inventario: los 19 nombres de `ModulesAndPermissionsSeeder` contrastados con
`routes/web.php`, `routes/api.php`, policies, servicios y pruebas. «Antes» describe
la base `ee94025`; «actual» describe esta entrega. La matriz no sustituye la
[matriz única de eventos](auditoria-eventos.md).

| Módulo / superficie | Naturaleza | Esperado y fundamento | Antes / actual | Filtro y autorización del servidor | Brecha o límite | Pruebas y evidencia |
| --- | --- | --- | --- | --- | --- | --- |
| `dashboard` `/dashboard` | Mixto | Indicadores por activa; consolidado explícito con permisos por indicador (ADR 0005/0036) | Antes SA sin activa consolidaba; actual sin activa no amplía, selector y enlaces coherentes | `ConsultaSucursal`, Gates de Clientes/Solicitudes/Créditos antes de contar; pendientes limitados a 5 | No es un listado completo de pendientes: enlaza Mi trabajo | `ConsultaSucursalTest`, `SolicitudesTest`, E2E contexto |
| `admin` `/admin` | Global | Centro de navegación, sin cartera propia | Conserva accesos según Gates | Navegación y permisos del equipo actual | No corresponde filtro de sucursal | `MenubarServiceTest`, `SicSafetyTest` |
| `users` `/admin/users`, `/opciones` | Global | Administración institucional; asignaciones no equivalen a filtro de visibilidad (ADR 0005/0027) | Antes colección en Vue; actual consulta SQL y edición fuera de página | `read/create/update/delete users`; columnas permitidas; opciones con la misma autoridad de lectura | Conserva alcance administrativo existente; no se crea un tenant de equipo | `ListadosServidorTest`, `UserLifecycleTest`, `TeamMemberActionsTest` |
| `roles` `/admin/roles?team=` | Equipo, sin tenant de cartera | Equipo seleccionado y membresía + permiso propio (ADR 0025/0027) | Actual roles e integrantes paginados, selección independiente | `AuthorizeRoleTeam`, `TeamContext`, guard web, rol del equipo; restauración Spatie | Matriz finita de permisos completa para edición | `RoleManagementTest`, `RoleMembersTest`, `SelectedTeamManagementTest`, `ListadosServidorTest`, E2E roles |
| `menubar-items` `/admin/menubar-items` | Global | Configuración de navegación institucional | Antes árbol completo; actual raíces paginadas y búsqueda de descendientes | Permisos específicos CRUD; búsqueda SQL con ancestros | El subárbol de una raíz se conserva para edición | `MenubarItemsSeederTest`, `MenubarServiceTest`, `ListadosServidorTest` |
| `activity-log` `/admin/users/activity`, CSV | Mixto: equipo + contexto histórico | Todas las sucursales históricas del equipo autorizado (ADR 0031), no activa forzosa | Ya resuelto: conservar query/paginación/CSV del servicio existente | `ActivityLogQueryService`, pertenencia y `read activity-log` del equipo seleccionado; SA explícito | Backlog general de cobertura auditable sigue abierto | `Admin/ActivityLogTest`, `SelectedTeamManagementTest`, E2E bitácora |
| `configuracion-empresa` `/admin/configuracion-empresa` | Global | Una instalación, estado operativo y datos documentales compartidos | Sin aislamiento artificial por activa | Permisos de empresa y `EmpresaOperacionService` en operaciones | No se alteran políticas financieras pendientes | `Admin/EmpresaConfiguracionTest`, `EmpresaOperacionTest` |
| `sucursales` `/admin/sucursales`, folios | Global: catálogo; folios por recurso | Administrar todas con permiso; folios de la sucursal seleccionada | Antes filtros Vue; actual filtros SQL, totales y selección de folios aparte | Permisos CRUD/folios; selección por ID autorizada | Inactivas consultables explícitamente; no operables por preferencia | `Admin/SucursalTest`, `ListadosServidorTest`, `BranchContextTest`, E2E folios |
| `clientes` `/clientes`, expedientes, API v1 | Sucursal | Activa por defecto e institucional con `read clientes`; escritura responsable y excepción administrativa SA (ADR 0005) | Antes ya había alcance; filtros/páginas ahora SQL, API establece/restaura Spatie | `ClientePolicy`, `BaseClienteRequest`, scope, columnas permitidas; hijos validan propiedad del cliente | API sin parámetros conserva colección compatible; sin exportador XLSX existente | `ConsultaSucursalTest`, `BranchContextTest`, `Clientes/ClientePermissionProfilesTest`, pruebas expediente/documentos/consentimientos |
| `productos-crediticios` `/productos-crediticios`, versiones/simulación | Global | Productos de toda instalación (ADR 0006/0036), sin pertenencia de sucursal | Antes colección Vue; actual catálogo y versiones paginados, selección independiente | `ProductoCrediticioPolicy`, permisos funcionales, versión perteneciente al producto | Cálculos y opciones elegibles son configuración global, no cartera | `ProductosCrediticiosTest`, `CapacidadesCreditoTest`, `ListadosServidorTest`, E2E productos |
| `plantillas-documentos` `/plantillas-documentos` | Global | Plantillas/versiones institucionales (ADR 0008) | Actual páginas SQL, detalle independiente y totales completos | `DocumentoPlantillaPolicy`, versión de la plantilla, permisos de activación/retiro/preview | No forzar activa operativa en catálogo | `Documentos/DocumentoPlantillaTest`, `DocumentoEditorTest`, `ListadosServidorTest`, E2E documentos |
| `documentos` expediente, status, PDF/descarga, biblioteca | Mixto | Plantilla global; archivos privados ligados a cliente/solicitud y sucursal original | Generados y biblioteca ya paginaban; actual auditoría no usa preferencias del lector | Policies de cliente/documentos, pertenencia de hijo, `RespuestaArchivoPrivado`; worker usa contexto almacenado | Históricos null permanecen desconocidos; no se genera exportación nueva | `Documentos/DocumentoPlantillaTest`, `Clientes/ClienteDocumentoTest`, `EmpresaOperacionTest` |
| `historial-crediticio` historial general/cliente y detalle SIC | Sucursal original | Bandeja activa; historial de cliente completo autorizado, incluso tras traslado | Defecto reproducido: general no filtraba; actual usa `sic_queries.sucursal_id` | `read historial-crediticio` + acceso de cliente; detalle directo autorizado, sin payload en listado | null aparece solo en alcance institucional; no inferir desde cliente trasladado | `ConsultaSucursalTest`, `SicSafetyTest`, `SicQaTest` |
| `circulo-credito` `/consultas-sic`, búsqueda y conciliación | Sucursal original | Operar responsable; conciliación del intento original confirmada por usuario | Selector sin activa ya no ofrece huérfanos; conciliación permite historia del trasladado desde origen | Permisos funcionales en contexto de equipo; asignación/activa y validación del servicio incluso SA | SIC QA solamente; producción sin habilitar | `SicQaTest`, `SicSafetyTest`, `ConsultaSucursalTest` |
| `teams` `/admin/teams`, `/teams/{id}` | Global catálogo; equipo seleccionado en gestión | Responsabilidad distinta de autorización; equipo no posee créditos (ADR 0027) | Actual SQL para equipos, integrantes, pendientes, roles; responsable una sola vez | `TeamPolicy`, `TeamContext`, capacidades del seleccionado; `read users` conserva autoridad administrativa vigente | Selección de otro equipo no cambia preferencias del actor | `SelectedTeamManagementTest`, `TeamHubTest`, `TeamMemberActionsTest`, `ListadosServidorTest`, E2E equipos |
| `creditos` `/creditos`, pagos/anticipos, descarga | Sucursal original | Activa por defecto, lectura institucional; mutaciones en responsable (ADR 0022/0023) | Antes general por defecto; actual alcance SQL, capacidades visuales coherentes y guardas directas | `CreditoPolicy`, servicios desembolso/pagos/anticipos/reverso y snapshots originales, también SA | Pagos son agregado del crédito; no se cambia cálculo ni dinero real | `CreditoPagosTest`, `CreditoAnticiposTest`, `ConsultaSucursalTest`, `EmpresaOperacionTest` |
| `solicitudes` `/solicitudes`, `/mi-trabajo`, paquete/firmas | Sucursal original | Activa por defecto y lectura institucional explícita; cambios sobre responsable | Defecto reproducido en bandejas; actual alcance SQL y responsables remotos del equipo de la acción | `SolicitudPolicy` + servicios con sucursal/estado/lock; detalles, paquete y archivos autorizan en servidor | Recargar una página anterior descarta formulario; no traslada recursos | `SolicitudesTest`, `ConsultaSucursalTest`, suites paquete/firmas/desembolso, E2E contexto |
| `evaluacion-solicitudes` `/evaluacion-solicitudes` | Sucursal original | Misma bandeja por activa, permiso propio para ver/crear dictamen | Antes mezclaba sucursales; actual query común acotada | Gate adicional de evaluación, servicio de solicitud exige sucursal y revisión vigente | Lectura institucional no habilita dictaminar a distancia | `SolicitudesTest`, `ConsultaSucursalTest` |
| `cumplimiento` `/cumplimiento` | Sucursal original | Misma separación lectura/contexto operativo y dictamen restringido | Antes mezclaba sucursales; actual query común acotada | Permiso específico PLD + servicio de solicitud, sin motivos en log técnico | No se cambian criterios de aprobación ni cumplimiento | `SolicitudesTest`, `EmpresaOperacionTest` |

Para comandos, cifras y revisión del commit consultar la
[Agent Note](../agent-notes/contexto-sucursal-transversal.md).
