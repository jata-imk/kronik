# Eventos de auditoría

## Alcance actual

La tabla `activity_log` contiene actividad real; la interfaz administrativa permite
filtrar, paginar y exportar CSV. Esta matriz distingue cobertura implementada,
parcial y pendiente. El inventario parte de los 19 módulos declarados en
`ModulesAndPermissionsSeeder`, no de una consulta a la instalación desplegada.
Se añaden autenticación, perfil y procesos transversales. Actualizado el 26/10/2026.

Originación P2 registra creación, actualización, envío y asignación de solicitudes.
P3 agrega `solicitud.returned`, `solicitud.rejected`, `solicitud.cancelled` y
`solicitud.assessment_recorded`. El log técnico no contiene motivos, fundamentos,
fuentes ni riesgo; el timeline comparte solo identificadores de evidencia/revisión.
Los dictámenes reservados se leen con permisos de la especialidad, no de auditoría.
P3 aprobación añade `originacion.politica_created` y `solicitud.approved`.
La política conserva autor y condiciones; la resolución conserva sus evidencias.
Los logs técnicos continúan sin copiar motivos ni contenido reservado.
Los incrementos QA posteriores incorporan firmas, documentos, desembolsos y pagos.
La existencia de esos emisores no acredita cobertura completa de cada acción ni
preparación productiva. La reconciliación general continúa en la
[tarea de auditoría](https://app.notion.com/p/21461db7db7f80d5b813c2899522a72b), repriorizada para el 31/10/2026.

## Contrato

Cada evento nuevo debe declararse en `App\Enums\ActivityEvent` y registrarse mediante `App\Services\ActivityLogService`. El servicio agrega automáticamente:

- `event`: código estable y apto para filtros.
- `description`: explicación legible de la acción.
- actor (`causer`) y sujeto (`subject`) cuando aplican.
- `team_id` para aislar la consulta por equipo.
- `sucursal_id` para conservar el contexto operativo en que ocurrio el evento.
- `ip`, `user_agent` y `request_id` de la petición.

Los metadatos funcionales permitidos son:

- `changed_fields`: nombres de campos modificados, nunca sus valores.
- `related`: tipo e identificador de un recurso relacionado.
- `state`: estado no sensible.
- `provider` y `product`: integración consultada.
- `result`: resultado operativo.

No se deben registrar nombres, correos, teléfonos, RFC, CURP, domicilios, contenido documental, respuestas SIC, contraseñas, tokens, secretos ni valores anteriores o posteriores de campos sensibles.

Cuando se dispone de contexto capturado, `ActivityLogService::log` acepta
`inheritActorContext: false`: conserva los IDs proporcionados, incluido `null`,
sin reconstruirlos desde preferencias posteriores del actor ni desde el usuario
autenticado por el modelo `Activity`. El valor predeterminado conserva la
compatibilidad de los emisores anteriores. La captura de contexto no se guarda
como atributo adicional en la base de datos.

## Inventario y cobertura

**Consulta Q:** permiso `read activity-log` del equipo autorizado o Super Admin
global. **C:** contexto del actor capturado antes de modificar preferencias.
**T:** equipo afectado y sucursal original del actor. **P:** contexto de la cuenta
que prueba su identidad al establecer/restablecer/cambiar contraseña.
Los resultados de estas pruebas corresponden al código, no a QA desplegado.

| Módulo | Acción/disparador | Código | Actor/iniciador | Sujeto | Resultado | Equipo/sucursal | Metadatos | Consulta | Estado | Emisor | Prueba |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `users` | Alta pendiente, edición y estado, incluidos cambios de acceso global y sucursales secundarias | `user.created`, `user.updated`, `user.deactivated`, `user.reactivated` | Administrador, incluido Super Admin | Usuario | `created` / `applied` | C | `changed_fields`, `state`, `result` | Q | Implementado para rutas de alta, PUT y DELETE; reintentos sin cambio no duplican eventos | `UserManagementService` | `AccountAdministrationAuditTest`, `UserLifecycleTest` |
| `users` | Envío/reenvío, fallo y throttle de invitación | `user.invitation.sent`, `.resent`, `.failed`, `.throttled` | Administrador; sistema si no se proporciona actor | Usuario pendiente | `transport_accepted`, `transport_failed`, `failed`, `rejected`, `throttled` | C; sistema conserva contexto de la cuenta capturado al enviar | `state`, `result` | Q | Implementado; aceptación del transporte no es entrega efectiva | `UserInvitationService` | `AccountAdministrationAuditTest`, `UserInvitationTest` |
| `users` / `teams` | Asignaciones Spatie de un integrante existente | `user.team_roles.updated` | Administrador, incluido Super Admin | Usuario | `applied` | T | `changed_fields`, `related`, `result` | Q | Implementado para cambios reales; no copia nombres ni valores de identidad | `UserAccessAuditService` desde `UserManagementService` | `AccountAdministrationAuditTest` |
| `teams` | Alta/baja de integrante, incluida ruta genérica | `team.member.added`, `team.member.removed` | Administrador o integrante autorizado que sale | Usuario | `applied` | T | `changed_fields`, `related`, `result` | Q | Implementado; creación, edición y eliminación de asignaciones; transacción con dominio | `UserAccessAuditService`, `AddTeamMember`, `RemoveTeamMember` | `AccountAdministrationAuditTest`, `TeamMemberActionsTest` |
| `teams` | Cambio del rol genérico de membresía | `team.member.legacy_role_updated` | Administrador autorizado | Usuario | `applied` | T | `changed_fields`, `result` | Q | Implementado; no representa permisos Spatie; un reintento idéntico no agrega actividad | `App/Actions/Jetstream/UpdateTeamMemberRole` | `AccountAdministrationAuditTest`, `UpdateTeamMemberRoleTest` |
| `teams` | Transferencia del responsable | `team.responsible_transferred` | Administrador autorizado | Equipo | Cambio registrado | T | `changed_fields`, `related` | Q | Implementado | `TeamManagementService` | `SelectedTeamManagementTest` |
| `teams` | Crear, editar y desactivar equipo | Pendiente | Administrador | Equipo | Pendiente | Por definir en emisor | Contrato | Q | Pendiente de reconciliación general; los eventos de Jetstream no prueban persistencia | Sin emisor específico verificado | Pendiente |
| `roles` | CRUD y matriz de permisos del rol | Pendiente | Administrador | Rol | Pendiente | Equipo seleccionado | Contrato | Q | Pendiente de auditoría; el aislamiento funcional sí está implementado. Consulta/etiquetas no añaden una acción auditable | `RoleController` sin emisor de CRUD | `RoleManagementTest` acredita funcionalidad, no auditoría |
| `menubar-items` | Cambios administrativos del menú | Pendiente | Administrador | Menú | Pendiente | Pendiente | Contrato | Q | Pendiente de reconciliación | Sin emisor específico verificado | Pendiente |
| `dashboard` | Consulta del tablero | No aplica nuevo evento | Usuario | Sin mutación | Lectura | Contexto operativo | Ninguno adicional | Permiso del módulo | No aplica en esta entrega: navegación/lectura sin acción nueva | No requiere emisor adicional | QA/E2E de navegación |
| `admin` | Entrada a administración | No aplica nuevo evento | Administrador | Sin mutación | Navegación | Contexto operativo | Ninguno adicional | `access admin` | No aplica en esta entrega; cada acción se audita en su dominio | No requiere emisor adicional | `TeamHubTest` |
| `activity-log` | Consulta y CSV | Pendiente política de auditoría de consulta/exportación | Lector autorizado | Actividad | Lectura/exportación | Equipo autorizado | No se copian registros consultados | Q | Aislamiento implementado; política adicional pendiente | `UserController` | `Admin/ActivityLogTest`, `SelectedTeamManagementTest` |
| `configuracion-empresa` | Guardado de configuración | `empresa.updated` | Administrador | Configuración | Cambio registrado | Contexto del actor | Nombres de campos | Q | Emisor existente; reconciliación general pendiente | `EmpresaConfiguracionController` | `Admin/EmpresaConfiguracionTest` |
| `sucursales` | Alta, edición y desactivación | `sucursal.created`, `.updated`, `.deactivated` | Administrador | Sucursal | Cambio registrado | Contexto del actor | Contrato | Q | Emisores existentes; reconciliación general pendiente | `SucursalController` | `Admin/SucursalTest` |
| `clientes` | Cliente, KYC, referencias, vínculos, garantías, documentos y consentimiento | Familias `cliente.*`, `cliente.documento.*`, `cliente.consentimiento_sic.*` | Operador autorizado | Cliente/recurso de expediente | Cambio o acceso registrado | Equipo y sucursal de la operación | Identificadores y campos; sin expediente | Q | Emisores existentes; reconciliar cada acción en backlog general | Servicios de cliente/expediente y controllers privados | `tests/Feature/Clientes/` |
| `productos-crediticios` | Versiones, activación y retiro | Familia `producto_crediticio.*` | Operador autorizado | Producto/versión | Cambio registrado | Contexto de la operación | Contrato | Q | Emisores existentes; completar reconciliación | Controllers/servicios de productos | `ProductosCrediticiosTest` |
| `plantillas-documentos` | Plantilla, versión y recurso | `documento_plantilla.*`, `documento_recurso.created` | Operador autorizado | Plantilla/recurso | Cambio registrado | Contexto de la operación | Sin contenido de plantilla | Q | Emisores existentes; completar reconciliación | Servicios de documentos | `Documentos/DocumentoPlantillaTest` |
| `documentos` | Generar, fallar, visualizar y descargar | `documento.generacion.*`, `documento.generated`, `documento.viewed`, `documento.downloaded` | Usuario iniciador / ejecutor según emisor | Documento | Resultado operativo | Verificar captura original en cada proceso | Sin contenido documental | Q | Parcial: revisar por proceso, cola y reintento | Servicios/controllers de documentos | `tests/Feature/Documentos/` |
| `historial-crediticio` | Consulta de evidencia SIC | `cliente.sic.evidence_viewed` | Operador autorizado | Evidencia/cliente | Acceso registrado | Equipo y sucursal de la operación | Identificadores; sin respuesta SIC | Q | Emisor existente; completar reconciliación | Superficie privada SIC | `SicQaTest` |
| `circulo-credito` | Consultar/reconciliar | `cliente.sic.fico_score_v2.queried`, `.fintech_score.queried`, `.credit_report_fico.queried`, `sic.attempt_reconciled` | Operador autorizado | Cliente/consulta | Resultado operativo | Equipo y sucursal originales | Proveedor/producto/IDs; sin reporte | Q | QA implementado; no acredita SIC productivo | `SicOperacionService` | `SicQaTest`, `SicSafetyTest` |
| `solicitudes` | Alta, captura, revisión, resolución, firma y aprobación | Familia `solicitud.*` | Operador autorizado | Solicitud/recurso | Resultado operativo | Equipo y sucursal de operación | IDs de evidencia/revisión; sin fundamento reservado | Q | Emisores existentes; cambio de etiqueta de PR #46 no añadió mutación | Servicios de solicitud, firma y política | `SolicitudesTest` y pruebas de originación |
| `evaluacion-solicitudes` | Dictamen de evaluación | `solicitud.assessment_recorded` | Evaluador | Dictamen/solicitud | Dictamen registrado | Equipo/sucursal de operación | IDs; sin fundamento ni riesgo reservado | Q | Emisor existente; reconciliación general pendiente | Servicio de dictámenes | Pruebas de originación |
| `cumplimiento` | Dictamen PLD | `solicitud.assessment_recorded` | Operador PLD | Dictamen/solicitud | Dictamen registrado | Equipo/sucursal de operación | IDs; sin fuentes ni fundamento | Q | Emisor existente; reconciliación general pendiente | Servicio de dictámenes | Pruebas de originación |
| `creditos` | Desembolso, pago y reverso QA | `credito.disbursed`, `credito.payment_recorded`, `credito.payment_reversed` | Operador autorizado | Solicitud/crédito | Resultado operativo QA | Equipo/sucursal de operación | IDs y estado; sin presumir dinero real | Q | Parcial: reconciliar anticipos, liquidación y reintentos | `DesembolsoService`, `PagoService` | `CreditoPagosTest`, `CreditoAnticiposTest` |
| Autenticación (transversal) | Login y 2FA | `login`, `login.2fa_completed` | Usuario autenticado | Según emisor | Autenticación completada | Contexto de sesión | Contrato | Q | Existente; cierre de sesión y fallos siguen pendientes | `JetstreamServiceProvider` | `AuthenticationTest`, `TwoFactorAuthenticationSettingsTest` |
| Cuenta/contraseña (transversal) | Activación inicial, recuperación y cambio autenticado | `user.activated`, `user.password.recovered`, `user.password.changed` | Titular que prueba identidad | Usuario | `applied` | P | `result`, estado cuando aplica; sin credenciales | Q | Implementado; un enlace consumido no vuelve a emitir activación | Acciones Fortify de contraseña | `AccountAdministrationAuditTest`, `PasswordResetTest`, `UpdatePasswordTest` |
| Perfil (transversal) | Guardar perfil | `user.profile.updated` | Titular autenticado | Usuario | Cambio registrado | Contexto del actor | Nombres de campos | Q | Existente; no confundir con alta administrativa | `UpdateUserProfileInformation` | Pruebas de perfil y `AccountMailTest` |
| Procesos automáticos/colas | Ejecutar en nombre de un iniciador | Según dominio | Sistema / iniciador separados | Recurso de dominio | Según proceso | Capturar al iniciar; no reconstruir desde preferencias | Contrato; sin secretos | Q | Reconciliación general pendiente; los emisores de cuenta de esta entrega son síncronos | Revisar por proceso | Pendiente por proceso |
| `configuracion-empresa` y procesos transversales | Bloqueo operativo | `empresa.operacion_bloqueada` | Usuario HTTP o sistema ejecutor / iniciador por ID | Recurso afectado | `blocked` | Equipo/sucursal originales | `state`, `product`, `result`, `related`; sin datos sensibles | Q | Implementado | `OperacionEmpresaBloqueada`, workers | `EmpresaOperacionTest`, `SicQaTest` |
| `sucursales` | Configurar numeracion | `sucursal.folios.updated` | Administrador autorizado | Sucursal seleccionada | `applied` | Equipo del actor / sucursal afectada | Nombres de campos; sin valores | Q | Implementado; no-op sin evento | `FolioService` | `FoliosTest` |
| Transversal (folios) | Asignar a historia | `folio.historical_assigned` | Sistema | Registro de negocio | `assigned` | Equipo desconocido / sucursal original | Tipo e ID de asignacion | Q | Implementado; reintento no duplica | Migracion / `folios:completar` | `FoliosTest` |

## Registros históricos

Los registros antiguos cuyo campo `event` es nulo no se reescriben ni eliminan. La interfaz y el filtro los exponen como `legacy.unclassified`, con la etiqueta **Sin clasificar (histórico)**. Los códigos antiguos no reconocidos se conservan y muestran con su valor original.

## Consulta y exportación

Super Admin global consulta todos los equipos. Un usuario normal necesita
pertenencia y `read activity-log` en el equipo consultado; sin filtro se usa el
actual. `team_id` conserva el seleccionado al filtrar, paginar o exportar sin
cambiar las preferencias. El acceso por sucursal y la política de exportación
adicional siguen pendientes del backlog de auditoría. Los valores CSV que
podrían interpretarse como fórmulas se prefijan para tratarlos como texto.

## Agregar un evento

1. Crear el caso y su etiqueta, severidad e icono en `ActivityEvent`.
2. Emitirlo exclusivamente con `ActivityLogService`.
3. Incluir solo metadatos de la lista permitida.
4. Agregar una prueba que verifique código, equipo, sujeto y ausencia de datos sensibles.
5. Actualizar esta matriz.
