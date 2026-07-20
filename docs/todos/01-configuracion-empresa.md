# TODO 01: Configuracion de Empresa

## Objetivo

Permitir que la financiera configure su operacion sin cambios de codigo. La app no sera multi-financiera: cada instalacion corresponde a una sola financiera.

## Estado Real

Terminado para el primer corte funcional con la decision local vigente: `empresa_configuraciones` es singleton por instalacion y `teams` representa departamentos/grupos de trabajo, no la financiera.

## Alcance

- Perfil legal: razon social, RFC, regimen, domicilio fiscal, telefonos, correos y logotipo.
- Operacion: moneda, zona horaria, horarios, sucursales, folios y consecutivos.
- Parametros: dias inhabiles, reglas de cobranza, formatos de contrato, cuentas bancarias y contactos.
- Integraciones: referencias seguras a llaves de Circulo de Credito, correo, geocoding y servicios documentales.
- Equipos: `teams` se reserva para departamentos, grupos de trabajo y permisos.

## Implementado

- [x] Definir que `teams` representa departamentos/grupos de trabajo, no la financiera.
- [x] Disenar configuracion global unica por instalacion.
- [x] Crear CRUD administrativo de perfil de financiera.
- [x] Crear entidad inicial de sucursales.
- [x] Agregar configuracion segura para credenciales externas.
- [x] Agregar permisos Spatie para administrar configuracion y sucursales.
- [x] Auditar cambios criticos con Activitylog.
- [x] Documentar datos obligatorios antes de activar una financiera.
- [x] Agregar seeders demo idempotentes para empresa y sucursal.
- [x] Agregar pruebas feature de configuracion de empresa y sucursales.

## Diferido

- Vincular usuarios, clientes, solicitudes, creditos y pagos con `sucursal_id` cuando esos modulos existan y el modelo de asignacion por sucursal este definido.
