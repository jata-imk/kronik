# ADR 0036: Alcance explícito de consulta y sucursal operativa

## Estado

Aceptado el 2026-11-01; implementación integrada en `main` mediante
[PR #66](https://github.com/jata-imk/kronik/pull/66), merge `ae4f398`.
Despliegue y QA del usuario pendientes.

## Contexto

La revisión de solicitudes y SIC reprodujo bandejas sin alcance operativo por
defecto. Clientes sí utilizaba la activa, aunque filtraba/paginaba en Vue.
La lectura institucional entre sucursales ya estaba concedida; limitarla a
asignaciones cambiaría autorización. Equipo y sucursal son contextos independientes.

## Decisión

Separar autorización, alcance de consulta y contexto operativo. Las bandejas
operativas abren en la activa; consultar otra o consolidar es explícito y conserva
los permisos actuales. Las policies y servicios siguen autorizando detalles y
acciones. No hay scopes globales de modelos, permisos nuevos ni backfill histórico.
La conciliación SIC se realiza en la sucursal original del intento, decisión
confirmada por el usuario al revisar el plan. Todos los listados usan
consulta y paginación en servidor, manteniendo globales Productos y los demás
recursos institucionales. El catálogo finito editable de permisos y las tablas
calculadas de simulación no constituyen bandejas de registros persistidos.

## Consecuencias

Clientes cambia su prop de colección a paginador; la API v1 conserva compatibilidad
sin parámetros. Las consultas institucionales y el acceso directo autorizado no
se eliminan por cambiar de sucursal. Los eventos conservan el contexto del recurso
y los procesos automáticos el del iniciador capturado. ADR 0005 y ADR 0031 siguen
vigentes; no se reinterpreta el equipo como propietario de la cartera.

Contrato: [contexto de sucursal y consultas](../reference/contexto-sucursal-consultas.md).
