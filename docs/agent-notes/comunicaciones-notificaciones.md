# Comunicaciones y notificaciones

## Referencias

- [Requisito de Notion](https://app.notion.com/p/Kronik-Comunicaciones-por-canales-env-os-y-seguimiento-3fe61db7db7f81e4af50c4f0f3ab7e41) y [copia literal conservada](../reference/comunicaciones-por-canales-envios-y-seguimiento.md).
- Rama `feat/comunicaciones-notificaciones`; commits previos `793e684` (base), `1027e89` (móvil), `fb12c7f` (canales y seguimiento) y `36da998` (evidencia). Esta entrega añade parametrización de tiempos y anticipación; PR e integración pendientes al 2026-11-20.
- [Contrato de eventos](../reference/eventos-comunicacion.md), [canales](../reference/canales-comunicacion.md) y [ADR 0049](../explanation/adr-0049-orquestacion-comunicaciones-externas.md).

## Estado — 2026-11-20

Implementado localmente en la rama: bandeja interna contextual; eventos candidatos para pago QA y próximo vencimiento con anticipación configurable por regla/canal (tres días iniciales); equipo estable de nuevos créditos y recuperación conservadora de históricos; consentimiento por equipo/canal/finalidad/contacto con acceso desde la ficha del cliente; reglas, plantillas y ventanas; adaptadores SMTP, Infobip y Meta; comprobaciones técnicas de conexión sin envío; salida en cola después del commit; revalidación previa; historial, filtros y webhooks firmados con deduplicación y conciliación. La vigencia del pago, las esperas operativas y el calendario del scheduler se parametrizan en el entorno. SMS y WhatsApp no están activados en ningún proveedor. Telegram permanece opcional y desactivado.

El entorno local sigue con `MAIL_MAILER=log`. La barrera `COMMUNICATIONS_SEND_ENABLED=false` es el valor predeterminado y no se envió correo al destinatario personal autorizado ni a acreditados. No se han configurado credenciales ni elegibilidad de prestamista/proveedor. La cuenta, identidad, remitente, permisos externos, plantilla Meta y ruta SMS mexicana requieren validación externa antes de tráfico real. No se ha desplegado en VPS ni confirmado cron/worker del servidor. El usuario administra la VPS; no se accedió a ella.

## Evidencia

- Pest conjunto de comunicaciones, pagos, solicitudes y campana: **102 pruebas, 1,629 aserciones**, SQLite aislado con PHP 8.4, 2026-11-20. Incluye permisos, secretos, consentimientos, contacto cambiado, idempotencia, cola, rechazo/timeout, firmas, duplicados, desorden y conciliación de webhooks.
- Prueba unitaria Vue de campana: 1 aprobada. Build Vite aprobado. `vendor/bin/pint --dirty` y `git diff --check` aprobados.
- E2E con Edge y SQLite desechable: 2 recorridos aprobados, escritorio y móvil, regla/vista previa/historial y anchura de las cuatro secciones. Capturas sintéticas locales: `test-results/communications-desktop.png` y `test-results/communications-mobile.png`. El primer lanzador `tests/E2E/run.js` falló por una aserción interna de `fetch` de Node 24; la ejecución manual con servidor visible y la misma base aislada sí pasó. `public/hot` fue restaurado.
- Regresión de crédito anticipado, Bullet y tablero: **26 pruebas, 1,234 aserciones** aprobadas con PHP 8.4 y SQLite aislado.
- Tras integrar la ficha del cliente y el límite diario, **13 pruebas focalizadas, 238 aserciones** aprobadas; incluye webhooks, entregas, gestión, permisos y revocación. La comprobación técnica de APIs añadió otra prueba de gestión (**5 aprobadas, 79 aserciones**). Build Vite repetido con éxito. `route:list` y `schedule:list` registran las rutas y los dos comandos; esto no acredita ejecución del programador en VPS.
- Parametrización posterior: **14 pruebas focalizadas, 257 aserciones** de preparación, gestión y entregas; otras **20 pruebas, 312 aserciones** de pagos, webhooks y transportes; tras ajustar valores iniciales, **11 pruebas, 196 aserciones** de preparación y entregas. Build Vite aprobado. `schedule:list` reflejó intervalos temporales de 15 y 10 minutos configurados por entorno.
- Cierre de parametrización: **37 pruebas, 615 aserciones** de comunicaciones y pagos con SQLite aislado y PHP 8.4. Incluye dos canales con anticipaciones distintas para un solo vencimiento, reprogramación de salida ya en cola, valores predeterminados variables y validación de rango. Build Vite, Pint y enlaces locales de la documentación aprobados. No se enviaron mensajes reales.

## Siguiente paso

La subida de la rama falló el 2026-11-20: este entorno no pudo conectar con `github.com:443` (TCP fallido); la alternativa `ssh.github.com:443` respondió pero rechazó la llave pública disponible, y `gh` no está instalado. Reintentar `git push origin feat/comunicaciones-notificaciones` al recuperar acceso. Después abrir PR, ejecutar `/review medium` y resolver hallazgos. Para QA real, configurar SMTP con dominio y destinatario de prueba autorizado; para SMS/WhatsApp, obtener aprobación escrita de uso, cuentas, remitentes, plantillas y webhook HTTPS antes de activar. Registrar recepción real y operación del programador/worker solo cuando haya evidencia. Actualizar Notion al confirmar integración/QA.
