# Folios y ayuda contextual

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81ef99efd2e8ed6e3a3c
- Entrega previa: [estatus operativo](empresa-estatus-operativo.md), PR #52.
- Rama/PR de esta tarea: todavía no creadas.

## Estado actual

- 2026-10-26: plan aprobado; implementación no iniciada. Pausa explícita del usuario al terminar la jornada.
- Se inspeccionaron sucursales, servicios de originación/pagos, documentos y reinicio QA; no hay código de folios agregado.

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

- Primero consultar CI/integración de PR #52. Después crear rama y PR independientes para esta tarea, con revisión medium, correcciones y pasada final acumulada contra `bc28e5e`.
- Preservar asignaciones, reservas y contadores en reinicios QA, incluido modo demo que recrea sucursales. Resolver su identidad de numeración antes de definir las claves foráneas; el modo mínimo conserva Matriz.
- Usar MariaDB aislada y procesos/conexiones independientes para acreditar concurrencia; SQLite no prueba bloqueos de filas.
- El esquema público CDC DT-03-0001 v25 define `CuentaActual` alfanumérica de hasta 25 caracteres; contrastar con especificaciones contratadas de CDC/Buró antes de reporteo productivo. No habilitar reporteo ni dinero real.
- Mantener auditoría en la entrega: configuración de secuencias, asignaciones ligadas al evento de negocio, contexto original y ausencia de datos sensibles. Actualizar matriz única, ADR, referencias, índices y Notion.
