---
type: reference
area: fiscal
status: active
---

# Validador de RFC

## Puntos de aplicacion

La regla unica es `App\Rules\Rfc`.

| Flujo | Campo de tipo | Campo RFC | Requerido |
| --- | --- | --- | --- |
| Configuracion de empresa | `tipo_persona` | `rfc` | Al activar |
| Alta y edicion de cliente | `datos_fiscales.tipo_persona` | `datos_fiscales.rfc` | En el alta; si se envia al editar |

Los modelos generados de los SDK de Circulo de Credito no ejecutan esta regla. Esos objetos reciben datos que ya debieron validarse en los Form Requests de Kronik.

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
- RFC genericos `XAXX010101000` y `XEXX010101000`, porque estos formularios capturan identidad fiscal propia.

Fuentes y fecha de consulta: [SAT: estructura y verificacion del RFC](../fuentes/sat-rfc.md).
