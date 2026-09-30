# Backlog 07 - Amortización, pagos y cobranza

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f8187affec6f73e92e952
- Iniciativa: https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666
- Rama actual: feat/creditos-pagos, base main c005e99 (PR #28 integrado).
- PR: https://github.com/jata-imk/kronik/pull/28; implementación 3401a1f.
- ADR: 0012, 0018, 0019, 0020.

## Objetivo

P6 conecta crédito con pagos, distribución y reversos manuales QA. El alcance
completo y decisiones permanecen en Notion; no duplicar aquí la especificación.

## Estado actual

- Estado: en progreso.
- Última actualización: 2026-09-30.
- Último punto integrado: P5, PR #28, main c005e99 y CI posterior aprobado.
- P6 local sin commit/PR todavía: política de atraso nullable/versionada,
  editor/consulta y anexo, reparto proporcional decimal, motor de devengo,
  persistencia cifrada/inmutable, previa, recibo, historial, saldos y reverso.
  Nuevos permisos pay/reverse payments creditos; bandera de pagos cerrada.
- Crédito, cronograma v1 congelado, desembolso y movimiento inicial separados.
  Navegación/listado/detalle y acceso desde solicitud/contrato.
- Migración aditiva 2026_09_29_100000; seeder de permisos read/disburse creditos.
  ORIGINACION_DESEMBOLSOS_QA_HABILITADOS cerrada por defecto.
- No se modificó .env, VPS ni base predeterminada. .playwright-mcp/ ajeno preservado.

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

- 273 backend / 2270 aserciones / 1 omitida preexistente; 82 frontend.
- E2E dual con usuarios normales hasta desembolso y consulta de crédito aprobado.
  Corrección de nombre accesible del enlace; capturas escritorio/móvil revisadas.
- Build, Pint y diff-check aprobados. Guion docs/how-to/qa-desembolso.md.
- Solo bases aisladas. Backend y E2E secuenciales para evitar caché compartida.

## Siguiente paso

Cerrar E2E de pagos/productos, inspeccionar capturas, publicar PR y verificar CI
MariaDB antes de integrar. No reabrir acuerdos funcionales.
P6: backend completo 321 aprobadas / 39385 aserciones / 1 omitida preexistente;
Vue 88 aprobadas; build y Pint aprobados. Fixtures compartidos extraídos a Support
para ejecutar pagos aisladamente. Comparación por fecha, no timestamp, permite
segundo pago/reverso del mismo día. E2E dual completo hasta pago/reverso aprobado;
tres pruebas de productos aprobadas en fixture separado sin escenario dual.
Corregidos nombres accesibles del enlace/selectores y captura automatizada por
teclado del importe monetario. Timeout PDF preexistente no se reprodujo al repetir
sin suites concurrentes. Capturas de pago/recibo escritorio y móvil inspeccionadas.
ADR 0020 y guía docs/how-to/qa-pagos.md. Sin concurrencia multiconexión acreditada.

## Cierre

- No cerrar todo Backlog 07 ni iniciativa 04–07 al integrar P6; quedan extensiones.
- SIC real, anticipos/liquidación, cobranza ampliada y 02.5 siguen diferidos.
