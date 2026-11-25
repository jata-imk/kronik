# LabsMobile: incrementos, compatibilidad y traspasos

Decisiones confirmadas por José el 25/11/2026. El alcance de producto sigue en
[Notion](https://app.notion.com/p/3fe61db7db7f81e4af50c4f0f3ab7e41); este documento
conserva la secuencia de implementación y los contratos que deben sobrevivir entre
sesiones. [ADR 0050](adr-0050-proveedor-sms-labsmobile.md) fija LabsMobile.

## Decisiones aceptadas

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
| I0 — preparación | Matriz de compatibilidad, inventario de solo lectura y pruebas aisladas; sin cambiar transporte ni datos de negocio. |
| I1 — procedencia y corte | Proveedor/cuenta/revisión/correlación estables, históricos desde evidencia, pendientes y jobs anteriores seguros. No reinterpretar IDs ni reenviar inciertos. |
| I2 — transporte/configuración | LabsMobile HTTP, variables de entorno, UI sin escritura de secretos, comprobación técnica, barrera SMS, límites y pruebas sin tráfico real. |
| I3 — seguimiento/retiro | Autenticidad confirmada antes de aceptar callbacks; persistencia, correlación, deduplicación, desorden y niveles de acuse. Retirar consumidores Infobip cuando cierre su histórico. |
| I4 — Notificaciones | Editor de texto plano y variables, catálogo global, permisos por acción, versiones completas, migración conservadora de textos, vista previa y segmentos. |
| I5 — cierre técnico | Regresión conjunta, documentación y auditoría completas, barrido acumulado de todas las PR contra `ecb9d4a`, procedimiento de piloto y pendientes externos. |

Cada PR requiere `/review medium`, base y SHA revisados, hallazgos resueltos y nueva
revisión si cambia código después. No considerar una solicitud de revisión como
evidencia de revisión terminada. El pase de rango completo de I5 complementa esas
revisiones individuales; no cerrarlo anticipadamente por terminar I0 o integrar una PR.

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

Antes de piloto, confirmar autenticidad y recuperación de callbacks, ruta mexicana
y OMV/portados, formato telefónico, remitente, niveles de acuse, caducidad/cancelación,
idempotencia/consulta y tarifa de cuenta. No inventar firma, endpoint o garantía.
Definir números autorizados, entorno, presupuesto, cupo y umbrales; registrar
aceptación, acuse de operador, recepción observada, demora y segmentos cobrados.
El trabajo con fakes no valida una ruta real ni habilita operaciones con dinero.

La [Agent Note](../agent-notes/comunicaciones-notificaciones.md) mantiene SHA, PR,
pruebas/review y el prompt vigente. No cerrar la iniciativa hasta el barrido final
y la resolución o identificación expresa de pendientes externos.
