# Backlog 06 - PLD y cumplimiento

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f8157b516e7f47aabb241
- Rama: feat/solicitudes-resolucion
- PR: [#19](https://github.com/jata-imk/kronik/pull/19), borrador sobre #18.
- ADR relacionados: 0010 y 0013.

## Objetivo

Conectar revisión humana reservada al expediente de solicitud. Alcance completo en Notion.

## Estado actual

- Estado: en progreso
- Última actualización: 2026-09-20
- Dictámenes cifrados/inmutables por revisión, lectura/escritura diferenciadas.
- Bandeja de Cumplimiento conduce al mismo detalle. No consulta listas automáticamente.
- Reenvío conserva dictámenes anteriores como históricos, no aplicables a nueva revisión.
- No aprobación, certificación legal, notificación externa ni operación monetaria.

## Decisiones pendientes

- Perfil/metodología/fuentes aplicables del operador y requisitos para habilitación real.
- Completar configuración explícita de aprobación; no resolver mediante defaults permisivos.

## Evidencia

- Pruebas: 18 de solicitudes / 250 aserciones; suite backend 188 / 1240; frontend 56.
- E2E: 9 aprobados, incluyendo evaluación/PLD y obsolescencia tras nueva revisión.
- Revisión manual: Notion y ADR 0013 cotejados; ver referencia de solicitudes.
- Migraciones: solicitud_resoluciones y solicitud_dictamenes, aditivas, solo BD aisladas.
- Seeder: nuevos permisos sin asignación automática de roles.

## Siguiente paso

Completar gates de aprobación y requisitos, conservando notas reservadas fuera de logs y props operativos.

## Cierre

- Commit o merge: c8cff6f y 2c401ad; no integrado en main.
- Pendientes diferidos: listas/proveedor, seguimiento periódico, avisos y perfiles regulatorios específicos.
