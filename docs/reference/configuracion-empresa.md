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

- Perfil legal: `razon_social`, `nombre_comercial`, `tipo_persona`, `rfc`, `regimen_fiscal_id`, `domicilio_fiscal`.
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

`regimen_fiscal_id` debe apuntar a un regimen compatible con `tipo_persona`. La validacion exacta del RFC se documenta en [Validador de RFC](validacion-rfc.md).

## Catalogos y formatos

- `regimen_fiscal_id`: relacion con `regimenes_fiscales`.
- `pais_base`: codigo ISO-2 existente en `paises`.
- `zona_horaria`: identificador IANA entregado por `DateTimeZone::listIdentifiers()`.
- `telefono`: numero internacional E.164.
- `domicilio_fiscal`: JSON con IDs de catalogo y una copia textual de pais y divisiones administrativas.

El domicilio guarda ambos valores porque los IDs permiten consultar los catalogos actuales y la copia textual conserva lo que el usuario vio al guardar.

### Consulta de codigo postal

La configuracion de empresa y el formulario de clientes comparten el composable `useCodigoPostal`.

- Con cero, uno o dos digitos no se consulta el servidor.
- Con tres o cuatro digitos se llama solamente a `codigos-postales/sugerencias`.
- Con cinco digitos se llama solamente a `codigos-postales/buscar`.
- Al cambiar la captura se cancela cualquier solicitud anterior y una respuesta atrasada no puede reemplazar la busqueda vigente.

Antes ambos flujos se observaban al escribir porque las sugerencias reaccionaban al valor mientras el formulario invocaba tambien la busqueda detallada. Las condiciones ahora son mutuamente excluyentes: sugerencias sirven para completar el codigo y buscar carga asentamientos y divisiones solo con el codigo completo.

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
