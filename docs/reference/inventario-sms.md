# Inventario previo al corte SMS

`php artisan comunicaciones:inventariar-sms` observa el almacenamiento antes de
sustituir Infobip por LabsMobile. `--json` devuelve únicamente el informe JSON.
Es una operación de consulta: no descifra credenciales, deserializa comandos PHP,
envía mensajes, encola trabajos, modifica registros ni escribe actividad.

## Entorno y alcance

El comando usa la configuración efectiva de Laravel del entorno donde se ejecute.
El responsable debe confirmar ese entorno antes de obtener evidencia para el corte.
No requiere ni ejecuta migraciones o seeders. Una tabla ausente se informa como
no disponible, nunca como inventario vacío. En VPS lo ejecuta exclusivamente el
usuario o su responsable técnico; el agente no establece conexiones SSH.

Se registra inicio y fin de observación. Las consultas no forman una instantánea
atómica entre conexiones: el scheduler y los workers pueden cambiar los datos.
El informe no comprueba recepción, procedencia histórica ni preparación productiva.

## Contrato del informe — versión 1

| Sección | Evidencia |
| --- | --- |
| `channels` | Canales SMS totales, habilitados, con secreto almacenado y por proveedor conocido. Presencia de secreto no acredita validez y no lo descifra. |
| `deliveries` | Salidas SMS por estado e identificadores externos presentes. Sin columna propia de proveedor, `provider_provenance=not_recorded`; nunca se infiere Infobip por el canal. |
| `webhooks` | Callbacks de todos los proveedores de comunicaciones, agrupados por proveedor y pendientes de asociación. No atribuye a SMS los de origen desconocido. |
| `queue` | Driver activo y, por cada conexión `database` configurada, candidatos de comunicaciones listos, retrasados y reservados. Inspecciona también esos almacenes cuando el driver activo sea `sync`. |
| `failed_jobs` | Candidatos de comunicaciones fallidos, únicamente para almacenamiento configurado `database` o `database-uuids`. No lee el texto de excepciones. |
| `complete` | Las secciones configuradas pudieron leerse, el driver activo es inspeccionable y no hubo payloads sin clasificar. No prueba cobertura de almacenes retirados de la configuración. |
| `cutover_ready` | Siempre `false`: el inventario por sí solo no autoriza ni ejecuta el corte. |

Cada sección indica `status=available` o `unavailable`. Los motivos acotados son
`missing_table`, `read_failed` y `unsupported_or_disabled_storage`; no se publican
mensajes crudos de excepciones. Estados y proveedores libres se agrupan en
`other`; la procedencia vacía se agrupa en `unknown`.

Los trabajos se reconocen por `data.commandName` o `displayName` en su JSON,
sin ejecutar ni deserializar `data.command`. Los candidatos corresponden a
`SendCommunicationDelivery` de **todos los canales**, no solo SMS. El JSON inválido
o sin nombre reconocible queda `unclassified` y vuelve incompleto el informe.
Si varias configuraciones apuntan al mismo almacén, sus conteos no deben sumarse
automáticamente.

No se inspeccionan Redis, SQS o Beanstalkd. Si uno es el driver activo,
`active_inspectable=false` y `complete=false`, aunque la tabla database esté vacía.
El almacenamiento de fallidos deshabilitado tampoco demuestra ausencia de fallos.
Almacenes anteriores no configurados y payloads cifrados requieren revisión
técnica separada. La ausencia de una fila de cola no acredita que un worker no
esté ejecutando un envío.

## Resultado y acceso

Código de salida `0`: observación completa según el alcance anterior.
Código `1`: faltó inspeccionar alguna parte o hubo payloads sin clasificar.
Ambos conservan `cutover_ready=false` y muestran la barrera de envíos efectiva.
Si Laravel no puede arrancar, su error de inicio no es un inventario válido.

El acceso es el acceso técnico al proceso Artisan y al entorno, no un endpoint
web ni un permiso funcional nuevo. El comando no revela IDs de destinatarios,
teléfonos, remitentes, secretos, hashes de contacto, payloads ni errores del
proveedor. No debe registrarse una acción auditable nueva por esta consulta
técnica agregada. `SmsMigrationInventoryTest` prueba lecturas, privacidad,
ausencia de envíos y de nueva actividad.

## Siguiente paso operativo

Antes del corte real, detener SMS de forma controlada, identificar workers y
almacenes antiguos, repetir el inventario y resolver las evidencias originales.
La cancelación de pendientes, conservación de inciertos, migración de procedencia
y retiro del adaptador pertenecen al siguiente incremento. No purgar la cola
compartida ni reenviar una salida incierta para aclarar su estado.

Ver [plan por incrementos y compatibilidad](../explanation/plan-incrementos-sms.md),
[contrato de canales](canales-comunicacion.md) y
[Agent Note](../agent-notes/comunicaciones-notificaciones.md).
