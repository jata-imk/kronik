# Corrección SIC — folio de escenario Fintech

## Referencias

- Notion: [manual visual QA](https://app.notion.com/p/3fe61db7db7f812d878ccccc1e90d651).
- Implementación: `fix/sic-folio-fintech`, commit9983bf4, base main79d155d.
- PR: [#44](https://github.com/jata-imk/kronik/pull/44), CI aprobada,
  integrada en main`a027700a5841a9e76551f788d5c3f4a757f8ffb2`.
- Seguimiento documental: `docs/sic-validacion-manual`.
- Decisión: [ADR0022, aclaración Fintech](../explanation/adr-0022-sic-qa-evidencia.md).

## Objetivo

Resolver SIC-FOL-001 dentro de la ampliación SIC autorizada por José. El sandbox
Fintech necesita su folio de escenario, separado del número interno de Kronik.

## Estado actual

- Entregado y validado en QA el25/10/2026. José confirmó despliegue de PR44
  con caché/frontend/workers; commit efectivo del servidor no informado.
- En QA, consultas3/5 HTTP400 por domicilio; después de PR43,6/7 HTTP404 por
  persona sin coincidencia. Consulta8 Fintech del cliente sintético10 también404.
- Laboratorio: caso oficial Fintech200; variante con RFC válido200; mismo caso
  con folio8 y00000008 da404; folio20210304 con municipio del catálogo da200.
  No se guardaron registros ni evidencia de aprobación en esas comprobaciones.
- Código usa el folio de escenario configurado y conserva la persona de la
  petición, ID interno e idempotencia. Sin folio configurado rechaza en español
  antes de conservar un intento o enviar. UI explica el alcance de simulación.
- No modifica validación RFC, consentimiento, política SIC ni aprobación.
- Empresa original del manual restaurada por interfaz el25/10 y comparada:
  cero diferencias salvo marcas de tiempo. Admin restaurado a equipo1.
- Sin migraciones, seeders ni variables nuevas; reconstruir caché y reiniciar
  workers al desplegar. Integración/despliegue confirmados; comportamiento validado.

## Evidencia

- Regresión inicial reproduce el folio incorrecto y ausencia de validación.
- Local aislado:428 backend aprobadas,1 omitida,40484 aserciones. SIC dirigido:
  32 pruebas/209 aserciones. Pint aprobado y build aprobado.
- E2E `sic-qa.spec.js` aprobado, incluidos los tres servicios, aviso de escenario
  Fintech y reporte gráfico. No usa proveedor externo. CI aprobada en PR44.
- Cliente10 es derivado del caso oficial: nombre Juan Juan Sesentaydos Prueba,
  RFC con dígito corregido SEPJ650809JGA, consentimiento5. No tiene solicitud ni
  movimientos. Su evidencia no se usó para aprobar a otro cliente.
- Tras el despliegue, consulta9 del cliente8 Javier, consentimiento4 y SOL-6
  revisión2 (ID8), creada por Elena en UI QA: Completada, folio12345681,
  score404 sintético, modelo no informado. **404 es score, no error HTTP**;
  no se infiere riesgo real ni un umbral de aprobación.
- Requisito SIC satisfecho sin cambiar política. Elena/Marina emitieron dictámenes
  actuales separados; Hugo aprobó tras20 requisitos. Contrato2, desembolso CR-2,
  pago/anticipo/liquidación por Lucía y reversos por Hugo. Cierre final:
  capital/exigible/vencido0, calendario5, recibos7–12 conservados.

## Siguiente paso

Conservar la evidencia. No repetir envíos ni movimientos: CR-1 y CR-2 ya están
liquidados. Mora/vencidos requieren otro caso activo que alcance sus fechas reales.

## Cierre

- Commit/merge:9983bf4, PR44, maina027700; despliegue reportado por José y
  consulta9 validada por interfaz el25/10/2026.
- Diferidos: producción y otros hallazgos del manual.
