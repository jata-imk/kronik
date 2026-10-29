# Equipo: tarjetas y filtros de bitácora

## Referencias

- Solicitud del usuario y decisiones confirmadas el 2026-10-28.
- Rama: `feat/equipo-tarjetas-bitacora`; base `f5455e6`. PR pendiente.
- Decisión: [ADR 0031](../explanation/adr-0031-consulta-actividad-equipo-sucursal.md).

## Estado actual

- En implementación; sin integración ni despliegue de este ajuste.
- Acciones horizontales, resumen de roles con personas activas en tarjetas
  cuadradas y tarjeta administrativa primera. Se conserva el catálogo completo
  de roles para administrar/invitar y solo el enlace contextual a bitácora.
- Equipo/Sucursal compartidos en listado y CSV. Permiso del equipo permite todas
  sus sucursales; Super Admin global conserva historia sin equipo. Consulta y
  opciones centralizadas; no cambia equipo/sucursal actuales.
- Sin nuevas acciones auditables: presentación y consulta; no se alteran emisores
  de negocio ni se duplica actividad por filtrar. Matriz y contrato revisados.

## Evidencia

- Backend dirigido: 22 pruebas aprobadas, 328 aserciones. Suite completa Vue:
  112 pruebas aprobadas. Build y Pint aprobados; 111 enlaces locales sin faltantes.
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

Publicar la PR con hooks completos; realizar review medium, correcciones y pasada
final antes de integrar con CI aprobado. Registrar el merge y dejar pendiente
únicamente el despliegue/QA de esta mejora nueva.
