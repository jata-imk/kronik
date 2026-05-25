# TODO 01: Configuración de Empresa

## Objetivo

Permitir que cada financiera configure su operación sin cambios de código.

## Alcance

- Perfil legal: razón social, RFC, régimen, domicilio fiscal, teléfonos, correos y logotipo.
- Operación: moneda, zona horaria, horarios, sucursales, equipos, folios y consecutivos.
- Parámetros: días inhábiles, reglas de cobranza, formatos de contrato, cuentas bancarias y contactos.
- Integraciones: llaves de Círculo de Crédito, correo, geocoding y servicios documentales.

## TODO

- [ ] Definir si `teams` representa financiera, sucursal o ambas mediante jerarquía.
- [ ] Diseñar tabla de configuración por empresa/equipo.
- [ ] Crear CRUD administrativo de perfil de financiera.
- [ ] Agregar configuración segura para credenciales externas.
- [ ] Agregar permisos Spatie para administrar configuración.
- [ ] Auditar cambios críticos con Activitylog.
- [ ] Documentar datos obligatorios antes de activar una financiera.
