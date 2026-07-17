---
type: adr
area: explanation
status: accepted
---

# ADR 0001: `teams` como financiera operativa

## Contexto

Kronik usa Laravel Jetstream con equipos y Spatie Permission con `team_id`. El Backlog 01 necesita configurar la operación de cada financiera sin cambios de código.

## Decisión

`teams` representa la financiera o tenant operativo principal. La configuración de empresa vive en una relación 1:1 con `teams` mediante `empresa_configuraciones`.

Las sucursales no se modelan dentro de `teams` en este corte. Cuando se requiera jerarquía operativa, debe agregarse una entidad propia de sucursales relacionada con la financiera.

## Consecuencias

- Los permisos Spatie siguen resolviéndose por el equipo actual.
- La configuración legal, operativa e integraciones se consulta desde `currentTeam`.
- Las credenciales externas no se guardan en la tabla; se guardan referencias a variables de entorno.
- Backlog 01 puede avanzar sin redefinir Jetstream ni romper equipos existentes.

## Evidencia Esperada

- Pruebas Pest para acceso, actualización, validación y auditoría.
- Screenshot o e2e Playwright del flujo administrativo cuando la UI esté disponible.
- Documentación Diataxis actualizada antes del commit.
