# Folios y ayuda contextual

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81ef99efd2e8ed6e3a3c
- Entrega previa: [estatus operativo](empresa-estatus-operativo.md), PR #52.
- Rama: `feat/folios-ayuda-contextual`, base integrada `234ec3d`; PR https://github.com/jata-imk/kronik/pull/53.

## Estado actual

- 2026-10-28: retomado por el usuario. Se añaden regresiones de previews HTML/PDF y fechas explícitas fuera de la transacción de Pest; se publicará el diagnóstico sanitizado del proceso hijo para resolver CI antes de integrar.
- Regresiones locales de reanudación: 24 pruebas de editor documental/folios aprobadas (229 aserciones), Pint aprobado. El helper compartido de crédito pagable ya se carga desde fixtures, sin depender del orden de descubrimiento de pruebas. La fecha explícita requiere aún el caso MariaDB sin transacción exterior.
- `3ca914e`: hooks 511 backend aprobadas, una omitida, 41219 aserciones y build aprobado. Review medium acumulada contra `bc28e5e` sin hallazgos, 72 pruebas/585 aserciones aprobadas. CI `40798934304` vuelve a fallar solo en concurrencia: el hijo arroja PDOException al revertir un savepoint. Se amplía el diagnóstico del harness para conservar la excepción original que podría estar ocultando la reversión, sin alterar la semántica transaccional.
- CI `40799499580` confirmó `HY000/1020` antes del `42000/1305` de savepoint: conflicto de snapshot no reconocido por Laravel 11. Se añaden adaptadores MySQL/MariaDB que lo clasifican y conservan la causa al salir de transacciones anidadas; lectura bloqueante de sucursal antes del snapshot. Regresiones de límite, aborto total, errores no reintentables y registro de drivers; 20 pruebas dirigidas/169 aserciones aprobadas antes del ajuste de lectura. No añade acciones auditables: corrige recuperación transaccional; los eventos de negocio siguen confirmándose una vez junto con su registro.
- 2026-10-27: pausado de nuevo por solicitud explícita del usuario al terminar la jornada. PR #53 abierta, sin integración ni despliegue de folios.
- Implementados ámbitos estables, secuencias/reservas, asignación atómica y migración histórica; configuración, ayudas, variables documentales y referencias visibles.
- Verificación inicial del árbol de trabajo: 508 backend aprobadas y una omitida; 102 Vue y build aprobados. Se añadieron después pruebas y ajustes; registrar conteos del commit final al cerrar.
- E2E dual de originación/PDF/firma/desembolso/pago/reverso aprobado con folios. Corregida propagación de Escape de la ayuda; se repite el caso de configuración.

## Decisiones confirmadas por el usuario

- V1: solicitud, contrato, crédito y recibo, por sucursal de origen. El resto del inventario queda documentado para entregas posteriores.
- Numeración continua, sin reinicio anual; seis dígitos mínimos, sin truncamiento.
- Formatos visibles con guiones: `MTZ-SOL-000001`, `MTZ-CTO-000001`, `MTZ-CRE-000001`, `MTZ-REC-000001`.
- Guardar un único folio visible; limpiar guiones solo al exportar a SIC. Validar desde ahora la unicidad normalizada. No introducir un segundo número de cuenta público.
- Configurar en Sucursales; secuencias genéricas, asignación transaccional, bloqueo, restricciones únicas y control de versiones al guardar.
- Cambiar prefijo solo para emisiones futuras, conservar contadores y reservar todos los prefijos históricos contra reutilización por otra sucursal.
- El contrato recibe número al confirmar su preparación junto con la tabla de pagos; otro contrato tras nueva aprobación recibe otro número. Reintentar el mismo PDF conserva el número.
- Completar registros existentes por creación/ID sin reescribir PDF, firmas, snapshots o hashes históricos; identificar los folios asignados posteriormente.
- Generalizar ayuda financiera a componente común, aplicarlo en Empresa/Sucursales y mostrar vista previa sin reservar números.

## Siguiente paso y riesgos comprobados

