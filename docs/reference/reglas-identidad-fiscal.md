---
type: reference
area: business-rules
status: active
---

# Reglas de negocio de identidad fiscal

## Tipo de persona

- Los valores internos son `fisica` y `moral`.
- Una empresa existente se migra como persona moral.
- Cambiar el tipo de persona invalida y limpia un regimen fiscal incompatible.

## Regimen fiscal

- El regimen se guarda por `regimen_fiscal_id`, no como texto libre.
- Para persona fisica, el catalogo debe tener `fisica = true`.
- Para persona moral, el catalogo debe tener `moral = true`.
- El backend repite esta comprobacion; el filtro de la interfaz no es una medida de seguridad.

## RFC

- La longitud depende del tipo de persona.
- El RFC se normaliza antes de persistir.
- Estructura, fecha y digito verificador deben ser validos.
- La empresa puede conservar un RFC vacio en borrador o suspendida, pero no puede activarse asi.
- Un cliente nuevo requiere RFC. En una actualizacion parcial se valida siempre que el campo sea enviado.

## Domicilio fiscal de empresa

- Para activar se requieren calle, codigo postal y estado.
- Codigo postal y divisiones administrativas se obtienen del catalogo.
- Se guardan IDs normalizados y una copia textual para trazabilidad historica.

## Telefono

- La configuracion de empresa almacena un numero E.164: signo `+`, codigo de pais y numero nacional, con un maximo de 15 digitos.

Estas reglas validan consistencia de captura; no sustituyen revision fiscal ni una consulta oficial.
