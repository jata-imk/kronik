# Notificaciones internas de operadores

La [nota original de Notion](comunicaciones-por-canales-envios-y-seguimiento.md) conserva el requisito completo. Esta referencia describe únicamente el comportamiento implementado en esta entrega.

## Modelo y alcance

`operator_notifications` guarda avisos para un usuario concreto con `team_id` y `sucursal_id` explícitos. El evento, prioridad, título y cuerpo se separan de la bitácora de auditoría y de cualquier envío externo al acreditado. `dedup_key` identifica una ocurrencia de negocio por destinatario y equipo; el índice único impide crearla dos veces. El cuerpo de los avisos actuales no incluye datos personales del cliente.

El catálogo inicial es `OperatorNotificationEvent`: solicitud asignada, solicitud devuelta y documento disponible. Solo asignación y devolución tienen emisores conectados. Ningún aviso externo se envía desde este módulo. No se crean configuraciones de proveedores, plantillas ni credenciales.

`OperatorNotificationService::publish()` exige que el destinatario pertenezca al equipo y a la sucursal de origen. Si no pertenece, omite el aviso sin revertir la operación de negocio. La asignación y devolución lo invocan dentro de la transacción que registra el evento de solicitud. La clave incluye el identificador y la versión/resolución del evento. El historial y el resumen filtran por el usuario autenticado, su equipo y sucursal activos. No hay enlaces a recursos hasta que se implemente una resolución de destino con autorización del recurso.

## API y UI

- `GET /notificaciones` muestra el historial paginado.
- `GET /notificaciones/resumen` devuelve cantidad no leída y seis avisos recientes, con `Cache-Control: no-store`.
- `PATCH /notificaciones/{id}/leer` marca un aviso propio del contexto activo como leído; un ID de otro usuario, equipo o sucursal responde 404. La segunda petición es idempotente.
- La campana del encabezado consulta al abrir, al montar y cada 60 segundos mientras la pestaña esté visible. Se limpia al cambiar el contexto operativo. Incluye vacío, carga, error, recarga, historial, teclado y vista adaptable.
- En móvil, el panel de la campana se centra bajo el encabezado para que su contenido no quede fuera de la pantalla; desde el ancho `sm` vuelve a quedar anclado a la campana.

La lectura registra `operator_notification.read` una vez mediante `ActivityLogService` con actor, sujeto y equipo/sucursal originales. La creación del aviso se representa por el evento de negocio que la origina; no duplica su auditoría.

## Límites de esta etapa

No se ha habilitado correo, SMS, WhatsApp ni Telegram para acreditados. Consentimiento por canal y propósito, configuración de proveedores, reglas, plantillas, envíos e intentos pertenecen al siguiente incremento. El requisito indica que WhatsApp necesita decisión y revisión específica antes de construcción productiva. La aceptación por un transporte nunca debe mostrarse como entrega o lectura.
