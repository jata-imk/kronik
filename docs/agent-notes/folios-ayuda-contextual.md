# Folios y ayuda contextual

## Entrega integrada

- Integrado el 2026-10-28 (America/Mexico_City) mediante [PR #53](https://github.com/jata-imk/kronik/pull/53), merge `2cc3f4a`; HEAD funcional `f0c3459`.
- [Tarea Notion](https://app.notion.com/p/3fe61db7db7f81ef99efd2e8ed6e3a3c). Base: estatus operativo integrado en `234ec3d`, [PR #52](https://github.com/jata-imk/kronik/pull/52).
- Implementación completa; sin decisiones pendientes. Sin despliegue ni QA posterior del usuario acreditados.
- Contrato canónico: [folios](../reference/folios.md); arquitectura: [ADR 0030](../explanation/adr-0030-folios-negocio.md). Inventario posterior fuera de v1 documentado allí.

## Comportamiento y decisiones

- Solicitud, contrato preparado, crédito y recibo por sucursal de origen; numeración continua, seis dígitos mínimos, sin truncamiento ni reinicio anual.
- Un único folio visible con guiones; normalización técnica para unicidad y futura exportación SIC. Prefijos anteriores reservados y cambios solo para futuras emisiones; no disminuyen contadores.
- Contrato numerado antes de firmar, al confirmar preparación; reintentos conservan número. Asignación atómica con negocio, locks, unicidad y control de versión.
- Historia completada por creación/ID sin alterar PDF, firmas, snapshots, hashes o timestamps. Los ámbitos y reservas sobreviven al reinicio QA.
- Configuración en Sucursales, ayuda común y vistas previas sin consumir números; referencias visibles en operación, documentos y actividad.

## Evidencia final

- Hook secuencial de `f0c3459`: 519 pruebas backend aprobadas, una omitida, 41261 aserciones; build y Pint aprobados. No ejecutar suites backend locales simultáneas: comparten el disco simulado.
- [CI final de PR](https://github.com/jata-imk/kronik/actions/runs/40801112168) aprobado, incluidas 103 pruebas Vue y MariaDB aislada: 40 creaciones paralelas, dos caminos con cruce de bloqueos (42 folios únicos consecutivos) y una creación con fechas explícitas (folio 43).
- [CI de main](https://github.com/jata-imk/kronik/actions/runs/40801550026) aprobado sobre el merge `2cc3f4a`, incluida la concurrencia MariaDB.
- E2E Chromium aprobados: configuración/ayuda/Escape, prefijo reservado, vista previa/persistencia/móvil y recorrido dual de originación/PDF/firma/desembolso/pagos/reverso/anticipos/liquidación.
- Capturas: [escritorio](../assets/folios-desktop.png), [móvil](../assets/folios-mobile.png).
- Review medium y correcciones: ámbitos conservados, errores visibles, deadlocks exteriores, fechas explícitas y previews sintéticos. Diagnóstico real de MariaDB `HY000/1020`: conflicto snapshot no reconocido por Laravel 11; adaptadores registrados preservan causa y reintento exterior limitado, sin desactivar aislamiento.
- Pasada acumulada contra `bc28e5e`: corregida atomicidad del PDF bloqueado y su auditoría, con fallo/reintento/contexto y ausencia de duplicados. Última revisión medium sobre `f0c3459` sin hallazgos accionables; no ejecutó pruebas, respaldadas por hooks/CI.
- Matriz única, contratos, ADR, índices y 178 enlaces locales revisados sin faltantes. Auditoría de numeración y asignación histórica incluida; ayuda y vistas previas no añaden acciones auditables.

## Pendientes reales

- Programar despliegue con respaldo, mantenimiento y workers detenidos; aplicar migraciones documentales, esquema de folios y relleno histórico. Comprobar prefijos antes del relleno y contrastar conteos. No se ejecutaron sobre la BD del usuario.
- Validar con el usuario el entorno desplegado. No habilitar SIC productivo ni dinero real; validar especificaciones contratadas antes del reporteo futuro.
