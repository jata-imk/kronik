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
- El campo de telefono comparte la normalizacion E.164 con sucursales y clientes; cargar o guardar el formulario no debe restaurar un valor anterior.
- El selector de pais incluye busqueda interna insensible a acentos y muestra una representacion consistente junto al codigo ISO.
- `domicilio_fiscal`: JSON con IDs de catalogo y una copia textual de pais y divisiones administrativas.

El domicilio guarda ambos valores porque los IDs permiten consultar los catalogos actuales y la copia textual conserva lo que el usuario vio al guardar.

### Consulta de codigo postal

La configuracion de empresa, las sucursales y el formulario de clientes comparten `CodigoPostalAutocomplete`, respaldado por el composable `useCodigoPostal`.

- Con cero, uno o dos digitos no se consulta el servidor.
- Con tres o cuatro digitos se llama solamente a `codigos-postales/sugerencias`.
- Con cinco digitos se llama solamente a `codigos-postales/buscar` y el autocompletado presenta una unica opcion para el codigo exacto.
- Estado, municipio, pais y las opciones de colonia solo se cargan al seleccionar el codigo o confirmarlo con Enter.
- La colonia siempre se elige manualmente; su seleccion conserva el `codigo_postal_id` correspondiente al asentamiento.
- Al abrir un registro existente, los IDs persistidos reconstruyen la seleccion sin obligar a volver a escribir el codigo postal.
- El backend canonicaliza domicilios heredados que solo tengan codigo postal y colonia. Solo completa una opcion cuando la coincidencia es inequivoca; nunca elige arbitrariamente entre asentamientos.
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

Super Admin global conserva acceso implicito mediante `users.is_super_admin` y `Gate::before`.

Las dependencias y reglas de desactivacion se documentan en [Usuarios, equipos y sucursales](usuarios-equipos-sucursales.md).

## Auditoria

Cada actualizacion de empresa registra actividad con descripcion:

`Configuracion de empresa actualizada`

El evento estable es `empresa.updated` y sus propiedades solo incluyen los nombres de los campos modificados.

Las sucursales registran:

- `Sucursal creada`
- `Sucursal actualizada`
- `Sucursal desactivada`

Sus eventos estables son `sucursal.created`, `sucursal.updated` y `sucursal.deactivated`. No se almacenan valores fiscales, de contacto ni de domicilio dentro de las propiedades de auditoria.
