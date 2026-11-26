# ADR 0051: Notificaciones globales y previas de texto/HTML

Fecha: 2026-11-26. Estado: aceptado para I3; integración y despliegue pendientes.

## Contexto

José confirmó varias plantillas globales seleccionables para SMS y correo,
comparables al catálogo documental, sin variantes privadas por equipo.
El texto anterior por regla no conservaba versiones completas. El editor
documental compartía variables y experiencia visual, pero su persistencia HTML/PDF
no correspondía a SMS. La ampliación exige previas sin guardar y versiones exactas,
sin generar PDF para consultar texto o HTML.

## Decisión

Mantener catálogo/versiones propios en Comunicaciones. Compartir panel de
variables, inserción y editor HTML con capacidades explícitas; SMS usa texto plano.
No agregar Notificaciones al enum documental ni construir un editor universal.

La regla elige una plantilla global compatible y usa su versión activa. Activación,
retiro o cambio de selección cancelan pendientes sin intento y sin recrearlos.
Las versiones activas/retiradas son inmutables y la salida conserva referencia
exacta más evidencia cifrada del mensaje intentado. Serializar operaciones y
auditoría en la misma transacción.

Previsualizar con datos sintéticos mediante servicios puros de compilación,
saneamiento y presentación. Compartir estilos/fuentes documentales entre HTML y
PDF. El HTML documental es continuo; PDF permanece una acción explícita. Aislar
HTML con sandbox/CSP, embebiendo únicamente recursos autorizados. Correo HTML
utiliza la misma presentación que la previa y recibe el formato congelado del
intento; correo de texto y SMTP conservan su contrato.

## Consecuencias

Permisos por acción independientes, sin exclusividad de Super Admin; equipo como
contexto de autorización, no propietario global. Solo variables aprobadas de
Notificaciones y límite de dos segmentos SMS tras sustitución real.

La migración es aditiva y no hace backfill de datos de prueba. Las reglas anteriores
SMS/correo requieren selección explícita; WhatsApp conserva su flujo y aprobación
Meta. No se habilita tráfico, recepción externa SMS ni Integraciones.

Contrato canónico: [plantillas de Notificaciones](../reference/plantillas-notificaciones.md).
Complementa [la evaluación](editor-plantillas-notificaciones.md) y
[el plan de incrementos](plan-incrementos-sms.md); no sustituye las decisiones
de proveedor ni el pendiente de autenticidad de I2.
