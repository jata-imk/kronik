# ADR 0019: crédito y desembolso único manual QA

- Estado: aceptado para P5 QA.
- Fecha: 2026-09-29.
- Precedentes: ADR 0010, 0012, 0018; iniciativa 04–07 de Notion.

## Decisión

Crear crédito y desembolso en una única transacción desde una solicitud formalizada.
No ejecutar transferencias bancarias. El registro es exclusivamente sintético QA,
con puerta `ORIGINACION_DESEMBOLSOS_QA_HABILITADOS=false` por defecto.

Conservar conceptos distintos: `Credito` y condiciones cifradas, `CreditoCronograma`
versión 1 copiado sin recalcular desde la tabla firmada, `CreditoDesembolso` con
fecha efectiva/referencia/actor y `CreditoMovimiento` de capital inicial. El capital
incluye cargos financiados; no equivale al efectivo transferido ni a la suma de
cuotas futuras. Los cargos separados no se registran pagados en este incremento.
La proyección fiscal permanece proyección, sin determinación fiscal operativa.

Registrar solo en fecha empresarial de hoy, igual a la tabla firmada. No habilitar
desembolsos pasados por analogía con pagos históricos. Fecha distinta exige devolver,
revisar, aprobar y formalizar nuevamente, antes del desembolso. Revalidar aprobación,
evidencia, política, vigencia, sucursal y hashes de formalización/original/firma.

Bloqueo solicitud → cliente → producto; crédito único por solicitud/formalización,
desembolso único por crédito, UUID y hash del payload. Reintento idéntico devuelve el
crédito conservado, sin repetir movimientos. Referencia completa de transferencia
sintética única en la instalación (trim y comparación sin distinguir mayúsculas),
cifrada; hash de búsqueda separado. No interpretar referencias como confirmación bancaria.

Permisos `read creditos` y `disburse creditos` separados, sin asignación implícita.
Lectura institucional con permisos del equipo activo, conforme a Solicitudes;
mutaciones solo en sucursal activa responsable, incluso Super Admin. Documentos
requieren sus permisos. Navegación Créditos y accesos contextuales sin publicar Pagos
ni Cobranza antes de implementarlos. No exponer snapshots cifrados o referencias en
listados/logs técnicos. Sin borrado/edición monetaria ni acciones masivas.

La solicitud pasa a desembolsada, ya no admite devolución/cancelación y sale de
Mi trabajo por defecto. La historia sigue accesible. Antes de P6 no se expone un
saldo devengado ni se ofrece un falso registro de pago. Los reversos se entregan
en el incremento correspondiente; no corregir eliminando un desembolso.

## Verificación y límites

Pruebas de duplicados, permisos, fechas, importes, evidencia alterada, cifrado,
inmutabilidad, rollback y transiciones. MariaDB en CI, SQLite local aislado y E2E
con usuarios normales. La restricción única es defensa adicional al bloqueo.
Migración aditiva; rollback rechazado cuando existan créditos.

P6 conserva los acuerdos de devengo actual/360, orden de aplicación, reversos y
pagos históricos sin movimientos posteriores. Notion aún requiere precisar
aplicación de impuestos en parcialidades y coexistencia/gracia antes de habilitar
esos casos. No resolver esas decisiones silenciosamente durante P5.
