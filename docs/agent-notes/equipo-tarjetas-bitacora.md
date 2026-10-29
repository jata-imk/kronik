# Equipo: tarjetas y filtros de bitácora

## Referencias

- Solicitud del usuario y decisiones confirmadas el 2026-10-28.
- Rama: `feat/equipo-tarjetas-bitacora`; base `f5455e6`.
  [PR #55](https://github.com/jata-imk/kronik/pull/55).
- Decisión: [ADR 0031](../explanation/adr-0031-consulta-actividad-equipo-sucursal.md).

## Estado actual

- 2026-10-28: retomado desde `9c8d2d0`; publicado con hooks completos.
  PR abierta, primera revisión medium sin hallazgos; CI y revisión final pendientes.
  Sin integración ni despliegue de este ajuste.
- Acciones horizontales, resumen de roles con personas activas en tarjetas
  cuadradas y tarjeta administrativa primera. Se conserva el catálogo completo
  de roles para administrar/invitar y solo el enlace contextual a bitácora.
- Equipo/Sucursal compartidos en listado y CSV. Permiso del equipo permite todas
  sus sucursales; Super Admin global conserva historia sin equipo. Consulta y
  opciones centralizadas; no cambia equipo/sucursal actuales.
- Sin nuevas acciones auditables: presentación y consulta; no se alteran emisores
  de negocio ni se duplica actividad por filtrar. Matriz y contrato revisados.

## Evidencia

- HEAD `81e38ce`: backend completo aprobado, 528 pruebas, 1 omitida y 41 431
  aserciones. Build aprobado en el pre-push. Backend dirigido: 22 pruebas,
  328 aserciones, también aprobadas en revisión medium.
- Suite completa Vue: 112 pruebas aprobadas en la implementación `9c8d2d0`.
  Pint aprobado; 115 enlaces locales sin faltantes. El revisor no pudo iniciar
  Vue por el sandbox de Vite; se conserva la evidencia local completa independiente.
- Tres E2E Chromium aprobados: tarjetas/acciones/filtros/CSV, contexto/transferencia
  de responsable y acciones de integrantes. Tarjetas cuadradas entre 320 y 1440 px
  con nombres largos. El evento de transferencia se comprueba en Bitácora tras
  retirar el listado de Equipo conforme a lo solicitado.
- Capturas: [escritorio](../assets/equipo-tarjetas-desktop.png),
  [móvil](../assets/equipo-tarjetas-mobile.png),
  [oscuro](../assets/equipo-tarjetas-dark.png),
  [filtros](../assets/bitacora-contextos-desktop.png).
- Confirmación anterior del usuario: despliegue normal y QA satisfactorio en
  VPS de estatus/folios; se actualizan sus notas y Notion. Esta confirmación no
  acredita despliegue ni QA del ajuste nuevo.
- Sin migraciones, variables o seeders productivos nuevos. Solo fixtures E2E
  sintéticos en la base aislada.

## Siguiente paso

Completar pasada final y CI antes de integrar. No hubo correcciones necesarias
en la primera revisión medium. Registrar el merge y dejar pendiente
únicamente el despliegue/QA de esta mejora nueva.

Logs locales: `storage/framework/testing/equipo-push.log`, `equipo-vue.log`,
`equipo-build-final.log` y `equipo-e2e-responsive.log`. La descripción preparada
está en `storage/framework/testing/pr-equipo-bitacora.md`. El QA anterior de
Empresa/Folios ya está registrado en sus dos páginas Notion y en las notas del
commit; no reabrirlo ni solicitar otra confirmación.
