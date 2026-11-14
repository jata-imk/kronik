---
type: reference
area: fiscal
status: active
---

# Validador de RFC

## Puntos de aplicacion

La regla unica es `App\Rules\Rfc`. Por defecto exige RFC propio; solo los Form
Requests de Clientes habilitan explícitamente `permitirGenericos`.

| Flujo | Campo de tipo | Campo RFC | Requerido |
| --- | --- | --- | --- |
| Configuracion de empresa | `tipo_persona` | `rfc` | Al activar |
| Alta y edicion de cliente | `datos_fiscales.tipo_persona` | `datos_fiscales.rfc` | En el alta; si se envia al editar |

Los modelos generados de los SDK de Circulo de Credito no ejecutan esta regla. Esos objetos reciben datos que ya debieron validarse en los Form Requests de Kronik.

## Genéricos en Clientes

Alta y edición, web y API, admiten exclusivamente `XAXX010101000` y
`XEXX010101000` como excepción a estructura, fecha y dígito. Se requiere un tipo
de persona válido (`fisica` o `moral`) y pueden repetirse entre clientes.
`Rfc::genericos()` mantiene la lista canónica; los formularios reciben
`rfcsGenericosPermitidos` y muestran «RFC genérico: identidad fiscal propia
pendiente». No se crea un estado fiscal persistido ni se acredita identidad ante el SAT.

El RFC propio conserva las reglas siguientes. Empresa rechaza ambos genéricos.
En edición parcial de cliente se usa el tipo guardado si se omite; enviar un
tipo inválido o nulo no activa ese respaldo. Corregir un RFC cambia la identidad
vigente y conserva snapshots anteriores, con la reevaluación existente.

No se agregan bloqueos a captura, originación o SIC QA. Se conserva el límite de
aprobación a personas físicas. El transporte SIC mantiene su payload actual;
las pruebas con dobles no prueban aceptación del proveedor. SIC productivo y
facturación no se habilitan; cada futura integración debe definir sus requisitos
de RFC propio antes de activarse. La unicidad de RFC propios queda diferida:
actualmente no se impone y la repetición de genéricos no la introduce.

## Normalizacion

1. Se eliminan espacios al inicio y al final.
2. Se convierte el valor a mayusculas con soporte UTF-8.
3. No se eliminan espacios interiores, puntos ni guiones: una captura con separadores se rechaza.

## Estructura

| Tipo | Patron conceptual | Longitud |
| --- | --- | --- |
| Persona fisica | `AAAA YYMMDD XXX` | 13 |
| Persona moral | `AAA YYMMDD XXX` | 12 |

Los caracteres iniciales admitidos son `A-Z`, `Ñ` y `&`. Los tres caracteres finales admiten `A-Z` y `0-9`.

## Fecha

El bloque `YYMMDD` debe representar un mes y dia validos. Como el RFC solo contiene dos digitos de año, la regla comprueba la validez de calendario usando el ciclo gregoriano de 2000 a 2099; no intenta deducir el siglo ni compararlo con una fecha que no forma parte del formulario de empresa.

## Digito verificador

1. Para persona moral se antepone un espacio para completar 13 posiciones.
2. Se toman las primeras 12 posiciones.
3. Se asigna el valor segun `0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ`.
4. Se multiplican por pesos descendentes de 13 a 2.
5. Se calcula `11 - (suma mod 11)`.
6. Resultado `11` produce `0`; resultado `10` produce `A`; los demas se usan directamente.
7. El resultado debe coincidir con el ultimo caracter.

## Fuera de alcance

- Inscripcion, vigencia o estatus ante el SAT.
- Correspondencia con nombre, razon social, CURP o fecha real.
- Constancia de situacion fiscal u obligaciones.
- Acreditar identidad fiscal o aceptación por una integración a partir de un RFC genérico.

Fuentes y fecha de consulta: [SAT: estructura y verificacion del RFC](../fuentes/sat-rfc.md).
