# Investigación de LabsMobile para los SMS de Kronik

Consulta: 2026-11-24. José eligió LabsMobile por costo; la integración, la cuenta
y el piloto permanecen pendientes. Ver [ADR 0050](adr-0050-proveedor-sms-labsmobile.md)
y [Agent Note](../agent-notes/comunicaciones-notificaciones.md).
El [contrato de canales](../reference/canales-comunicacion.md) describe todavía
el código con Infobip de `ecb9d4a`; esta investigación no acredita un cambio funcional.

## Sustitución del código Infobip

Alcance solicitado por José para la siguiente sesión de planeación. Conservar
Infobip en este commit documental; su sustitución se implementará y probará como
un incremento concreto, sin un segundo proveedor activo ni fallback automático.

| Superficie identificada en `ecb9d4a` | Trabajo que debe incluir el plan |
| --- | --- |
| `InfobipSmsTransport` y `ChannelTransport` | Implementar LabsMobile con el contrato compartido y retirar el adaptador anterior cuando no queden consumidores necesarios. No renombrarlo conservando su payload. |
| `CommunicationController` | Sustituir selección, capacidades y prueba de conexión; configuración técnica por entorno y UI sin escritura de secretos. Conservar permisos y eventos. |
| `CommunicationOrchestrator` | Cambiar la resolución del transporte SMS conservando consentimiento, reglas, contexto, cola, idempotencia y resultado incierto. |
| `CommunicationWebhookController`, rutas y `CommunicationWebhookService` | Retirar recepción y conciliación Infobip; definir recepción LabsMobile con autenticidad y nivel de acuse. No se requiere convivencia histórica. |
| `Communications/Index.vue` | Actualizar proveedor, campos, ayudas y estados; impedir que secretos Infobip se interpreten como credenciales LabsMobile. |
| Pruebas de transporte, gestión, preparación y webhooks | Cambiar fixtures del proveedor y añadir regresiones del corte, manteniendo comportamiento genérico y aislamiento. |
| Contrato, guía QA, matriz de auditoría y variables | Actualizar lo que realmente cambie; preservar ADR y evidencia histórica del proveedor anterior. |

José confirmó el 25/11/2026 que Kronik solo existe en desarrollo y QA, nunca ha
estado en producción, no tiene clientes ni históricos que conservar y sus datos
de prueba son desechables. Esta confirmación sustituye la hipótesis anterior de
una migración histórica: no desarrollar backfill, convivencia de callbacks ni
conciliación Infobip para preservar datos de prueba.

La sustitución será directa. Resolver configuraciones y datos/jobs SMS de prueba
mediante limpieza explícita en el entorno correspondiente, con envíos desactivados
y sin afectar las colas o datos compartidos de correo y Meta. No se ejecuta ninguna
limpieza en esta corrección documental. Los nuevos intentos LabsMobile deberán
guardar proveedor y correlación propios para su seguimiento normal.

Secuencia vigente en el [plan por incrementos](plan-incrementos-sms.md): transporte,
configuración y retiro activo de Infobip; seguimiento LabsMobile; editor; cierre
y piloto autorizado. El inventario I0 es opcional para QA y no bloquea la
sustitución por falta de datos históricos. Conservar el orquestador y los contratos
comunes. Revisar la divergencia frente a `main`, sin fusionar la rama ni desplazar
el trabajo de Brian.

## Editor visual de plantillas: evaluación pendiente

José propuso aprovechar el editor documental, su panel de variables y una nueva
categoría de Notificaciones con texto plano y límites propios de SMS. La
[evaluación del editor](editor-plantillas-notificaciones.md) documenta piezas
reutilizables, diferencias de formato/variables/permisos y alternativas.
Reutilizar esa experiencia es viable; la ubicación y el modelo de persistencia
siguen por decidir. No se aprobó convertir avisos SMS en documentos PDF ni se
implementó la nueva categoría.

## Cobertura mexicana y operadores virtuales

