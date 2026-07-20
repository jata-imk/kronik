# TODO 01: Configuración de Empresa

## Objetivo

Permitir que la financiera configure su operación sin cambios de código. La app no será multi-financiera: cada instalación corresponde a una sola financiera.

## Alcance

- Perfil legal: razón social, RFC, régimen, domicilio fiscal, teléfonos, correos y logotipo.
- Operación: moneda, zona horaria, horarios, sucursales, folios y consecutivos.
- Parámetros: días inhábiles, reglas de cobranza, formatos de contrato, cuentas bancarias y contactos.
- Integraciones: llaves de Círculo de Crédito, correo, geocoding y servicios documentales.
- Equipos: `teams` se reserva para departamentos, grupos de trabajo y permisos.

## TODO

- [x] Definir que `teams` representa departamentos/grupos de trabajo, no la financiera.
- [x] Diseñar configuración global única por instalación.
- [x] Crear CRUD administrativo de perfil de financiera.
- [x] Crear entidad inicial de sucursales.
- [x] Agregar configuración segura para credenciales externas.
- [x] Agregar permisos Spatie para administrar configuración y sucursales.
- [x] Auditar cambios críticos con Activitylog.
- [ ] Documentar datos obligatorios antes de activar una financiera.
- [ ] Vincular usuarios, clientes, solicitudes, créditos y pagos con `sucursal_id` cuando esos módulos existan.
