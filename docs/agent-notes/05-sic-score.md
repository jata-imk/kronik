# Backlog 05 - SIC y score

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f8150ba15f9c27562a5f8
- Rama P1: fix/sic-navegacion-segura; seguimiento en feat/originacion-incrementos.
- PR: https://github.com/jata-imk/kronik/pull/16 (depende de #17).
- ADR relacionados: 0011

## Objetivo

Sanear el legado antes de implementar el adaptador productivo contratado.

## Estado actual

- Estado: en progreso
- Última actualización: 2026-09-20
- P1: servicios heredados cerrados; historial sin payload/demos; permisos y
  navegación separados; protección contra eliminación de evidencia SIC.
- P7 sigue pendiente; no se hicieron consultas externas.

## Decisiones pendientes

- Contrato/API, tarifas, retención, reutilización y textos de consentimiento.

## Evidencia

- Pruebas: SicSafetyTest + MenubarServiceTest: 11 pruebas, 114 aserciones.
- Frontend: AppMenu.test.js: 2 pruebas.
- Regresión: 170 pruebas backend, 990 aserciones, 1 omitida; 49 frontend; build aprobado.
- Navegador: 7 E2E autenticación/administración aprobados; SIC aprobado tras
  corregir una aserción que contaba formularios ajenos del layout.
- Migraciones o seeders: ninguno.

## Siguiente paso

Verificar regresión general y revisar P1; P7 depende de validación del proveedor.

## Cierre

- Commit o merge: pendiente.
- Pendientes diferidos: adaptador, cifrado/migración de históricos y normalización.
