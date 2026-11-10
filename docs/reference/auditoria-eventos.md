# Eventos de auditoría

## Estado y contrato

Incremento de días de gracia de Empresa (10/11/2026): conserva `empresa.updated`
y `producto_crediticio.*`; prellenar no es una acción auditable nueva.
`ProductoDiasGraciaTest` verifica persistencia única, actor/contexto y preservación
de condiciones e históricos. El parámetro no se copia como valor al log.

Reconciliación técnica del 05/11/2026, continuidad `80b2f92`, base funcional
`0707ce9`. Esta es la única matriz de eventos. El inventario parte de los 19
módulos de `ModulesAndPermissionsSeeder` y añade autenticación, perfil, API y
procesos automáticos. No presupone el catálogo de una instalación desplegada.
La [Agent Note](../agent-notes/auditoria-actividad.md) acredita pruebas, commit,
PR/revisión y límites; implementación, integración, despliegue y QA son estados
separados. El [plan integral](https://app.notion.com/p/21461db7db7f80d5b813c2899522a72b)
conserva el alcance aprobado.

Los eventos se declaran en `App\Enums\ActivityEvent` y se persisten mediante
`App\Services\ActivityLogService`. Dominio y actividad de una mutación comparten
transacción; un fallo de auditoría revierte la mutación. Las peticiones rechazadas
no emiten éxito. Cambios sin efecto y reentregas no duplican un resultado ya
persistido; fallos de acceso, bloqueos operativos e invitaciones tienen sus propios
resultados auditables. Cada nueva exportación constituye una acción explícita.

Se guardan código estable, descripción fija en español, actor/sujeto cuando aplican,
equipo/sucursal e IP, user agent y request ID disponibles. La lista funcional
permitida es `changed_fields` (solo nombres), `related` (tipo e ID), `state`,
`provider`, `product` y `result`. Las filas indican los metadatos pertinentes;
no autorizan claves adicionales. No copiar nombres personalizados, correos,
teléfonos, RFC/CURP, domicilios, contraseñas/hashes, tokens, enlaces, secretos,
respuestas SIC, contenido documental, motivos reservados, importes ni snapshots.
Las evidencias y fundamentos conservan sus permisos de dominio.

`inheritActorContext: false` conserva IDs capturados, incluido `null`. No reconstruir
historia desde preferencias actuales. Los eventos automáticos tienen causer null;
el iniciador permanece en el recurso o en `related`. Los intentos no autenticados
son anónimos: sin actor, sujeto ni equipo/sucursal inferidos del correo. La interfaz
los presenta como **Anónimo**; **Sistema** corresponde a ejecución sin actor de
usuario. Un desafío 2FA no acredita acceso completado. La revocación de tokens por
desactivación de cuenta queda representada por la desactivación, sin duplicarla
como revocación manual. El cierre de otras sesiones acredita renovación del
marcador de autenticación y eliminación de filas si el driver es database, sin
afirmar cierre físico de navegadores. No representa un cambio de contraseña.
El cambio explícito de equipo conserva el contexto original y referencia el
destino; crear un equipo no duplica este evento de preferencia.

### Leyenda de la matriz

- **U:** usuario autorizado/titular; incluye Super Admin y delegados según el dominio.
- **S/I:** sistema ejecutor e iniciador separados; **S:** sistema sin iniciador conocido;
  **A:** intento anónimo sin identidad atribuida.
- **C:** contexto del actor capturado antes de mutaciones de preferencias.
- **T:** equipo afectado/seleccionado y sucursal original del actor, incluido null.
- **R:** equipo de la operación y sucursal responsable/original del recurso.
- **J:** contexto persistido de la solicitud/recurso, incluso desconocido; nunca heredado.
- **P:** contexto de la cuenta que prueba identidad al establecer/restablecer contraseña.
- **Q:** pertenencia y `read activity-log` del equipo consultado; excepción global de
  Super Admin. **G:** solo consulta global autorizada de Super Admin.

## Matriz por acción

La evidencia es una prueba de comportamiento con actividad persistida; declarar
un caso del enum o despachar un evento Jetstream no acredita cobertura. Los nombres
de suites son rutas relativas a `tests/Feature/`. Las filas compartidas conservan
un solo código por acción, sin crear catálogos paralelos.

| Módulo | Acción/disparador | Código | Actor/iniciador | Sujeto | Resultado | Equipo/sucursal | Metadatos permitidos | Consulta | Estado | Emisor | Prueba |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `teams` | Equipo creado | `team.created` | U | Equipo | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | TeamManagementService::create | AuditCoverageTest |
| `teams` | Equipo actualizado | `team.updated` | U | Equipo | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | TeamManagementService::update | AuditCoverageTest |
| `teams` | Equipo desactivado | `team.deactivated` | U | Equipo | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | TeamManagementService::update | AuditCoverageTest |
| `teams` | Equipo reactivado | `team.reactivated` | U | Equipo | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | TeamManagementService::update | AuditCoverageTest |
| `roles` | Rol creado | `role.created` | U / S (comando) | Rol | Cambio aplicado | T / desconocido en CLI | changed_fields, result | Q | Implementado; validar entrega en Agent Note | CreateRole::handle; RoleManagementService::create | AuditCoverageTest |
| `roles` | Rol actualizado | `role.updated` | U | Rol | Cambio aplicado | T | changed_fields, result | Q | Implementado; validar entrega en Agent Note | RoleManagementService::update | AuditCoverageTest |
| `roles` | Rol eliminado | `role.deleted` | U | Rol | Cambio aplicado | T | changed_fields, result | Q | Implementado; validar entrega en Agent Note | RoleManagementService::delete | AuditCoverageTest |
| `roles` | Permisos del rol actualizados | `role.permissions.updated` | U / S (comando) | Rol | Cambio aplicado | T / desconocido en CLI | changed_fields, result | Q | Implementado; validar entrega en Agent Note | CreateRole::handle; RoleManagementService::update | AuditCoverageTest |
| `roles` | Permiso creado | `permission.created` | S (comando) | Permiso | Cambio aplicado | Equipo explícito del comando o null; sucursal null | changed_fields, result | Q / G si null | Implementado; validar entrega en Agent Note | CreatePermission::handle; CreateRole::handle | AuditCoverageTest |
| `Autenticación` | Sesión cerrada | `logout` | U | Usuario | Cierre completado | C | result | Q | Implementado; validar entrega en Agent Note | FortifyServiceProvider::boot → AccessAuditService/ActivityLogService | AuditCoverageTest |
| `Autenticación` | Credenciales de acceso rechazadas | `login.failed` | A | — | Rechazado/limitado | Desconocido explícito | result | G | Implementado; validar entrega en Agent Note | FortifyServiceProvider::boot → AccessAuditService/ActivityLogService | AuditCoverageTest |
| `Autenticación` | Acceso limitado por frecuencia | `login.throttled` | A | — | Rechazado/limitado | Desconocido explícito | result | G | Implementado; validar entrega en Agent Note | FortifyServiceProvider::boot → AccessAuditService/ActivityLogService | AuditCoverageTest |
| `Autenticación` | Acceso rechazado por estado de cuenta | `login.rejected` | A | — | Rechazado/limitado | Desconocido explícito | result | G | Implementado; validar entrega en Agent Note | EnsureUserIsActive::handle; TwoFactorSessionController::store | AuditCoverageTest |
| `Acceso/perfil` | Configuración de segundo factor iniciada | `user.2fa.enabled` | U | Usuario | Cambio aplicado | C | result | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle | AuditCoverageTest |
| `Acceso/perfil` | Segundo factor confirmado | `user.2fa.confirmed` | U | Usuario | Cambio aplicado | C | result | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle | AuditCoverageTest |
| `Acceso/perfil` | Segundo factor deshabilitado | `user.2fa.disabled` | U | Usuario | Cambio aplicado | C | result | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle | AuditCoverageTest |
| `Acceso/perfil` | Códigos de recuperación regenerados | `user.2fa.recovery_codes_regenerated` | U | Usuario | Cambio aplicado | C | result | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle | AuditCoverageTest |
| `Autenticación` | Segundo factor rechazado | `login.2fa_failed` | A | — | Rechazado/limitado | Desconocido explícito | result | G | Implementado; validar entrega en Agent Note | TwoFactorSessionController::store | AuditCoverageTest |
| `Autenticación` | Segundo factor limitado por frecuencia | `login.2fa_throttled` | A | — | Rechazado/limitado | Desconocido explícito | result | G | Implementado; validar entrega en Agent Note | FortifyServiceProvider::boot → AccessAuditService/ActivityLogService | AuditCoverageTest |
| `Acceso/perfil` | Token API creado | `user.api_token.created` | U | Usuario | Cambio aplicado | C | result, related (token) | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle | AuditCoverageTest |
| `Acceso/perfil` | Capacidades del token API actualizadas | `user.api_token.updated` | U | Usuario | Cambio aplicado | C | result, related (token) | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle | AuditCoverageTest |
| `Acceso/perfil` | Token API revocado | `user.api_token.revoked` | U | Usuario | Cambio aplicado | C | result, related (token) | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle | AuditCoverageTest |
| `Acceso/perfil` | Correo verificado | `user.email_verified` | U | Usuario | Cambio aplicado | C | result | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle | AuditCoverageTest |
| `activity-log` | Archivo CSV de actividad generado | `activity_log.exported` | U | — | Archivo generado | Equipo consultado / sucursal original; global explícito null | result | Q | Implementado; validar entrega en Agent Note | UserController::exportActivity | AuditCoverageTest |
| `productos-crediticios` | Activación de producto programada | `producto_crediticio.activation_scheduled` | U | Producto; versión en related | Programada | C | changed_fields, state, result, related | Q | Implementado; validar entrega en Agent Note | ProductoVersionService::activar | ProductosCrediticiosTest; VersionMotorCreditoTest |
| `circulo-credito` | Intento SIC solicitado | `sic.requested` | U | Intento SIC; iniciador en related | Cambio aplicado | J | state, result, related, provider, product | Q | Implementado; validar entrega en Agent Note | SicOperacionService::preparar | SicQaTest; ConsultaSucursalTest (lectura) |
| `circulo-credito` | Envío SIC iniciado | `sic.sending` | S/I | Intento SIC; iniciador en related | Cambio aplicado | J | state, result, related, provider, product | Q | Implementado; validar entrega en Agent Note | SicOperacionService::transicion | SicQaTest; ConsultaSucursalTest (lectura) |
| `circulo-credito` | Intento SIC cancelado antes del envío | `sic.cancelled` | S/I | Intento SIC; iniciador en related | Cambio aplicado | J | state, result, related, provider, product | Q | Implementado; validar entrega en Agent Note | SicOperacionService::transicion | SicQaTest; ConsultaSucursalTest (lectura) |
| `circulo-credito` | Intento SIC marcado incierto | `sic.uncertain` | S/I | Intento SIC; iniciador en related | Cambio aplicado | J | state, result, related, provider, product | Q | Implementado; validar entrega en Agent Note | SicOperacionService::transicion | SicQaTest; ConsultaSucursalTest (lectura) |
| `circulo-credito` | Respuesta SIC tardía conservada | `sic.late_response` | S/I | Intento SIC; iniciador en related | Cambio aplicado | J | state, result, related, provider, product | Q | Implementado; validar entrega en Agent Note | SicOperacionService::ejecutar | SicQaTest; ConsultaSucursalTest (lectura) |
| `users` | Equipo activo cambiado | `user.active_team_changed` | U | Usuario; destino en related | Cambio aplicado | Equipo/sucursal originales | related, result | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle (current-team.update) | AuditCoverageTest |
| `users` | Otras sesiones invalidadas | `user.sessions.invalidated` | U | Usuario | Marcador de autenticación renovado | C | result | Q | Implementado; validar entrega en Agent Note | AuditSecurityChanges::handle (other-browser-sessions.destroy) | AuditCoverageTest |
| `users` | Sucursal activa cambiada | `user.active_branch_changed` | U | Usuario | Cambio aplicado | Equipo iniciador / sucursal anterior | related, result | Q | Implementado; validar entrega en Agent Note | CurrentSucursalController::update | ConsultaSucursalTest |
| `users` | Cuenta pendiente creada | `user.created` | U | Usuario | Cambio aplicado | C | changed_fields, state, result | Q | Implementado; validar entrega en Agent Note | UserManagementService::create | AccountAdministrationAuditTest |
| `users` | Cuenta actualizada | `user.updated` | U | Usuario | Cambio aplicado | C | changed_fields, state, result | Q | Implementado; validar entrega en Agent Note | UserManagementService::update | AccountAdministrationAuditTest |
| `users` | Cuenta desactivada | `user.deactivated` | U | Usuario | Cambio aplicado | C | changed_fields, state, result | Q | Implementado; validar entrega en Agent Note | UserManagementService::update | AccountAdministrationAuditTest |
| `users` | Cuenta reactivada | `user.reactivated` | U | Usuario | Cambio aplicado | C | changed_fields, state, result | Q | Implementado; validar entrega en Agent Note | UserManagementService::update | AccountAdministrationAuditTest |
| `teams` | Integrante agregado al equipo | `team.member.added` | U | Usuario | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | UserAccessAuditService::recordChanges | AccountAdministrationAuditTest |
| `teams` | Integrante retirado del equipo | `team.member.removed` | U | Usuario | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | UserAccessAuditService::recordChanges | AccountAdministrationAuditTest |
| `teams` | Rol genérico de membresía actualizado | `team.member.legacy_role_updated` | U | Usuario | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | UpdateTeamMemberRole::update | AccountAdministrationAuditTest |
| `users` | Roles del integrante actualizados | `user.team_roles.updated` | U | Usuario | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | UserAccessAuditService::recordChanges | AccountAdministrationAuditTest |
| `users` | Invitación aceptada por el transporte | `user.invitation.sent` | U / S | Usuario | Aceptado por transporte | Contexto capturado del actor o de cuenta en sistema | state, result | Q | Implementado; validar entrega en Agent Note | UserInvitationService::send | AccountAdministrationAuditTest |
| `users` | Reenvío de invitación aceptado por el transporte | `user.invitation.resent` | U / S | Usuario | Aceptado por transporte | Contexto capturado del actor o de cuenta en sistema | state, result | Q | Implementado; validar entrega en Agent Note | UserInvitationService::send | AccountAdministrationAuditTest |
| `users` | Envío de invitación fallido | `user.invitation.failed` | U / S | Usuario | Fallido / rechazado | Contexto capturado del actor o de cuenta en sistema | state, result | Q | Implementado; validar entrega en Agent Note | UserInvitationService::send | AccountAdministrationAuditTest |
| `users` | Reenvío de invitación limitado por frecuencia | `user.invitation.throttled` | U / S | Usuario | Limitado por frecuencia | Contexto capturado del actor o de cuenta en sistema | state, result | Q | Implementado; validar entrega en Agent Note | UserInvitationService::send | AccountAdministrationAuditTest |
| `users` | Cuenta activada con contraseña inicial | `user.activated` | Titular con identidad probada | Usuario | Cambio aplicado | P | state, result | Q | Implementado; validar entrega en Agent Note | ResetUserPassword::reset | AccountAdministrationAuditTest |
| `users` | Contraseña recuperada | `user.password.recovered` | Titular con identidad probada | Usuario | Cambio aplicado | P | state, result | Q | Implementado; validar entrega en Agent Note | ResetUserPassword::reset | AccountAdministrationAuditTest |
| `users` | Contraseña cambiada | `user.password.changed` | Titular con identidad probada | Usuario | Cambio aplicado | P | state, result | Q | Implementado; validar entrega en Agent Note | UpdateUserPassword::update | AccountAdministrationAuditTest |
| `teams` | Responsable del equipo transferido | `team.responsible_transferred` | U | Equipo | Cambio aplicado | T | changed_fields, related, result | Q | Implementado; validar entrega en Agent Note | TeamManagementService::transfer | SelectedTeamManagementTest |
| `circulo-credito` | Intento SIC conciliado | `sic.attempt_reconciled` | U | Cliente (conciliación) / intento SIC | Cambio aplicado | J | state, result, related; proveedor/producto si aplica | Q | Implementado; validar entrega en Agent Note | SicOperacionService::conciliar | SicQaTest; ConsultaSucursalTest (lectura) |
| `Autenticación` | Inicio de sesión | `login` | U | Usuario | Autenticación completada | C | result | Q | Implementado; validar entrega en Agent Note | JetstreamServiceProvider::boot → AccessAuditService::authenticated | AuditCoverageTest |
| `solicitudes` | Solicitud creada | `solicitud.created` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::crear | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Borrador de solicitud actualizado | `solicitud.updated` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::guardar | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Solicitud enviada a revisión | `solicitud.submitted` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::enviar | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Responsable de solicitud asignado | `solicitud.assigned` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::asignar | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Solicitud devuelta para corrección | `solicitud.returned` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::resolver | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Solicitud rechazada | `solicitud.rejected` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::resolver | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Solicitud cancelada | `solicitud.cancelled` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::resolver | AuditDomainCoverageTest; SolicitudesTest |
| `evaluacion-solicitudes / cumplimiento` | Revisión especializada registrada | `solicitud.assessment_recorded` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::dictaminar | AuditDomainCoverageTest; SolicitudesTest |
| `productos-crediticios` | Política de originación versionada | `originacion.politica_created` | U | Producto | Cambio aplicado | C | related | Q | Implementado; validar entrega en Agent Note | OriginacionPoliticaService::crear | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Solicitud aprobada | `solicitud.approved` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudService::aprobar | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Copia firmada recibida | `solicitud.signature_received` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudFirmaService::recibir | AuditDomainCoverageTest; SolicitudesTest |
| `solicitudes` | Firma revisada | `solicitud.signature_reviewed` | U | Solicitud | Cambio aplicado | R | state / related (política, firma) | Q | Implementado; validar entrega en Agent Note | SolicitudFirmaService::revisar | AuditDomainCoverageTest; SolicitudesTest |
| `creditos` | Desembolso QA registrado | `credito.disbursed` | U | Solicitud; crédito en related | Cambio aplicado | R | related | Q | Implementado; validar entrega en Agent Note | DesembolsoService::registrar | AuditDomainCoverageTest; CreditoPagosTest; CreditoAnticiposTest |
| `creditos` | Pago QA registrado | `credito.payment_recorded` | U | Crédito | Cambio aplicado | R | related | Q | Implementado; validar entrega en Agent Note | PagoService::registrar | AuditDomainCoverageTest; CreditoPagosTest; CreditoAnticiposTest; BulletPagosTest (evidencia sintética) |
| `creditos` | Pago QA revertido | `credito.payment_reversed` | U | Crédito | Cambio aplicado | R | related | Q | Implementado; validar entrega en Agent Note | PagoService::reversar | AuditDomainCoverageTest; CreditoPagosTest; CreditoAnticiposTest; BulletPagosTest (evidencia sintética) |
| `Autenticación` | Autenticación de dos factores completada | `login.2fa_completed` | U | Usuario | Autenticación completada | C | result | Q | Implementado; validar entrega en Agent Note | TwoFactorSessionController::store | AuditCoverageTest |
| `Perfil` | Perfil de usuario actualizado | `user.profile.updated` | U | Usuario | Cambio aplicado | C | changed_fields | Q | Implementado; validar entrega en Agent Note | UpdateUserProfileInformation::update | AuditCoverageTest |
| `menubar-items` | Elemento de menú creado | `menubar_item.created` | U | Elemento de menú | Cambio aplicado | C | changed_fields, result | Q | Implementado; validar entrega en Agent Note | MenubarItemService::guardar | Admin/ConsultaEmpresaMenuTest |
| `menubar-items` | Elemento de menú actualizado | `menubar_item.updated` | U | Elemento de menú | Cambio aplicado | C | changed_fields, result | Q | Implementado; validar entrega en Agent Note | MenubarItemService::guardar | Admin/ConsultaEmpresaMenuTest |
| `menubar-items` | Elemento de menú eliminado | `menubar_item.deleted` | U | Elemento de menú | Cambio aplicado | C | changed_fields, result | Q | Implementado; validar entrega en Agent Note | MenubarItemService::eliminar | Admin/ConsultaEmpresaMenuTest |
| `configuracion-empresa` | Empresa actualizada | `empresa.updated` | U | Configuración | Cambio aplicado | C | changed_fields | Q | Implementado; validar entrega en Agent Note | EmpresaConfiguracionController::update | Admin/EmpresaConfiguracionTest; ProductoDiasGraciaTest |
| `configuracion-empresa` | Operación bloqueada por el estado de la empresa | `empresa.operacion_bloqueada` | U / S/I | Recurso de la operación | blocked | C / J | state, product, result, related | Q | Implementado; validar entrega en Agent Note | OperacionEmpresaBloqueada::registrar | EmpresaOperacionTest; SicQaTest |
| `sucursales` | Sucursal creada | `sucursal.created` | U | Sucursal | Cambio aplicado | Equipo iniciador / sucursal afectada | changed_fields | Q | Implementado; validar entrega en Agent Note | SucursalController::store | Admin/SucursalTest; Admin/ActivityLogTest |
| `sucursales` | Sucursal actualizada | `sucursal.updated` | U | Sucursal | Cambio aplicado | Equipo iniciador / sucursal afectada | changed_fields | Q | Implementado; validar entrega en Agent Note | SucursalController::update | Admin/SucursalTest; Admin/ActivityLogTest |
| `sucursales` | Numeración de sucursal actualizada | `sucursal.folios.updated` | U | Sucursal | Cambio aplicado | Equipo iniciador / sucursal afectada | changed_fields | Q | Implementado; validar entrega en Agent Note | FolioService::configurar | FoliosTest |
| `sucursales` | Folio asignado a registro histórico | `folio.historical_assigned` | S | Registro con folio | Cambio aplicado | Equipo desconocido / sucursal original | related | Q | Implementado; validar entrega en Agent Note | FolioService::completarHistoricos | FoliosTest |
| `sucursales` | Sucursal desactivada | `sucursal.deactivated` | U | Sucursal | Cambio aplicado | Equipo iniciador / sucursal afectada | state | Q | Implementado; validar entrega en Agent Note | SucursalController::destroy | Admin/SucursalTest; Admin/ActivityLogTest |
| `clientes` | Cliente creado | `cliente.created` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteService::store | AuditDomainCoverageTest |
| `clientes` | Cliente actualizado | `cliente.updated` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteService::update | AuditDomainCoverageTest |
| `clientes` | Cliente eliminado | `cliente.deleted` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteService::destroy | AuditDomainCoverageTest |
| `clientes` | Cliente trasladado de sucursal | `cliente.sucursal.transferred` | U | Cliente | Cambio aplicado | Equipo iniciador / sucursal origen | changed_fields, related, state | Q | Implementado; validar entrega en Agent Note | ClienteService::transferir | ConsultaSucursalTest |
| `clientes` | Perfil KYC actualizado | `cliente.kyc.updated` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::updateProfile | AuditDomainCoverageTest |
| `clientes` | Referencia de cliente creada | `cliente.referencia.created` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::storeReferencia | AuditDomainCoverageTest |
| `clientes` | Referencia de cliente actualizada | `cliente.referencia.updated` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::updateReferencia | AuditDomainCoverageTest |
| `clientes` | Referencia de cliente eliminada | `cliente.referencia.deleted` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::deleteReferencia | AuditDomainCoverageTest |
| `clientes` | Persona vinculada al expediente | `cliente.vinculo.created` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::storeVinculo | AuditDomainCoverageTest |
| `clientes` | Persona desvinculada del expediente | `cliente.vinculo.deleted` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::deleteVinculo | AuditDomainCoverageTest |
| `clientes` | Garantía creada | `cliente.garantia.created` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::storeGarantia | AuditDomainCoverageTest |
| `clientes` | Garantía actualizada | `cliente.garantia.updated` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::updateGarantia | AuditDomainCoverageTest |
| `clientes` | Garantía eliminada | `cliente.garantia.deleted` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteExpedienteService::deleteGarantia | AuditDomainCoverageTest |
| `documentos` | Documento de cliente recibido | `cliente.documento.received` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteDocumentoService::upload | AuditDomainCoverageTest |
| `documentos` | Estado documental actualizado | `cliente.documento.status_updated` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteDocumentoService::changeLockedStatus | AuditDomainCoverageTest |
| `documentos` | Documento de cliente descargado | `cliente.documento.downloaded` | U | Cliente | Acceso autorizado | R | related | Q | Implementado; validar entrega en Agent Note | ClienteDocumentoController::download | AuditDomainCoverageTest |
| `clientes` | Consentimiento SIC registrado | `cliente.consentimiento_sic.created` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteConsentimientoSicService::store | AuditDomainCoverageTest; Clientes/ClienteConsentimientoSicTest |
| `clientes` | Consentimiento SIC revocado | `cliente.consentimiento_sic.revoked` | U | Cliente | Cambio aplicado | R | changed_fields, related, state, result | Q | Implementado; validar entrega en Agent Note | ClienteConsentimientoSicService::revoke | AuditDomainCoverageTest; Clientes/ClienteConsentimientoSicTest |
| `clientes` | Evidencia de consentimiento SIC descargada | `cliente.consentimiento_sic.evidence_downloaded` | U | Cliente | Acceso autorizado | R | related | Q | Implementado; validar entrega en Agent Note | ClienteConsentimientoSicController::download | AuditDomainCoverageTest; Clientes/ClienteConsentimientoSicTest |
| `circulo-credito` | Consulta SIC FICO Score v2 | `cliente.sic.fico_score_v2.queried` | S/I | Cliente (conciliación) / intento SIC | Cambio aplicado | J | state, result, related; proveedor/producto si aplica | Q | Implementado; validar entrega en Agent Note | SicOperacionService::ejecutar | SicQaTest; ConsultaSucursalTest (lectura) |
| `historial-crediticio` | Resultado SIC visualizado | `cliente.sic.evidence_viewed` | U | Cliente (conciliación) / intento SIC | Cambio aplicado | J | state, result, related; proveedor/producto si aplica | Q | Implementado; validar entrega en Agent Note | HistorialCrediticioController::detalle | SicQaTest; ConsultaSucursalTest (lectura) |
| `circulo-credito` | Consulta SIC Fintech Score | `cliente.sic.fintech_score.queried` | S/I | Cliente (conciliación) / intento SIC | Cambio aplicado | J | state, result, related; proveedor/producto si aplica | Q | Implementado; validar entrega en Agent Note | SicOperacionService::ejecutar | SicQaTest; ConsultaSucursalTest (lectura) |
| `circulo-credito` | Consulta de reporte de crédito con FICO Score | `cliente.sic.credit_report_fico.queried` | S/I | Cliente (conciliación) / intento SIC | Cambio aplicado | J | state, result, related; proveedor/producto si aplica | Q | Implementado; validar entrega en Agent Note | SicOperacionService::ejecutar | SicQaTest; ConsultaSucursalTest (lectura) |
| `productos-crediticios` | Producto crediticio creado | `producto_crediticio.created` | U / S/I | Producto; versión en ejecución automática | Cambio aplicado | C / J | changed_fields, state, result, related | Q | Implementado; validar entrega en Agent Note | ProductoVersionService::crear | ProductosCrediticiosTest; VersionMotorCreditoTest |
| `productos-crediticios` | Producto crediticio actualizado | `producto_crediticio.updated` | U / S/I | Producto; versión en ejecución automática | Cambio aplicado | C / J | changed_fields, state, result, related | Q | Implementado; validar entrega en Agent Note | ProductoVersionService::actualizar | ProductosCrediticiosTest; VersionMotorCreditoTest |
| `productos-crediticios` | Producto crediticio versionado | `producto_crediticio.versioned` | U / S/I | Producto; versión en ejecución automática | Cambio aplicado | C / J | changed_fields, state, result, related | Q | Implementado; validar entrega en Agent Note | ProductoVersionService::nuevaVersion | ProductosCrediticiosTest; VersionMotorCreditoTest; ProductoDiasGraciaTest |
| `productos-crediticios` | Versión de producto activada | `producto_crediticio.activated` | U / S/I | Producto; versión en ejecución automática | Activa | C / J | changed_fields, state, result, related | Q | Implementado; validar entrega en Agent Note | ProductoVersionService::activar / activarProgramadas | ProductosCrediticiosTest; VersionMotorCreditoTest |
| `productos-crediticios` | Versión de producto retirada | `producto_crediticio.retired` | U / S/I | Producto; versión en ejecución automática | Retirada | C / J | changed_fields, state, result, related | Q | Implementado; validar entrega en Agent Note | ProductoVersionService::retirar / retirarActivaAnterior | ProductosCrediticiosTest; VersionMotorCreditoTest |
| `plantillas-documentos` | Plantilla documental creada | `documento_plantilla.created` | U | Plantilla | Cambio aplicado | C | result, related | Q | Implementado; validar entrega en Agent Note | DocumentoPlantillaVersionService::create | Documentos/DocumentoPlantillaTest |
| `plantillas-documentos` | Imagen documental incorporada | `documento_recurso.created` | U | —; UUID en related | Cambio aplicado | C | result, related | Q | Implementado; validar entrega en Agent Note | DocumentoRecursoService::upload | Documentos/DocumentoEditorTest |
| `plantillas-documentos` | Borrador de plantilla actualizado | `documento_plantilla.updated` | U | Plantilla | Cambio aplicado | C | result, related | Q | Implementado; validar entrega en Agent Note | DocumentoPlantillaVersionService::update | Documentos/DocumentoPlantillaTest |
| `plantillas-documentos` | Plantilla documental versionada | `documento_plantilla.versioned` | U | Plantilla | Cambio aplicado | C | result, related | Q | Implementado; validar entrega en Agent Note | DocumentoPlantillaVersionService::duplicate | Documentos/DocumentoPlantillaTest |
| `plantillas-documentos` | Versión de plantilla activada | `documento_plantilla.activated` | U | Plantilla | Cambio aplicado | C | result, related | Q | Implementado; validar entrega en Agent Note | DocumentoPlantillaVersionService::activate | Documentos/DocumentoPlantillaTest |
| `plantillas-documentos` | Versión de plantilla retirada | `documento_plantilla.retired` | U | Plantilla | Cambio aplicado | C | result, related | Q | Implementado; validar entrega en Agent Note | DocumentoPlantillaVersionService::activate / retire | Documentos/DocumentoPlantillaTest |
| `documentos` | Generación de documento solicitada | `documento.generacion.requested` | U | Cliente; UUID del documento en related | Solicitado | J | related, state | Q | Implementado; validar entrega en Agent Note | SolicitudPaqueteService::registrar; DocumentoGeneracionService::request | Documentos/DocumentoPlantillaTest; SolicitudesTest (paquete) |
| `documentos` | Documento generado | `documento.generated` | S/I | Cliente; UUID del documento en related | Generado | J | related, state | Q | Implementado; validar entrega en Agent Note | GenerarDocumentoPdf::handle | Documentos/DocumentoPlantillaTest; SolicitudesTest (paquete) |
| `documentos` | Generación de documento fallida | `documento.generacion.failed` | S/I | Cliente; UUID del documento en related | Fallido | J | related, state | Q | Implementado; validar entrega en Agent Note | GenerarDocumentoPdf::failed | Documentos/DocumentoPlantillaTest; SolicitudesTest (paquete) |
| `documentos` | Documento visualizado | `documento.viewed` | U | Cliente; UUID del documento en related | Acceso autorizado | J | related | Q | Implementado; validar entrega en Agent Note | DocumentoGeneradoController::view; SolicitudFirmaController::file | Documentos/DocumentoPlantillaTest; SolicitudesTest (paquete) |
| `documentos` | Documento descargado | `documento.downloaded` | U | Cliente; UUID del documento en related | Acceso autorizado | J | related | Q | Implementado; validar entrega en Agent Note | DocumentoGeneradoController::download; SolicitudFirmaController::file | Documentos/DocumentoPlantillaTest; SolicitudesTest (paquete) |
| `documentos` | Documento de cliente visualizado | `cliente.documento.viewed` | U | Cliente | Acceso autorizado | R | related | Q | Implementado; validar entrega en Agent Note | ClienteDocumentoController::view | AuditDomainCoverageTest |
| `clientes` | Evidencia SIC visualizada | `cliente.consentimiento_sic.evidence_viewed` | U | Cliente | Acceso autorizado | R | related | Q | Implementado; validar entrega en Agent Note | ClienteConsentimientoSicController::view | AuditDomainCoverageTest; Clientes/ClienteConsentimientoSicTest |


## Acciones sin evento adicional

Bullet mantiene los emisores reales de Productos, Solicitudes, Documentos,
firmas y Créditos de la matriz anterior. La habilitación del ciclo no añade un
evento por seleccionar política/base, resolver capacidades, calcular ni consultar.
BulletCondicionesTest verifica creación/copia/edición, activación/programación,
uso y consulta sin escrituras; los rechazos de combinaciones no crean actividad.
BulletPagosTest y las unitarias conservan cobertura sintética de obligaciones,
mora, anticipos y reversos.

BulletCicloQaTest origina productos y contratos mediante los servicios reales:
condiciones, tabla, firma, desembolso, anticipo y liquidación/reapertura. Comprueba
actor, sujeto, equipo/sucursal y emisión única de pago/reverso ante reintentos,
sin referencias, motivos reservados, importes ni condiciones en actividad.
Concurrency/bullet.php añade carreras multiconexión en MariaDB aislada para
desembolsos, pagos, anticipos/liquidación y reversos: la operación perdedora
revierte sus escrituras y sus eventos; un reintento no duplica auditoría.
E2E/bullet-ciclo.spec.js recorre ambas políticas con PDF real. La ejecución
aprobada y su commit se acreditan en la Agent Note; una prueba declarada no
demuestra por sí sola ejecución ni despliegue.

| Superficie | Decisión y justificación | Evidencia |
| --- | --- | --- |
| `dashboard`, `admin` y bandejas | Consulta, navegación, filtros, búsqueda y paginación no mutan negocio ni generan actividad adicional. | ConsultaSucursalTest, Admin/ActivityLogTest, SelectedTeamManagementTest |
| `activity-log` listado | Se conserva lectura sin evento; el CSV sí registra generación. | AuditCoverageTest, Admin/ActivityLogTest, E2E auditoria-actividad |
| Productos, plantillas, políticas, catálogos y simulación | Consulta histórica y cálculo puro no añaden eventos. Previews sintéticos no crean evidencia de negocio. | ConsultaProductosTest, ConsultaPlantillasTest, ConsultaPoliticasTest, CapacidadesCreditoTest |
| Biblioteca de imágenes | Consultar recursos de configuración no equivale a acceder a evidencia privada de clientes; el alta tiene su evento. | Documentos/DocumentoEditorTest |
| Autorregistro y eliminación física de cuenta | Deshabilitados en Fortify/Jetstream; no se habilitan para instrumentar auditoría. | AuditCoverageTest, RegistrationTest, DeleteAccountTest |
| Métodos de permisos sin ruta | createPermission/assignPermission del controlador no están expuestos; no crear rutas nuevas. Las rutas CRUD y comandos operativos sí se auditan. | routes/web.php, AuditCoverageTest, RoleManagementTest |
| Seeders y mantenimiento técnico | Los catálogos/fixtures no acreditan acciones de usuarios. Cache, compilación, migraciones, importación pública SAT/Sepomex y cifrado de SIC histórico son mantenimiento; no producir actividad de negocio por cada fila. E2E usa únicamente fixtures sintéticas en BD aislada. | Seeders, pruebas Console, SicSafetyTest |
| Laboratorio SIC | sic:probar-sandbox usa casos sintéticos, no clientes ni evidencia operativa; no sustituye el flujo SIC auditable. No se ejecuta al validar esta entrega. | SicQaTest, SicSafetyTest |
| Folios/programador | folios:completar audita asignaciones efectivas; productos:activar-versiones y sic:marcar-inciertas auditan transiciones efectivas, sin un evento duplicado por invocación. | FoliosTest, ProductosCrediticiosTest, SicQaTest, Concurrency/auditoria.php |
| Retención, resguardo, integridad y alertas | Seguimiento separado aprobado. No cambiar plazos, habilitar limpieza automática ni acreditar políticas inexistentes. | Plan integral de Notion |

## Consulta, históricos y exportación

Se conserva [ADR 0031](../explanation/adr-0031-consulta-actividad-equipo-sucursal.md):
equipo autorizado, sucursal como filtro e históricos de sucursales no asignadas o
inactivas. Consultar/exportar no cambia preferencias. Los intentos anónimos sin
equipo permanecen fuera de las consultas de equipos. Se conserva la sanitización
CSV de valores interpretables como fórmulas.

El CSV se genera por completo en un stream temporal antes de persistir
`activity_log.exported` y preparar la respuesta. No incluye su propio evento ni
registra filas, búsqueda o filtros sensibles en metadatos. `generated` acredita
archivo generado; no recepción ni descarga efectiva por el usuario.

Los registros cuyo event es null se muestran como `legacy.unclassified` (**Sin
clasificar (histórico)**). Se conservan códigos desconocidos; no reclasificar,
reescribir ni completar contexto antiguo por inferencia.

## Automatismos, esquema y despliegue

La migración `2026_11_05_000000` añade `programada_por`, `programada_team_id` y
`programada_sucursal_id` a `producto_versiones`. Son nullable y conservan IDs sin
borrado en cascada. Históricos quedan desconocidos. Programar captura contexto;
el worker revalida estado y fecha bajo bloqueo del producto y versión, y registra
activación/retiro en la misma transacción. La fecha se interpreta en zona empresarial.

PDF comparte transacción en solicitud/evento, generación/evento y fallo/evento.
El archivo privado se escribe antes de confirmar el resultado en BD; un fallo no
se anuncia como generación completada. Se preserva la política de no regenerar
originales contractuales con huella existente. La eliminación de evidencias de un
cliente se pospone hasta confirmar su eliminación; rollback conserva sus archivos.
SIC registra estados y resultados sin copiar payloads ni excepciones del proveedor.

Aplicar la migración antes de reanudar workers/programador de la nueva versión,
con el procedimiento de despliegue y entorno confirmado. No requiere seeder ni
variables nuevas. Las pruebas usan SQLite en memoria y MariaDB aislada; E2E usa
su base dedicada y proveedores simulados. No habilita SIC productivo ni operaciones
con dinero real. Véase [ADR 0037](../explanation/adr-0037-cobertura-tecnica-auditoria.md).

## Mantener la cobertura

Toda nueva acción debe añadir/ajustar emisor y prueba en la misma entrega y actualizar
esta matriz. Cubrir permisos, actor/sujeto, contexto original, resultado, privacidad,
rechazos y rollback; añadir reintento/concurrencia cuando corresponda. Los cálculos
puros del core conservan sus eventos de producto/crédito existentes y no generan
actividad por cada estrategia o proyección. La trazabilidad técnica no acredita
por sí sola conformidad legal.

## Escenarios SIC QA

La presentación de actividad muestra nombres y distingue ejecución automática del
usuario iniciador. No modifica eventos persistidos ni agrega una acción de negocio;
se conserva `SicEvidenceViewed` al leer el detalle. Regresión de exposición mínima
(solo nombre) en `SicActividadPresentacionTest`.

La modalidad por escenario conserva los emisores y eventos SIC de la matriz. `provider` distingue `cdc_sandbox` y `simulacion_interna`; el intento referenciado conserva escenario y evidencia cifrada. No se agrega un evento por elegir un control del formulario ni se duplican finalizaciones. Actor/iniciador y contexto originales siguen vigentes. Evidencia: SicEscenariosQaTest y SicQaTest. Las simulaciones internas no representan comunicaciones con CDC.
