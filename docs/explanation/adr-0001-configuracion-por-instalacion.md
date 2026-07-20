---
type: adr
area: architecture
status: accepted
---

# ADR 0001: Configuracion por Instalacion

## Contexto

El sistema se despliega como una app por financiera. Si otra financiera contrata el sistema, se prepara otra instancia con su propia base de datos, variables de entorno y configuracion.

## Decision

La financiera se modela como configuracion global singleton en `empresa_configuraciones`.

`teams` queda reservado para departamentos, grupos de trabajo y contexto de permisos. Las sucursales se modelan en `sucursales` porque necesitan domicilio operativo, horarios y folios propios.

## Consecuencias

- No se agrega `team_id` a `empresa_configuraciones`.
- Los modulos futuros deben usar `sucursal_id` cuando necesiten identificar la unidad operativa.
- Los permisos siguen usando Spatie con teams para roles por contexto organizacional, no para separar financieras.
- Se evita complejidad multi-financiera dentro de una misma instancia.
