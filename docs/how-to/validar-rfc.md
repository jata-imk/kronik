---
type: how-to
area: fiscal
status: active
---

# Validar un RFC en Kronik

## Configuracion de empresa

1. Abra `Administracion > Configuracion de empresa`.
2. Elija `Persona fisica` o `Persona moral`.
3. Capture el RFC sin espacios ni guiones.
4. Elija un regimen fiscal compatible.
5. Guarde la configuracion.

El RFC debe tener 13 caracteres para persona fisica y 12 para persona moral.

## Expediente de cliente

1. Abra `Clientes`.
2. Cree o edite un cliente.
3. En `Datos fiscales`, elija el tipo de persona antes de capturar el RFC.
4. Capture el RFC y elija el regimen fiscal.
5. Guarde el expediente.

Al cambiar el tipo de persona, Kronik limpia un regimen que ya no sea compatible y ajusta la longitud maxima del RFC.

## Interpretar errores

- `debe tener 12/13 caracteres`: la longitud no corresponde al tipo de persona.
- `no tiene la estructura`: contiene caracteres o posiciones no admitidas.
- `fecha ... no es valida`: el bloque `YYMMDD` no representa una fecha de calendario.
- `digito verificador ... no es valido`: el ultimo caracter no coincide con el calculo del SAT.

## Comprobar existencia ante el SAT

Kronik solo valida estructura e integridad. Para saber si el RFC esta inscrito, use el [servicio oficial de verificacion del SAT](https://wwwmat.sat.gob.mx/cs/Satellite?c=SATAplicacion&childpagename=SatTyR%2FSATAplicacion&cid=1462228529073&packedargs=d%3DTouch&pagename=TySWrapper).

No use un resultado local exitoso como constancia de situacion fiscal.
