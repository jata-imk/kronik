# Backlog 07 - Amortización, pagos y cobranza

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f8187affec6f73e92e952
- Iniciativa: https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666
- Rama de entrega: feat/creditos-pagos, base main c005e99 (PR #28 integrado).
- Último PR integrado: https://github.com/jata-imk/kronik/pull/29; main bce10933.
- ADR: 0012, 0018, 0019, 0020.

## Objetivo

P6 conecta crédito con pagos, distribución y reversos manuales QA. El alcance
completo y decisiones permanecen en Notion; no duplicar aquí la especificación.

## Estado actual

- Estado: P6 entregado; Backlog 07 continúa en progreso.
- Última actualización: 2026-09-30.
- Último punto integrado: P6, PR #29, main bce10933 y CI posterior aprobado.
- Política de atraso nullable/versionada con editor/consulta y anexo; reparto
  proporcional decimal; motor de devengo; pagos cifrados/inmutables con previa,
  recibos, historial y saldos; reverso compensatorio inmutable. Permisos pay y
  reverse payments creditos separados; bandera de pagos QA cerrada por defecto.
- Crédito, cronograma v1 congelado, desembolso y movimiento inicial separados.
  Navegación/listado/detalle y acceso desde solicitud/contrato.
- Migración aditiva 2026_09_29_100000; seeder de permisos read/disburse creditos.
  ORIGINACION_DESEMBOLSOS_QA_HABILITADOS cerrada por defecto.
- No se modificó .env, VPS ni base predeterminada. .playwright-mcp/ ajeno preservado.
- Migraciones P6 aditivas: 2026_09_30_100000 y 2026_09_30_110000. Rollback
  bloqueado cuando existe política o historia de pago.

## Decisiones pendientes

- P5 definido; no admitir fecha retroactiva ni distinta de la firmada.
- Reparto proporcional aprobado y gracia/sustitución aprobadas; registrados en Notion.
- Excepción P6 resuelta: usuario aprobó limitar inicialmente B + sustitución:
  bloquear abonos parciales a cuotas dentro de gracia; admitir cubrirlas completas.
  No afectar A, B + ambos ni fechas fuera de gracia. Motor valida la distribución
  antes de devolver instrucciones de registro y el editor avisa la limitación.
  Compensación trazable de ordinario/impuesto ya pagados queda en backlog futuro,
  no autorizar redistribución automática, cambio de fecha ni edición del recibo.

## Evidencia

- P5: 273 backend / 2270 aserciones / 1 omitida preexistente; 82 frontend.
- E2E dual con usuarios normales hasta desembolso y consulta de crédito aprobado.
  Corrección de nombre accesible del enlace; capturas escritorio/móvil revisadas.
- Build, Pint y diff-check aprobados. Guion docs/how-to/qa-desembolso.md.
- Solo bases aisladas. Backend y E2E secuenciales para evitar caché compartida.
- P6: backend 321 aprobadas / 39,385 aserciones / 1 omitida preexistente; Vue
  88 aprobadas; build, Pint, hook pre-push y CI pre/post merge aprobados.
- Fixtures compartidos extraídos a Support. Comparación por fecha, no timestamp,
  permite segundo pago/reverso del mismo día.
- E2E dual hasta pago/reverso aprobado; tres pruebas de productos con fixture
  separado. Capturas de pago/recibo escritorio y móvil inspeccionadas.
- Corrigidos nombres accesibles y captura automatizada por teclado de InputNumber.
- ADR 0020 y guía docs/how-to/qa-pagos.md. No se probó concurrencia multiconexión.

## Siguiente paso

Siguiente paso operativo: habilitar pagos únicamente en QA, aplicar migraciones y
permisos, luego ejecutar docs/how-to/qa-pagos.md con un crédito QA nuevo. No
reabrir acuerdos funcionales. No afirmar operación real ni concurrencia
multiconexión acreditada.

## Cierre

- PR #29 integrado en main bce10933; sin despliegue en VPS.
- No cerrar todo Backlog 07 ni iniciativa 04–07; quedan extensiones.
- SIC real, anticipos/liquidación, cobranza ampliada y 02.5 siguen diferidos.