La [ficha mexicana](https://www.labsmobile.com/es/plataforma-envio-sms-masivo/mexico)
anuncia cobertura del 100% y rutas directas. Su lista no identifica expresamente
a Bait, PilloFon, Axios ni Altán. Declara acuses de red y remitente numérico
aleatorio; números dedicados, cortos y máscara se consultan aparte. Son
capacidades anunciadas, no resultados del piloto ni garantías de recepción.

Un OMV utiliza capacidad de una red mayorista: lo explica el
[IFT en su medición de calidad](https://www.ift.org.mx/sites/default/files/industria/notificacionesdiarias/calidad-del-servicio-movil/2023/10/informegeneralquintanaroo2023.pdf),
que situaba PilloFon bajo Altán en 2023. Las fuentes de
[Bait](https://mibait.com/utilidades/archivos/footer/PUJ.pdf) y
[Axios](https://axiosmobile.mx/files/Politica-de-Uso-Justo-AXIOS-230524-003.pdf)
también describen esa red. Es contexto de conectividad, no prueba de la ruta A2P
de LabsMobile al día de la consulta.

Hipótesis para validar: Kronik enviaría al número internacional y el proveedor
resolvería la ruta; no se necesita una integración de Kronik por marca móvil.
La entrega concreta depende de interconexión, portabilidad, filtros, línea y
dispositivo. No excluir automáticamente un OMV por no aparecer en una lista
comercial, ni prometer cobertura real basándose solo en el 100% publicado.
Preguntar por Altán y esos tres OMV, números portados y disponibilidad de acuses
por ruta. El piloto debe conservar marca declarada, evidencia del teléfono y
estado recibido, sin publicar números personales.

La ficha muestra ejemplos antiguos con prefijo mexicano 521. Contrastar con
la [explicación del IFT sobre 52 y diez dígitos](https://www.ift.org.mx/conocenos/pleno/entrevistas/javier-juarez-mojica/entrevista-al-comisionado-javier-juarez-mojica-en-jose-cardenas-informa-sobre-la-nueva-marcacion-10).
Preservar el contacto canónico; confirmar el formato de la ruta antes de
programar una transformación específica del transporte.

## Estados y evidencia

El [histórico WebSMS](https://www.labsmobile.com/es/blog/exportar-sms-otras-estadisticas-la-plataforma-labsmobile)
distingue procesado, enviado y entregado; también caducado, rechazado, no
entregable y duplicado. Un error puede deberse al número, filtros o conectividad;
no diagnosticar automáticamente cuál fue su causa.

Propuesta para Kronik, pendiente de implementación:

| Evidencia | Interpretación que debe conservar Kronik |
| --- | --- |
| Respuesta positiva del envío | Aceptado por el proveedor; no demuestra llegada al teléfono. |
| Acuse de operador | Admitido por la red; conservar ese nivel de evidencia. |
| Acuse explícito de dispositivo | Entregado al dispositivo, únicamente si la ruta lo suministra. |
| Caducado | Se agotó el plazo del intento; no crear otro envío automático. |
| Rechazado o no entregable | Fallo con causa original protegida; no acusar al cliente de bloquearlo. |
| Desconocido o falta de respuesta | Resultado sin resolver; revisar antes de repetir. |
| Duplicado filtrado por proveedor | No inventar una entrega nueva. Correlacionar con el intento original. |

La API distingue niveles `operator`, `handset` y `error`; describe causas como
`BLOCKED`, `DELIVRD`, `EXPIRED`, `REJECTD`, `UNDELIV` y `UNKNOWN`. La lectura es
exclusiva de RCS en esa documentación. Ver [recepción de estados](https://www.labsmobile.com/es/api-sms/versiones-api/http-rest-post-json).
El código actual solo tiene aceptación/entrega/lectura y fallos generales;
habrá que definir cómo conservar el nivel y la causa sin equiparar acuse de red
con llegada al dispositivo. No se ha aprobado una migración concreta.

## Teléfono apagado, reintentos y caducidad

Según el [artículo de analítica](https://www.labsmobile.com/es/blog/la-analitica-pieza-clave-en-el-envio-masivo-de-sms),
un SMS no entregado puede caducar tras los intentos; 72 horas es el plazo
habitual citado cuando el destinatario no ha estado operativo. No establece
una garantía única para todas las redes mexicanas.

Ejemplo conceptual: se intenta un recordatorio a las 10:00 con el móvil apagado;
si vuelve a estar disponible a las 12:00, podría recibirlo entonces. Si supera
la vigencia sin recibirlo, el intento puede terminar caducado. Es una explicación
del flujo, no una prueba ejecutada. En una ruta con solo acuse de operador,
el estado observado podría no describir ese desenlace al nivel del teléfono.

No se encontró un parámetro público de vigencia de entrega en la API examinada.
La cancelación documentada afecta a mensajes programados pendientes; no prueba
que se pueda retirar un SMS ya cedido a la red. Tampoco confundir el vencimiento
del saldo comprado con la caducidad de un mensaje.

Preguntar por el plazo efectivo de la ruta mexicana, posibilidad de reducirlo,
unidad, mínimo/máximo y efecto sobre los acuses. La vigencia de un evento en
Kronik solo controla el despacho previo; no retira un SMS en la red después de
un pago. Proponer textos con fecha concreta en lugar de referencias como “hoy”
y conservar la revalidación financiera, de contacto y consentimiento al despachar.

## API y callbacks

Los [primeros pasos](https://www.labsmobile.com/es/blog/sms-de-labsmobile-primeros-pasos-para-desarrolladores)
documentan envío JSON sobre HTTPS, autenticación de cuenta/token, consulta de
saldo y tarifas y gestión de programados. Hay una
[librería PHP oficial](https://www.labsmobile.com/es/api-sms/librerias-sdk/php),
con modo simulado sin envío ni consumo. Esto permite adaptar el transporte
existente; la elección de SDK o cliente HTTP se resolverá al implementar.

La [API de envío y estados](https://www.labsmobile.com/es/api-sms/versiones-api/http-rest-post-json)
devuelve identificador y código; comprobar ambos. Los callbacks son GET hacia
`ackurl`, configurable por envío o cuenta; contienen identificación, nivel,
resultado, causa y fecha GMT. Indica hasta cinco reintentos ante error HTTP,
pero enumera cuatro intervalos: 30 segundos, cinco minutos, media hora y seis
horas. Aclarar la secuencia. No se identificaron en lo examinado una firma,
TTL configurable ni una consulta autenticada de estado individual.

La [seguridad de cuenta](https://www.labsmobile.com/es/blog/mejora-la-seguridad-de-tu-cuenta-labsmobile)
incluye token y restricción de IP de origen; esta última protege llamadas hacia
LabsMobile, no autentica callbacks hacia Kronik. Confirmar un mecanismo verificable
para recibir estados. No inventar HMAC ni aceptar resultados por una URL pública
sin comprobación. Evitar que parámetros de callbacks con teléfono se copien a
auditoría o logs de acceso; revisar redacción de registros durante implementación.

Diseño propuesto: un identificador por salida, correlación por contexto original,
recepción persistida antes de responder éxito, deduplicación y tratamiento de
eventos adelantados/desordenados. Conservar resultado incierto ante timeout.
Un identificador de correlación o el filtro de duplicados del proveedor no
demuestran idempotencia contractual de su API. No reenviar a ciegas.

La documentación indica un máximo de diez peticiones por segundo. Otros límites
de cuenta (diario y lote) y filtros de destino/duplicados se configuran aparte;
revisar capacidades exactas sin confundir peticiones con mensajes por segundo.
No se hicieron pruebas autenticadas de endpoints, límites o SDK.

## Porcentajes y soporte

Un [artículo comercial](https://www.labsmobile.com/es/blog/enviar-sms-gratis-online)
publica 99.9% de entrega. La ficha mexicana anuncia 100% de cobertura y 98% de
lectura; cobertura, recepción y lectura miden cosas distintas. No se encontró
una serie mexicana verificable por operador con período y denominador.

Los [términos, anexo I](https://www.labsmobile.com/es/sobre-nosotros/terminos-del-servicio)
fijan 99.4% de disponibilidad mensual para API y WebSMS. Miden disponibilidad
del servicio, no porcentaje de SMS recibidos; contemplan exclusiones. El saldo
comprado tiene una vigencia de 18 meses. No convertir publicidad de lectura en
un estado de SMS normal ni dar por validada la capacidad por una muestra pequeña.

En [contacto](https://www.labsmobile.com/es/contacto) se anuncia chat/WhatsApp
laborables y tickets/email con respuesta en seis horas y resolución de 24–48
horas laborables cuando sea necesaria. Es información pública, no soporte
comprobado en una incidencia nuestra. El centro de ayuda y la página de estado
no fueron accesibles desde la herramienta web de esta investigación; no se
interpretó como caída general del proveedor.

## Texto, remitente y respuestas

Revisar el texto completo con nombre y fecha sustituidos: el producto de
[SMS Unicode](https://www.labsmobile.com/es/envio-sms-masivo/sms-unicode)
reduce la capacidad frente al texto estándar y los
[concatenados](https://www.labsmobile.com/es/envio-sms-masivo/sms-concatenados)
consumen varias partes. Definir un límite de segmentos por aviso; no suprimir
acentos automáticamente sin acordar la redacción del producto.

No prometer que el teléfono mostrará “Kronik” ni que responder al SMS abrirá una
conversación. Confirmar el remitente contratado y el tratamiento de respuestas;
las bajas pueden mantenerse en la operación actual hasta definir otro flujo.
Cuenta, acceso a teléfonos/textos, retención y separación entre prestamistas
deben precisarse con el proveedor. No reconstruir equipo de origen desde las
preferencias mutables del iniciador.

## Preguntas concretas para LabsMobile

Preparadas para el usuario; no se enviaron:

1. ¿Qué ruta A2P mexicana cubre Bait, PilloFon, Axios y otros OMV de Altán,
   incluidos números portados? ¿Qué nivel de acuse devuelve cada red?
2. ¿La aceptación del operador puede figurar como entrega en WebSMS/API?
   ¿Cómo se distingue de recepción confirmada al dispositivo?
3. ¿Cuál es la vigencia real del mensaje y puede limitarse por envío?
   ¿Qué puede cancelarse después de entregarlo al operador?
4. ¿Cómo se autentican los callbacks y cuáles son sus IP, campos, reintentos
   y política ante eventos perdidos o fuera de orden?
5. ¿Existe consulta autenticada por identificador y una clave idempotente?
   ¿Qué ocurre si se repite un identificador tras timeout?
6. ¿Qué remitente, tarifa, segmentos, cobro de fallidos y compra mínima aplican
   a avisos de pago/vencimiento de créditos mexicanos en nuestra cuenta?
7. ¿Cómo se separan clientes/prestamistas, credenciales, saldo y datos?

## Piloto y criterios propuestos

Sin ejecución: faltan cuenta, número autorizado y presupuesto. Empezar con texto
sintético y una muestra que incluya Telcel, AT&T, Movistar, Bait, PilloFon y Axios
según disponibilidad, además de un número portado cuando se pueda.

| Escenario | Evidencia que se necesita |
| --- | --- |
| Teléfono encendido | Aceptación API, acuse, hora real de recepción y remitente observado. |
| Apagado 30–60 minutos y encendido posterior | Recepción al volver y diferencia entre el acuse y el teléfono. |
| Caducidad | Plazo efectivo acordado; usar vigencia corta solo si existe. Un fake no valida la ruta. |
| Texto largo y caracteres especiales | Segmentos cobrados y contenido recompuesto en el teléfono. |
| Falta de saldo, timeout y reintento | Simular primero; no duplicar mensajes para resolver una respuesta perdida. |
| Callbacks inválidos, duplicados y desordenados | Rechazo o persistencia segura, sin falsas entregas ni regresión de estado. |
| Pago/cambio de contacto/baja antes de despachar | Cancelación de salida obsoleta sin contactar al proveedor. |

Medir por marca/red y escenario: intentados, aceptados, acuses de operador,
confirmación a dispositivo cuando exista, recepción manual, pendientes, fallos,
caducados y demoras. Acordar umbrales antes de evaluar; una muestra exploratoria
no certifica porcentajes comerciales. Actualizar contrato, auditoría, pruebas y
documentación con el comportamiento implementado antes de cerrar la integración.
