# LabsMobile: incrementos, compatibilidad y traspasos

Decisiones confirmadas por José el 25/11/2026. El alcance de producto sigue en
[Notion](https://app.notion.com/p/3fe61db7db7f81e4af50c4f0f3ab7e41); este documento
conserva la secuencia de implementación y los contratos que deben sobrevivir entre
sesiones. [ADR 0050](adr-0050-proveedor-sms-labsmobile.md) fija LabsMobile.

## Decisiones aceptadas

- José confirmó el 25/11/2026 que Kronik solo ha llegado a desarrollo y QA: no
  existe producción, clientes ni datos históricos que conservar; los datos de
  prueba son desechables. Se elimina el incremento de migración histórica y su
  convivencia con Infobip. La sustitución será directa, con limpieza explícita de
  datos/jobs de prueba cuando corresponda y envíos desactivados. El inventario de
  I0 es una herramienta opcional de QA, no un requisito para demostrar históricos.
- Una instalación corresponde a una financiera; los equipos son departamentos o
  contextos de permisos, no financieras independientes. Conservar el contexto
  original de las operaciones sin usarlo como propietario de catálogos globales.
- LabsMobile sustituirá Infobip para pago recibido y próximo vencimiento, sin
  fallback automático. Contratación, piloto y envíos reales siguen pendientes.
- Las credenciales y configuración técnica provendrán del entorno mediante
  `config()`, no de formularios o secretos guardados desde la UI. La pantalla solo
  recibirá presencia/configuración y, como máximo, máscara constante.
- Notificaciones será una categoría visual en Comunicaciones con catálogo global
  de plantillas y permisos por acción, homologados a productos y documentos:
  consultar, crear, editar, versionar, activar y retirar. No se exige Super Admin.
- Compartir panel/búsqueda/inserción del editor documental; SMS persiste texto
  plano y conserva `{nombre}` como primer nombre y `{fecha_vencimiento}` como fecha
  del evento. No exponer automáticamente todo el catálogo documental.
- Máximo dos segmentos tras sustituir variables; verificar en backend antes de
  despachar, sin truncar ni quitar acentos. La vista previa sintética es estimación.
- Versiones activas inmutables. Activar otra redacción o retirar la utilizada
  cancela pendientes y bloquea su uso nuevo; no recrea automáticamente el mismo
  aviso. Cada salida conserva la versión exacta y evidencia protegida.
- La cuenta, permisos y capacidades de correo y Meta conservan sus contratos
  mientras su homologación técnica se aborda en la tarea transversal.

La [tarea de Integraciones](https://app.notion.com/p/42361db7db7f81e6b747c5ea0241cbb4)
queda pendiente para otra sesión: catálogo global de consulta de CDC/correo/SMS/
WhatsApp y demás integraciones verificadas, estado efectivo, retiro de campos
técnicos de Empresa y endpoints, configuración por entorno y retirada controlada
de secretos de BD. No bloquea que SMS use entorno desde su implementación.

## Una sesión y una PR por incremento

Petición expresa de José del 25/11/2026; prevalece sobre la recomendación general
de no exigir una sesión por PR. Cada cierre deja Agent Note, evidencia en Notion y
prompt de continuación. No iniciar el siguiente incremento en esa misma sesión.
Las PR se dirigen a `feat/comunicaciones-notificaciones`; no a `main`. Conservar
el trabajo compartido de Brian. No forzar ramas ni fusionar `main` durante estas
entregas; una sincronización posterior requiere su propia evaluación.

| Incremento | Entrega verificable |
| --- | --- |
| I0 — preparación | Matriz de compatibilidad, inventario de solo lectura y pruebas aisladas. Completar la clasificación de tablas de comunicaciones en el reinicio QA y corregir fixtures de menús preexistentes; sin cambiar el transporte. |
| I1 — sustitución directa | LabsMobile HTTP, variables de entorno, UI sin escritura de secretos, comprobación técnica, barrera SMS, límites y pruebas sin tráfico real. Retirar referencias activas de Infobip y resolver sus configuraciones, salidas y jobs desechables de dev/QA mediante limpieza explícita; conservar correo y Meta. Los nuevos intentos guardan proveedor y correlación propios. No desarrollar recuperación de históricos ni compatibilidad con callbacks Infobip. |
| I2 — seguimiento LabsMobile | Autenticidad confirmada antes de aceptar callbacks; persistencia, correlación, deduplicación, desorden, niveles de acuse y conciliación de los nuevos intentos LabsMobile. |
| I3 — Notificaciones | Editor de texto plano y variables, catálogo global, permisos por acción, versiones completas, vista previa y segmentos. No se requiere migrar las plantillas desechables de prueba. |
| I4 — cierre técnico | Regresión conjunta, documentación y auditoría completas, barrido acumulado de todas las PR contra `ecb9d4a`, procedimiento de piloto y pendientes externos. |

Esta secuencia sustituye la anterior I1–I5: el antiguo I1 de procedencia histórica
se elimina como entrega independiente y el transporte pasa a I1. Seguimiento,
editor y cierre pasan respectivamente a I2, I3 e I4. Las referencias anteriores a
I5 corresponden al cierre de la secuencia sustituida.

Cada PR requiere `/review medium`, base y SHA revisados, hallazgos resueltos y nueva
revisión si cambia código después. No considerar una solicitud de revisión como
evidencia de revisión terminada. El pase de rango completo de I4 complementa esas
revisiones individuales; no cerrarlo anticipadamente por terminar I0 o integrar una PR.

## Estado de I2 — núcleo interno, recepción pendiente

Rama `feat/sms-i2-seguimiento` desde `85d2db6`, dependiente de #93 y #94,
ambas abiertas al inicio. CI #311 aprobado para I1, revisión medium de código
`cacf89f` sin hilos pendientes. Árbol funcional idéntico en `85d2db6`. Main sigue
en `217acef`, ancestro `bfa0b87`, divergencia 20/89 respecto de I1, sin fusionar.

El usuario no tiene cuenta ni saldo LabsMobile. El [núcleo interno](../reference/seguimiento-sms.md)
se implementa con pruebas sintéticas; autenticación y recepción externa quedan
pendientes, sin declarar completo el seguimiento operativo. No añadir endpoint,
ackurl ni verificador permisivo. Evidencia de validación y publicación en la Agent Note.

## Estado de I1 — publicado el 25/11/2026

[PR I1 #94](https://github.com/jata-imk/kronik/pull/94), código revisado `cacf89f`.
Rama `feat/sms-i1-labsmobile` creada desde `9d0473d` con dependencia de PR #93,
aún abierta. Se conserva I0 y el trabajo compartido. Las consultas al inicio
acreditan CI verde para `3095384` y `213a4b6`, revisión medium final de código
`3095384` y ningún hilo pendiente. El remoto Git apunta a `9d0473d`, aunque la
API de la PR devuelve todavía `213a4b6`; no hay comprobaciones visibles para la
corrección exclusivamente documental. No se afirma aprobación CI de ese SHA.

I1 implementa transporte/configuración por entorno, UI de presencia, consulta de
saldo, doble barrera, dos segmentos, limitador de cuenta con lock y correlación
persistida. Retira transporte/endpoint/conciliación Infobip y añade
[limpieza selectiva QA](../how-to/limpiar-sms-prueba.md). No incorpora seguimiento
LabsMobile, editor o Integraciones. Evidencia de entrega en la Agent Note.

Main sigue en `217acef`; ancestro `bfa0b87`, 89 commits exclusivos de main y 15
de I0 al arrancar I1. Se inspeccionó la divergencia sin fusionar. Los cruces
financieros, de navegación, permisos, auditoría e índices listados a continuación
siguen pendientes de integración conjunta.

## Compatibilidad observada en I0

Base funcional `ecb9d4a`; punto de arranque documental `547d9e5` después de
`6b11bf6`. `main=217acef`; ancestro común `bfa0b87`. Al inspeccionar el 25/11/2026
había 89 commits exclusivos de main y nueve de comunicaciones. No se fusionaron.
Desde `ecb9d4a` hasta el arranque solo cambió documentación.

| Cruce | Contrato que debe preservarse al integrar posteriormente |
| --- | --- |
| `PagoService` | Main corrige fechas civiles de reversos; comunicaciones agrega eventos después de sincronizar proyecciones y dentro de la transacción. Conservar ambos comportamientos, nunca reemplazar el archivo por una rama completa. |
| Navegación y seeders de menús | Main integra calendario y navegación protegida; comunicaciones agrega su acceso y campana. Mantener catálogo, permisos y protección de formularios de main al añadir destinos. |
| Enum y matriz de auditoría | Reunir eventos y emisores sin perder códigos existentes ni duplicar acciones. Verificar transacciones, actor/iniciador y contexto original. |
| Catálogo de permisos | Mantener permisos de calendario y navegación junto a comunicaciones; los nuevos permisos de plantillas serán globales en recurso, evaluados en el contexto autorizado. |
| Rutas y mensajes | Preservar rutas de ambos módulos y errores en español, sin reutilizar identificadores o sobrescribir validaciones. |
| Índices y ADR | Mantener enlaces de ambas entregas. Hay numeración ADR 0047/0048 con asuntos diferentes entre ramas; revisar esa colisión en una integración futura, sin borrar justificación histórica. |

Los archivos modificados por ambos lados desde el ancestro son el enum, pagos,
navegación, ambos seeders, rutas web, validación española y los índices de Agent
Notes/Explicación/How-to/Referencia y matriz de auditoría. No se ha acreditado una
ejecución combinada de main y comunicaciones. I0 no modifica estos comportamientos.

## Pruebas y activación posterior

I0 prueba el [contrato del inventario](../reference/inventario-sms.md) con BD aislada,
sin HTTP ni escritura de dominio/auditoría. El cambio es CLI; no requiere capturas
ni E2E nuevos. Los siguientes incrementos deben agregar sus pruebas propias y
preservar las regresiones de pagos, proyecciones, correo, Meta y editor documental.

La regresión detectó problemas previos de esta rama: el reinicio QA rechazaba las
siete tablas de comunicaciones sin clasificar y las pruebas de menús suponían un
catálogo vacío aunque las migraciones ya registran módulos/vínculos. I0 clasifica
esas tablas en orden de dependencias y conserva todas las barreras de QA,
confirmación, entorno y tablas desconocidas. La prueba de ciclo completo verifica
su limpieza con claves foráneas activas; los fixtures de menú reutilizan el módulo
y comparan el conteo original de vínculos. No se ejecutó un reinicio operativo.

Antes de piloto, confirmar autenticidad y recuperación de callbacks, ruta mexicana
y OMV/portados, formato telefónico, remitente, niveles de acuse, caducidad/cancelación,
idempotencia/consulta y tarifa de cuenta. No inventar firma, endpoint o garantía.
Definir números autorizados, entorno, presupuesto, cupo y umbrales; registrar
aceptación, acuse de operador, recepción observada, demora y segmentos cobrados.
El trabajo con fakes no valida una ruta real ni habilita operaciones con dinero.

La [Agent Note](../agent-notes/comunicaciones-notificaciones.md) mantiene SHA, PR,
pruebas/review y el prompt vigente. No cerrar la iniciativa hasta el barrido final
y la resolución o identificación expresa de pendientes externos.
