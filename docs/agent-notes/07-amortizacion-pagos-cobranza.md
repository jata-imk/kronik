# Backlog 07 - Amortización, pagos y cobranza

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f8187affec6f73e92e952
- Iniciativa: https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666
- Rama: feat/creditos-desembolso, base main 78f3cd5 (PR #27 integrado).
- PR: https://github.com/jata-imk/kronik/pull/28; implementación 3401a1f.
- ADR: 0012, 0018, 0019.

## Objetivo

P5 conecta formalización con crédito y desembolso único manual QA. El alcance
completo y decisiones permanecen en Notion; no duplicar aquí la especificación.

## Estado actual

- Estado: en progreso.
- Última actualización: 2026-09-29.
- Último punto verificado localmente: implementación P5 3401a1f, suites y hook aprobados.
- Crédito, cronograma v1 congelado, desembolso y movimiento inicial separados.
  Navegación/listado/detalle y acceso desde solicitud/contrato. No pagos todavía.
- Migración aditiva 2026_09_29_100000; seeder de permisos read/disburse creditos.
  ORIGINACION_DESEMBOLSOS_QA_HABILITADOS cerrada por defecto.
- No se modificó .env, VPS ni base predeterminada. .playwright-mcp/ ajeno preservado.

## Decisiones pendientes

- P5 definido; no admitir fecha retroactiva ni distinta de la firmada.
- Antes de cálculos P6: precisar impuestos/parcialidades y combinación gracia y
  sustitución de ordinario, según límites expresos de Notion. No asumir prioridad
  fiscal ni causación. Pagos pasados solo sin movimientos posteriores, excedentes
  fuera del sistema y reversos compensatorios ya acordados.

## Evidencia

- 273 backend / 2270 aserciones / 1 omitida preexistente; 82 frontend.
- E2E dual con usuarios normales hasta desembolso y consulta de crédito aprobado.
  Corrección de nombre accesible del enlace; capturas escritorio/móvil revisadas.
- Build, Pint y diff-check aprobados. Guion docs/how-to/qa-desembolso.md.
- Solo bases aisladas. Backend y E2E secuenciales para evitar caché compartida.

## Siguiente paso

Verificar estado remoto del PR #28 y su CI antes de retomar; integración autorizada
solo con CI aprobado. Resultado remoto de cierre se registra en Notion.
Después P6, resolviendo únicamente los detalles de cálculo realmente pendientes;
consultas de producto enviadas sobre impuesto proporcional y gracia/sustitución.

## Cierre

- No cerrar Backlog 07 ni iniciativa 04–07 al integrar P5; pagos no implementados.
- SIC real, anticipos/liquidación, cobranza ampliada y 02.5 siguen diferidos.