- PR #52 integrada con CI aprobado. Completar E2E de configuración, review medium, PR y concurrencia MariaDB en CI; después pasada final acumulada contra `bc28e5e`.
- Preservar asignaciones, reservas y contadores en reinicios QA, incluido modo demo que recrea sucursales. Resolver su identidad de numeración antes de definir las claves foráneas; el modo mínimo conserva Matriz.
- Usar MariaDB aislada y procesos/conexiones independientes para acreditar concurrencia; SQLite no prueba bloqueos de filas.
- El esquema público CDC DT-03-0001 v25 define `CuentaActual` alfanumérica de hasta 25 caracteres; contrastar con especificaciones contratadas de CDC/Buró antes de reporteo productivo. No habilitar reporteo ni dinero real.
- Mantener auditoría en la entrega: configuración de secuencias, asignaciones ligadas al evento de negocio, contexto original y ausencia de datos sensibles. Actualizar matriz única, ADR, referencias, índices y Notion.

## Verificación antes de PR

- E2E de configuración aprobado: ayuda/Escape, vista previa, persistencia, móvil y error visible de prefijo reservado.
- Capturas: [escritorio](../assets/folios-desktop.png) y [móvil](../assets/folios-mobile.png).
- Review medium: corregidas lectura del ámbito conservado y visibilidad de errores; se incorporaron reintentos exteriores y barreras de concurrencia para el cruce actor/numeración. CI MariaDB ejecutado con fallo pendiente de diagnosticar, detallado abajo.

## Punto de continuación de la pausa

- Publicados `4036030` (implementación) y `33787ba` (cierre de ayuda); HEAD de PR #53: `33787ba1e1004190162a740c768b8e729399ab45`.
- Hooks secuenciales de ese HEAD: 510 backend aprobadas, una omitida, 41206 aserciones y build aprobado. La primera publicación falló porque el revisor y los hooks ejecutaron pruebas simultáneas con el mismo disco simulado; repetir secuencialmente resolvió esos fallos. No ejecutar dos suites backend locales a la vez.
- CI de PR #53, run `40633401799`: build, Vue y backend aprobados; falló exclusivamente `Verify folio concurrency on isolated MariaDB`. El script padre reportó RuntimeException en línea 79: uno de los ocho procesos no confirmó sus cinco solicitudes. No se obtuvo todavía la causa del proceso hijo; no asumir que es solo un fallo del harness.
- El caso E2E de configuración y prefijo reservado y el recorrido dual completo están aprobados. Capturas ya versionadas. La ayuda de `33787ba` tiene además regresión Vue de Escape abierto/cerrado.
- Review medium de folios y pasada acumulada contra `bc28e5e` realizadas. La acumulada encontró dos defectos adicionales: restaurar atributos originales como limpios sobrescribe timestamps explícitos en creaciones directas; las previsualizaciones HTML/PDF no tienen valores sintéticos de folios.
- Correcciones locales **sin commit ni verificación posterior**: `TieneFolio` restaura el estado original separado de los atributos; `DocumentoPlantillaController::previewValues()` incluye folios sintéticos; el script de concurrencia imprime diagnóstico sanitizado del hijo (clase/línea, validación o SQLSTATE/código) para poder determinar el fallo de CI.
- Logs locales de diagnóstico: `storage/framework/testing/folios-ci-failed.log`, `folios-review-medium.log`, `folios-review-final.log`, `folios-review-deadlocks.log`, `estatus-folios-review-acumulado.log` y `folios-push.log`.
- Al retomar: añadir regresiones de timestamps fuera de una transacción de pruebas y de previews; ejecutar pruebas locales secuencialmente, publicar las correcciones y diagnosticar/reparar el fallo real de concurrencia con el nuevo detalle del hijo. Verificar CI MariaDB y repetir review medium acumulado del HEAD final antes de integrar #53.
- Cierre pendiente: actualizar descripción de PR y Notion con evidencia final, registrar integración solo cuando ocurra, actualizar notas/documentación de cierre y sincronizar `main`. No hay despliegue ni QA posterior del usuario acreditados.
