# Backlog 03 - Productos crediticios

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f8123a76dc9e1b2271bac
- Rama: `feat/backlog-03-productos-crediticios`
- PR: https://github.com/jata-imk/kronik/pull/14
- ADR relacionados: ADR 0001, ADR 0006

## Objetivo

Implementar crédito simple V1 configurable, versionado, simulable y protegido contra cambios históricos.

## Estado actual

- Estado: entregado
- Última actualización: 2026-08-12
- Último punto estable: implementación completa verificada en backend, frontend, build y navegador real.

## Decisiones pendientes

- Ninguna bloqueante. IVA, comisiones financiadas, inhábiles y crédito revolvente están fuera de V1.

## Evidencia

- Pruebas: Laravel 118/118 (617 aserciones); Vitest 27/27; E2E específico 2/2; ejemplo Banxico 57.4%; build Vite, Pint acotado y `git diff --check` exitosos.
- Revisión manual: catálogo, alta, editor, versiones y simulador revisados con Chrome/Playwright a 1521 px y 390 px. Se corrigieron etiquetas del simulador. Sin overflow horizontal global ni errores propios en consola.
- Migraciones o seeders: migración aditiva `2026_08_12_120000` aplicada en desarrollo; seeders idempotentes de permisos, menú y cuatro conceptos RECO aplicados. Se creó un producto controlado `CS-DEMO` durante la revisión manual.

## Siguiente paso

Revisión manual del usuario y preparación de commit/PR. En Backlog 04, registrar el snapshot al crear cada solicitud.

## Cierre

- Commit o merge: `100dc06`; PR #14 abierto
- Pendientes diferidos: Backlog 02.5; crédito revolvente V2; IVA; inhábiles; comisiones financiadas.
