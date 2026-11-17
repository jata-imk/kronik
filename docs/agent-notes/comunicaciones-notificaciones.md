# Comunicaciones y notificaciones

## Referencias

- Notion: https://app.notion.com/p/Kronik-Comunicaciones-por-canales-env-os-y-seguimiento-3fe61db7db7f81e4af50c4f0f3ab7e41
- Rama: `feat/comunicaciones-notificaciones`, cambios locales sin PR.
- PR: pendiente.
- ADR relacionados: [0047, bandeja interna contextual](../explanation/adr-0047-bandeja-interna-contextual.md) y [0048, eventos candidatos](../explanation/adr-0048-eventos-candidatos-comunicacion.md); pendiente otro para orquestación de canales externos.

## Objetivo

Iniciar el módulo de notificaciones conforme al requisito de Notion, priorizando avisos internos de operadores.

## Estado actual

- Estado: en progreso.
- Última actualización: 2026-11-17.
- Último punto estable: copia directa del cuerpo de Notion a `docs/reference/comunicaciones-por-canales-envios-y-seguimiento.md`; base de avisos internos y primer disparador en solicitudes implementados localmente. El usuario eligió pago recibido y próximo vencimiento tres días antes. La capa de eventos candidatos para ambos quedó implementada localmente, sin envíos.
- Integración y despliegue: pendientes; sin PR ni QA de usuario.

## Decisiones pendientes

- Definir los próximos eventos internos y los enlaces autorizados a recursos.
- Resolver equipo/prestamista estable del crédito, consentimiento por finalidad y canal, contactos, textos y proveedor elegible antes de cualquier envío real.
- ADR y revisión de políticas antes de construir WhatsApp productivo.

## Evidencia

- Pruebas: ejecución conjunta de `CommunicationReadinessTest`, `CreditoPagosTest`, `OperatorNotificationsTest` y `SolicitudesTest`: 89 pruebas/1442 aserciones. La dirección autorizada del usuario se pasó como variable temporal; sin correo enviado ni guardado en el repositorio. Incluyen reintentos, reverso, recordatorio a tres días, lectura invalidada, expiración, auditoría y rollback. `CreditoAnticiposTest`, `BulletPagosTest` y `TableroFinancieroTest`: 26 pruebas/1234 aserciones en la pasada anterior. Prueba de componente de campana aprobada en esta pasada; Vite compiló en la pasada anterior. Backend ejecutado con PHP 8.4 y extensiones SQLite activadas por CLI. Pint de la nueva prueba y `git diff --check` aprobados.
- Revisión visual local: campana vacía y con aviso, historial antes/después de lectura y vistas de escritorio/móvil capturadas desde navegador Edge con SQLite E2E y usuarios sintéticos. Se corrigió el panel móvil que quedaba fuera de pantalla. Es un cambio de presentación sin nueva acción auditable. La validación de QA por el usuario sigue pendiente.
- [Set de pruebas](../how-to/probar-comunicaciones.md) documentado; recepción real de correo pendiente porque el entorno local usa transporte `log` y el módulo aún no despacha avisos externos.
- Confirmación de QA/usuario: pendiente.
- Migraciones o seeders: tablas `operator_notifications` y `communication_events` aplicadas en la base local `kronik_softcredito9`; sin nuevos seeders. La tarea horaria aparece en `schedule:list`. El comando se ejecutó localmente con cero créditos y preparó cero candidatos, sin envíos. No se ha comprobado un scheduler de servidor.

## Siguiente paso

Validar la UI interna con datos reales de asignación y resolver propiedad estable del crédito y consentimiento antes de orquestar correo.

## Cierre

- Commit inicial: `793e684` en `feat/comunicaciones-notificaciones`, rama subida; merge pendiente.
- Pendientes diferidos: canales externos y sus dependencias de proveedor.
