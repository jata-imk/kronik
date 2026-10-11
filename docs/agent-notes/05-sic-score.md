# Backlog 05 - SIC y score

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f8150ba15f9c27562a5f8
- Rama actual: `feat/sic-qa-anticipos-liquidacion`, base main b988273.
- PR histórico P1 integrado: https://github.com/jata-imk/kronik/pull/16.
- ADR relacionados: 0011 (legado), 0022 (SIC QA).

## Objetivo

Recuperar tres servicios sandbox, histórico cifrado y evidencia trazable para
originación QA. Contratación y habilitación productivas corresponden a otra entrega.

## Estado actual

- Actualización 10/10/2026: P7 QA implementado en `feat/sic-qa-anticipos-liquidacion`,
  base main b988273; pendiente de revisión/integración. ADR 0022 y guía `qa-sic.md`.
- Tres pruebas sandbox explícitas con casos oficiales: HTTP 200, sin evidencia
  insertada. E2E de tres servicios y MCP de reporte/temas/móvil aprobados.
- Migraciones aplicadas en QA tras respaldo cifrado verificado (67 tablas).
  Dos históricos archivados, verificados y retirados de texto plano, IDs intactos.
  Flag SIC QA local activo; TLS verificado con CA privado local. VPS intacta.
- Pruebas de consentimiento/revisión, incertidumbre/conciliación, respuesta tardía,
  firma, cifrado y resúmenes. No acreditar concurrencia simultánea multiconexión.
- Regresión final: 372 backend / 39,755 aserciones / 1 omitida; 90 frontend;
  dos E2E completos, build y Pint aprobados. Capturas sintéticas en la guía QA.
- La evidencia siguiente corresponde a P1; producción permanece cerrada.

## Seguimiento histórico P1

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

Revisar PR y CI MariaDB; probar con operadores QA y políticas de servicios/sin score.
Contrato/API productivos permanecen como entrega posterior, según Notion.

## Cierre histórico P1

- P1 integrado (PR #16). P7 sandbox implementado ahora; pendiente de revisión/integración.
- El adaptador productivo contratado sigue diferido. Cifrado y normalización QA
  están incluidos en P7.
