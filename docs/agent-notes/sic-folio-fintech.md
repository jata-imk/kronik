# Corrección SIC — folio de escenario Fintech

## Referencias

- Notion: [manual visual QA](https://app.notion.com/p/3fe61db7db7f812d878ccccc1e90d651).
- Rama: `fix/sic-folio-fintech`, base main79d155d (PR43).
- PR: pendiente de crear.
- Decisión: [ADR0022, aclaración Fintech](../explanation/adr-0022-sic-qa-evidencia.md).

## Objetivo

Resolver SIC-FOL-001 dentro de la ampliación SIC autorizada por José. El sandbox
Fintech necesita su folio de escenario, separado del número interno de Kronik.

## Estado actual

- En progreso,25/10/2026. PR42/43 desplegadas según confirmación de José.
- En QA, consultas3/5 HTTP400 por domicilio; después de PR43,6/7 HTTP404 por
  persona sin coincidencia. Consulta8 Fintech del cliente sintético10 también404.
- Laboratorio: caso oficial Fintech200; variante con RFC válido200; mismo caso
  con folio8 y00000008 da404; folio20210304 con municipio del catálogo da200.
  No se guardaron registros ni evidencia de aprobación en esas comprobaciones.
- Código usa el folio de escenario configurado y conserva la persona de la
  petición, ID interno e idempotencia. Sin folio configurado rechaza en español
  antes de conservar un intento o enviar. UI explica el alcance de simulación.
- No modifica validación RFC, consentimiento, política SIC ni aprobación.
- Empresa moral sintética del manual activa: restaurar original al terminar/pausar.
- Sin migraciones, seeders ni variables nuevas; reconstruir caché y reiniciar
  workers al desplegar. Integración/despliegue de este ajuste pendientes.

## Evidencia

- Regresión inicial reproduce el folio incorrecto y ausencia de validación.
- Local aislado:428 backend aprobadas,1 omitida,40484 aserciones. SIC dirigido:
  32 pruebas/209 aserciones. Pint aprobado y build aprobado.
- E2E `sic-qa.spec.js` aprobado, incluidos los tres servicios, aviso de escenario
  Fintech y reporte gráfico. No usa proveedor externo. CI pendiente.
- Cliente10 es derivado del caso oficial: nombre Juan Juan Sesentaydos Prueba,
  RFC con dígito corregido SEPJ650809JGA, consentimiento5. No tiene solicitud ni
  movimientos. Las consultas se crearán nuevas por UI después del despliegue.

## Siguiente paso

Completar pruebas y PR, integrar y solicitar despliegue manual a José. Validar
Fintech desde UI con el cliente10 y continuar un caso de equipo con SIC válido,
siempre ligado a su propia identidad y revisión, sin reutilizar otro cliente.

## Cierre

- Commit/merge: pendiente.
- Diferidos: producción y otros hallazgos del manual.
