# Estatus operativo de empresa

## Entrega integrada

- [Tarea Notion](https://app.notion.com/p/3fe61db7db7f81f8a941ffe166235c8b).
- Implementación `6e57010`, integrada el 2026-10-27 mediante [PR #52](https://github.com/jata-imk/kronik/pull/52), merge `234ec3d`; base `bc28e5e`.
- Corrección posterior de auditoría `f0c3459`, integrada el 2026-10-28 mediante [PR #53](https://github.com/jata-imk/kronik/pull/53), merge `2cc3f4a`.
- Decisiones pendientes: ninguna. Sin despliegue ni QA posterior del usuario acreditados.
- Contrato: [estatus operativo](../reference/estatus-operativo-empresa.md); decisión: [ADR 0029](../explanation/adr-0029-estatus-operativo-empresa.md).

## Resultado

Borrador permite configuración, captura y simulación. Suspendida bloquea toda
originación y conserva mantenimiento, historia, cancelaciones y atención de
cartera. Los documentos finales exigen razón social, RFC válido y correo incluso
si la plantilla no los utiliza. Super Admin respeta las invariantes operativas.
Reporteo SIC y monitoreo/reporte PLD de cartera previa quedan permitidos en
Suspendida para módulos futuros; no se implementan envíos en esta entrega.

Backend/API y workers aplican la política central; el banner y los controles
explican el motivo. Auditoría distingue sistema e iniciador y conserva el
contexto original. Estado terminal del PDF bloqueado y evento se confirman juntos;
un fallo transitorio revierte ambos y el reintento no duplica actividad.

## Evidencia final

- `6e57010`: 497 pruebas backend aprobadas, una omitida, 41057 aserciones; 100 Vue, build, Pint y E2E Chromium aprobados.
- CI inicial de PR: `40454032328`/`40626605579`; main `40627021433`, aprobado.
- Corrección final `f0c3459`: hook con 519 backend aprobadas, una omitida, 41261 aserciones; build/Pint aprobados. [CI de PR #53](https://github.com/jata-imk/kronik/actions/runs/40801112168) aprobado, con 103 Vue y concurrencia MariaDB.
- [CI del merge final](https://github.com/jata-imk/kronik/actions/runs/40801550026) aprobado sobre `2cc3f4a`.
- Review medium corrigió sujeto UUID de auditoría, admisión atómica de PDF y persistencia atómica de bloqueo/evento. Regresiones de fallo/reintento, actor/sujeto y contexto original aprobadas. Revisión acumulada final contra `bc28e5e` sin hallazgos accionables.
- Captura: [empresa suspendida](../assets/empresa-suspendida.png). Matriz única y documentación actualizadas.

## Pendientes reales

Aplicar migración de contexto documental cuando se programe despliegue; conserva
historia desconocida sin inferir preferencias actuales. Verificar después el
entorno desplegado. Sin variables nuevas ni seeders productivos; no habilitar
proveedores productivos ni dinero real. Seguimiento de la entrega conjunta en
[folios y ayuda](folios-ayuda-contextual.md).
