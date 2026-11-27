# ADR 0052: bandeja interna contextual

## Estado

Propuesto en implementación local el 2026-11-14. [Requisito](https://app.notion.com/p/Kronik-Comunicaciones-por-canales-env-os-y-seguimiento-3fe61db7db7f81e4af50c4f0f3ab7e41).

## Contexto

Los avisos al operador deben persistir, deduplicarse y mantenerse aislados por usuario, equipo y sucursal. El proyecto ya usa Laravel Notifications para correos de acceso, pero no dispone de una tabla ni de un contrato de bandeja que guarde el contexto original. La auditoría y los envíos externos tienen fines y autorizaciones distintos.

## Decisión

Guardar avisos internos en `operator_notifications` con destinatario y contexto de origen obligatorios, evento catalogado y clave única de deduplicación. Los productores publican dentro de su transacción de negocio. La lectura exige coincidencia exacta de destinatario, equipo y sucursal activos. El cambio de estado leído emite un evento auditable separado; el evento de negocio conserva su auditoría original.

Por ahora no se guardan enlaces a recursos ni datos personales del cliente en el cuerpo. Un destinatario que perdió pertenencia al equipo o sucursal no recibe un aviso nuevo. La bandeja se actualiza por consulta periódica; no se agrega infraestructura de WebSockets. Los canales externos se diseñarán en una decisión aparte, con consentimiento y estados propios de entrega.

## Consecuencias

La tabla es adicional y permite construir una bandeja segura sin alterar correos de acceso. Los avisos de otros contextos permanecen en historial persistido, pero no son visibles al cambiar de equipo o sucursal. El siguiente incremento debe resolver enlaces autorizados y caducidad del acceso al recurso antes de mostrar detalles de negocio.

## Renumeración de integración — 27/11/2026

Originalmente ADR 0047 en la rama de Comunicaciones. Se renumera a 0052
para conservar el ADR 0047 independiente de main, sin cambiar esta decisión
ni su contexto histórico.
