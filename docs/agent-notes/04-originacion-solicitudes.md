# Backlog 04 - Originación y solicitudes

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f81948d4ae8fa9118ac1d
- Coordinación: https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666
- Rama: feat/originacion-incrementos
- PR: [P0 #17](https://github.com/jata-imk/kronik/pull/17), [P1 #16](https://github.com/jata-imk/kronik/pull/16), [P2 #18](https://github.com/jata-imk/kronik/pull/18).
- ADR relacionados: 0010–0013, 0005–0009

## Objetivo

Ejecutar los incrementos aprobados del recorrido de solicitud a primer pago.
El alcance completo y las decisiones de producto permanecen en Notion.

## Estado actual

- Estado: en progreso
- Última actualización: 2026-09-20
- Último punto estable: main 002ca0d (PR #15), igual a remoto al iniciar.
- P0: Notion ampliado y ADR registrados (6359c99).
- P1: SIC saneado/navegación (c29af31), rama fix/sic-navegacion-segura.
- P2: borrador/revisión/asignación implementados y verificados (`c4b5770` más seguimiento); PR #18 borrador.
- P3–P9 pendientes. No aprobación ni operación monetaria habilitada.
- Se preserva `.playwright-mcp/` local ajeno.

## Decisiones pendientes

- Habilitación real exige políticas financieras, impuestos, contratos y perfil
  de cumplimiento validados; lista completa y alcance manual en Notion.

## Evidencia

- Pruebas: suite backend previa 180 aprobadas / 1089 aserciones / 1 omitida; seguimiento
  de solicitudes 11 pruebas / 102 aserciones aprobado; suite final se repite en pre-push.
- Frontend: 54 pruebas aprobadas. Build aprobado.
- E2E: 9 aprobados (solicitud, SIC, autenticación y administración).
- QA encontró y corrigió etiquetas accesibles, sincronización del plazo y fallo
  de orientación en Select de PrimeVue 4.3.1; guard temporal documentado y probado.
- Se agrega regresión de fecha de hoy en zona empresarial; CI remoto en curso.
- GitHub reportó alertas de dependencias en main al publicar; no se remediaron en estos incrementos.
- Revisión manual: fuentes Notion y arquitectura existente inspeccionadas.
- Migraciones o seeders: 2026_09_20_000000_create_solicitudes_tables; ModulesAndPermissionsSeeder
  agrega cuatro permisos, sin asignarlos a roles. Solo se ejecutó en BD de pruebas aisladas.

## Siguiente paso

Revisar PR de P2; después P3 evaluación manual, cumplimiento y resolución.

## Cierre

- Commit o merge: commits publicados; PR apilados 17 → 16 → 18, sin merge.
- Pendientes diferidos: 02.5 sigue diferido; producción SIC en P7.
