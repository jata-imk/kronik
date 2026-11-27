# Comunicaciones y notificaciones

## Referencias

- [Tarea en Notion](https://app.notion.com/p/3fe61db7db7f81e4af50c4f0f3ab7e41), estado Doing.
- Destino autorizado: `feat/comunicaciones-notificaciones`, rama original de Bryan.
- I4: `feat/comunicaciones-i4-cierre`, desde I3 `82c7756`.
- PR: [#91 original](https://github.com/jata-imk/kronik/pull/91), [#93 I0](https://github.com/jata-imk/kronik/pull/93), [#94 I1](https://github.com/jata-imk/kronik/pull/94), [#95 I2](https://github.com/jata-imk/kronik/pull/95), [#96 I3](https://github.com/jata-imk/kronik/pull/96).
- [Plan de incrementos](../explanation/plan-incrementos-sms.md), [ADR 0049](../explanation/adr-0049-orquestacion-comunicaciones-externas.md), [ADR 0050](../explanation/adr-0050-proveedor-sms-labsmobile.md), [ADR 0051](../explanation/adr-0051-notificaciones-globales-y-previas-html.md).
- [Guía de VPS y variables](../how-to/preparar-comunicaciones-vps.md), [QA](../how-to/probar-comunicaciones.md), [seguimiento SMS](../reference/seguimiento-sms.md), [auditoría](../reference/auditoria-eventos.md).

## Estado — 27/11/2026

Cierre técnico I4 en validación. José autorizó consolidar #93–#96 e I4 en la
rama original, conservando ramas auxiliares. Sustituye el «sin merge» del
traspaso solo para ese destino. No incluye `main` ni la rama separada de Bryan
`feat/comunicaciones-whatsapp`. Sin despliegue ni validación externa.

Código final I4: `3f62b15f328afc8ae7b86fff13a41ea3d4612b80`, después de `5fe2d88`.
Código publicado; PR, CI e integración pendientes de completar en esta sesión.
Pre-push completo repetido: **1,168 aprobadas, dos omitidas optativas y 49,778
aserciones**, duración 541.21 s; build Vite aprobado. Primera ejecución
interrumpida sin resultado global, no computada como aprobación.
El cierre posterior solo modifica documentos, con identidad funcional respecto
a `3f62b15` comprobada. Su publicación usa la excepción documental del hook
tras verificar UTF-8/LF, enlaces, índices y diff; conserva la pasada completa
anterior y requiere CI propio del HEAD final de la PR.

## Barrido conjunto y revisiones

Se revisó todo `ecb9d4a..3f62b15` y el antecedente original
`bfa0b87..ecb9d4a`, incluyendo #91 y las interacciones I0–I3. #91 continúa
cerrada sin integrar en main; su revisión inicial solicitada no acreditaba
resultado, por lo que se incluyó expresamente en el barrido I4.

| Incremento | Código revisado | HEAD previo a I4 / CI por SHA |
| --- | --- | --- |
| I0 / #93 | `3095384` | API PR `213a4b6`, [CI #309 aprobado](https://github.com/jata-imk/kronik/actions/runs/45646122948); ref Git real `9d0473d`, corrección documental sin CI propio |
| I1 / #94 | `cacf89f` | `85d2db6`, [CI #311 aprobado](https://github.com/jata-imk/kronik/actions/runs/45696230037) |
| I2 / #95 | `c7a31bd` | `2c865b1`, [CI #314 aprobado](https://github.com/jata-imk/kronik/actions/runs/45709799131) |
| I3 / #96 | `6a48765` | `82c7756`, [CI #316 aprobado](https://github.com/jata-imk/kronik/actions/runs/45895167209) |

Las revisiones medium terminadas constan en las PR; los cierres posteriores
solo cambian documentación, con identidad funcional comprobada. Sin hilos
pendientes al retomar. La discrepancia API/ref de I0 se conserva explícita;
`9d0473d` es ancestro de I1–I4, no se pierde su corrección documental.

Revisión medium acumulada inicial de `82c7756`: cuatro hallazgos. Revisión de
`5fe2d88`: seis correcciones confirmadas y un hallazgo adicional de bloqueos.
**Revisión final de `3f62b15`, Codex CLI 0.172.0, medium: sin otros defectos
accionables, todos los hallazgos resueltos.** Revisión estática, sin ejecutar
PHP/pruebas concurrentes. Los informes ignorados están en
`storage/framework/testing/i4-review-{inicial,final,release}-result.txt`.

## Correcciones I4

- Serializar el cupo diario por cliente entre créditos/canales y contar el día
  en la zona de los timestamps almacenados, incluida la madrugada no UTC.
- Abrir la lectura consistente después de bloquear crédito/cliente para observar
  reversos confirmados antes de reservar el intento. Orden común crédito →
  plantilla → regla → cliente → salida; preparación también bloquea crédito
  primero y revalida el evento, evitando inversión por la FK.
- Derivar `effective_status` del consentimiento con el contacto actual: vigente,
  contacto cambiado o revocado, sin exponer hash/evidencia ni mutar al consultar.
- Serializar las lecturas de campana, deduplicar clics y descartar respuestas o
  trabajos tardíos tras cambiar contexto; el resumen no recupera no leídos.
- Revertir la FK I3 con nombre MariaDB y columnas SQLite; reinstalar I1–I3 sin
  perder correo/Meta ni duplicar permisos.

Son siete defectos corregidos, con regresiones. No se crean acciones auditables
nuevas: se mantienen eventos, actor/iniciador, contexto original y privacidad.
El [contrato de canales](../reference/canales-comunicacion.md) y la matriz
explican las transacciones y la ausencia justificada de eventos adicionales.
CI incluye ahora el harness de concurrencia de comunicaciones y el push al destino.

## Evidencia local del código final

- Focal backend: 25 pruebas / 545 aserciones; casos posteriores incluidos en la suite completa.
- Frontend completo: 188 pruebas / 71 archivos; build Vite aprobado.
- Harness MariaDB 11 aislada: cupo compartido entre créditos/canales, reverso
  confirmado antes del intento y cruce prepare/send sin deadlock, aprobados.
  HTTP/Mail falsos, BD `kronik_test`, sin tráfico de proveedor.
- Esquema: ciclo SQLite aislado de reversión/reinstalación I1–I3 aprobado,
  preservando filas correo/Meta y sin duplicar módulos/permisos.
- E2E: primera pasada conjunta, seis aprobados y uno fallido en política por
  espera anterior al POST/redirección. Se conservaron traza, captura y vídeo;
  se añadió espera de respuesta y mocks/consola compartidos sin omitir pasos.
  Repetición aislada de política: una aprobada. No se presenta la pasada fallida
  como aprobación global. CI debe repetir los siete recorridos sobre su SHA.
- PDF real: 9,704 bytes. Guardas Node E2E: cuatro aprobadas. Pint, diff --check,
  UTF-8/LF, enlaces locales, índices y cobertura temática comprobados.
- Pruebas PHP secuenciales por checkout; revisión estática en paralelo sin PHP.
  No se modificó `.env`, ni se ejecutaron migraciones o limpieza operativas.

## Integración y límites

Antes de consolidar: destino `547d9e5`, main `217acef`, ancestro común `bfa0b87`;
main/I3 divergían 89/32 commits. Main conserva cambios independientes de pagos,
navegación, permisos, auditoría, rutas, editor, CI e índices. Hay colisión de
numeración ADR 0047/0048. No se acredita ejecución combinada con main. El
[plan](../explanation/plan-incrementos-sms.md#compatibilidad-observada-en-i0)
registra los cruces que una integración posterior debe conservar.

La rama WhatsApp separada sigue `8719362`; su preparación de piloto no forma
parte de I4. Si la VPS usa main, evaluar integración antes de cambiar ramas.
El correo reutiliza el mailer Laravel existente: si la VPS usa SMTP ZeptoMail,
usa sus mismas credenciales `MAIL_*`, con remitente autorizado. Esto describe
el código; no confirma los secretos ni la ejecución de la VPS.

## Pendientes reales y siguiente paso

Completar publicación/CI e integración autorizada en la rama original y registrar
SHA, revisiones y resultado vigente. Conservar main y todas las ramas auxiliares.

Después del cierre técnico quedan recepción SMS externa autenticada,
cuenta/ruta mexicana/remitente/tarifas, presupuesto y piloto con números aprobados.
I2 solo acredita núcleo interno sintético; no hay endpoint SMS, ackurl, firma o
consulta inventados. No hay cuenta/saldo LabsMobile confirmados por José.
Ambas barreras permanecen cerradas; sin envíos reales, compras, Integraciones,
limpieza operativa, despliegue, SSH ni acceso a VPS. La iniciativa permanece
Doing y no se cierra por integrar PR. El usuario ejecutará los comandos de la
guía de VPS y compartirá sus resultados para registrar el QA posterior.
