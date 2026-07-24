---
type: explanation
area: fiscal
status: accepted
---

# ADR 0003: validacion local reutilizable del RFC

## Contexto

Empresa y clientes capturaban RFC con reglas distintas. Empresa solo verificaba longitud y expresion regular; clientes aceptaban cualquier cadena de hasta 255 caracteres. Esto permitia fechas imposibles, longitudes incompatibles con el tipo de persona y digitos verificadores incorrectos.

## Decision

Usar una regla de dominio unica, `App\Rules\Rfc`, desde los Form Requests.

La regla recibe el tipo de persona porque ese dato determina la longitud y la posicion de la fecha. Los formularios filtran opciones para facilitar la captura, pero el backend sigue siendo la autoridad.

La implementacion local sigue la estructura y el calculo publicados por el SAT. No se hace una consulta remota durante el guardado.

## Motivos

- La validacion es determinista y funciona sin disponibilidad de un tercero.
- Empresa y clientes comparten exactamente la misma interpretacion.
- Los errores distinguen estructura, fecha y digito.
- Evita enviar datos evidentemente corruptos a integraciones como SIC.

## Consecuencias

- Un RFC estructuralmente valido puede no existir ante el SAT.
- Verificar inscripcion requiere el portal o un servicio oficial separado.
- El bloque de año tiene dos digitos; sin fecha real no puede determinarse el siglo.
- Los RFC genericos no se aceptan en formularios de identidad propia.
- Cualquier cambio futuro debe actualizar regla, pruebas, referencia y fuente en el mismo PR.

## Alternativas descartadas

- Solo expresion regular: no detecta fechas ni digito incorrecto.
- Duplicar reglas por modulo: permite divergencias.
- Consultar al SAT en cada guardado: agrega dependencia externa, latencia y nuevos modos de falla.
