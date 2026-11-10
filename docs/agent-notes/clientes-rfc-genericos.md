# Clientes: RFC genéricos

## Referencias

- Notion: https://app.notion.com/p/41461db7db7f8105908cdbc563d1174a
- Rama: `fix/clientes-rfc-genericos`, base `640dc5f`.
- Checkout aislado: `storage/framework/testing/rfc-worktree`.
- PR: pendiente.
- [ADR 0044](../explanation/adr-0044-rfc-genericos-clientes.md) y [contrato](../reference/validacion-rfc.md).

## Estado actual — 2026-11-10

Implementado localmente; integración, despliegue y QA del usuario pendientes.
Ambos genéricos se admiten y repiten para física/moral, alta/edición web/API.
Empresa conserva validación estricta; la UI informa identidad propia pendiente.
Consulta bloquea submit y watchers de normalización. Sin migraciones ni nuevas
variables productivas; `.env` del checkout contiene solo configuración sintética
para pruebas aisladas, nunca credenciales ni la base de desarrollo.

## Evidencia

- Backend focalizado: 39 pruebas / 442 aserciones aprobadas.
- SIC y originación QA con genéricos/corrección: 9 pruebas / 65 aserciones aprobadas.
- Componentes nuevos: 7 pruebas aprobadas.
- Suite backend: 913 aprobadas / 46741 aserciones / 1 omitida (SQLite en memoria).
- Build aprobado; suite frontend y E2E en curso.
- Revisión medium y barrido acumulado: pendientes antes de integrar.

## Pendientes diferidos y siguiente paso

Completar comprobaciones y registrar PR/revisión. La unicidad de RFC propios
queda en [su tarea de Notion](https://app.notion.com/p/41461db7db7f81aea71aefbb4b912571)
por decisión del usuario, sin fecha nueva; evaluar datos existentes antes de definir alcance o migraciones.
No se acredita aceptación por un proveedor real, SIC productivo ni facturación.
