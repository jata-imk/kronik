# TODO 01: Configuración de Empresa

## Objetivo

Permitir que cada financiera configure su operación sin cambios de código.

## Alcance

- Perfil legal: razón social, RFC, régimen, domicilio fiscal, teléfonos, correos y logotipo.
- Operación: moneda, zona horaria, horarios, sucursales, equipos, folios y consecutivos.
- Parámetros: días inhábiles, reglas de cobranza, formatos de contrato, cuentas bancarias y contactos.
- Integraciones: llaves de Círculo de Crédito, correo, geocoding y servicios documentales.

## TODO

- [x] Definir si `teams` representa financiera, sucursal o ambas mediante jerarquía.
- [x] Diseñar tabla de configuración por empresa/equipo.
- [x] Crear CRUD administrativo de perfil de financiera.
- [x] Agregar configuración segura para credenciales externas.
- [x] Agregar permisos Spatie para administrar configuración.
- [x] Auditar cambios críticos con Activitylog.
- [x] Documentar datos obligatorios antes de activar una financiera.

## Decisión

`teams` representa la financiera o tenant operativo principal. La configuración vive en `empresa_configuraciones` como relación 1:1 con `teams`. Las sucursales quedan fuera de este corte y deben modelarse como entidad propia cuando el backlog lo requiera.

Ver [ADR 0001](../explanation/adr-0001-teams-como-financiera.md).

## Pruebas

- Pest feature test para vista de configuración.
- Pest feature test para actualización.
- Pest feature test para validación de activación.
- Validación Playwright/screenshot requerida antes de cerrar visualmente el flujo.

## Documentación

- [Configuración de empresa](../reference/configuracion-empresa.md)
- [Configurar empresa](../how-to/configurar-empresa.md)
- [Flujo de tareas, pruebas y PR](../reference/flujo-tareas-pruebas-pr.md)
