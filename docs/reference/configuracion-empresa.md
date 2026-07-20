---
type: reference
area: configuration
status: active
---

# Configuracion de Empresa

## Modelo

La configuracion de empresa se guarda en `empresa_configuraciones` como registro unico por instalacion.

Relacion:

- `empresa_configuraciones.singleton_key = default` identifica el registro global.
- `teams` no representa financieras ni sucursales; se usa para departamentos, grupos de trabajo y contexto de permisos.
- `sucursales` guarda unidades operativas con domicilio, horarios y folios propios.

Campos principales:

- Perfil legal: `razon_social`, `nombre_comercial`, `rfc`, `regimen_fiscal`, `domicilio_fiscal`.
- Contacto: `email`, `telefono`, `sitio_web`, `logotipo_path`.
- Operacion: `moneda`, `zona_horaria`, `pais_base`, `estatus`.
- Parametros: `parametros_operativos`.
- Integraciones: `integraciones`.

## Activacion

Para cambiar `estatus` a `activa` se requieren:

- Razon social.
- RFC valido.
- Regimen fiscal.
- Correo operativo.
- Calle, codigo postal y estado del domicilio fiscal.

## Credenciales Externas

`integraciones` se guarda con cast `encrypted:array`. La pantalla administrativa no devuelve secretos completos; solo expone indicadores como `circulo_credito_api_key_configurada`.

Si una clave viene vacia al actualizar, se conserva la clave configurada previamente.

## Permisos

Modulo Spatie: `configuracion-empresa`.

Permisos:

- `read configuracion-empresa`
- `update configuracion-empresa`

Modulo Spatie: `sucursales`.

Permisos:

- `create sucursales`
- `read sucursales`
- `update sucursales`
- `delete sucursales`

`Super Admin` conserva acceso implicito por `Gate::before`.

## Auditoria

Cada actualizacion de empresa registra actividad con descripcion:

`Configuracion de empresa actualizada`

Las sucursales registran:

- `Sucursal creada`
- `Sucursal actualizada`
- `Sucursal desactivada`
