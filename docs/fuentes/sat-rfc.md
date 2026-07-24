# SAT: estructura y verificacion del RFC

## Entidad

Servicio de Administracion Tributaria (SAT), Gobierno de Mexico.

Fecha de consulta: 2026-07-23.

## Fuentes primarias

- [Estructura de la clave en el RFC](https://www.sat.gob.mx/cs/Satellite?blobcol=urldata&blobkey=id&blobtable=MungoBlobs&blobwhere=1461176322651&ssbinary=true): longitud, caracteres iniciales, fecha y homoclave para personas fisicas y morales.
- [Especificacion tecnica del RFC](https://www.sat.gob.mx/cs/Satellite?blobcol=urldata&blobkey=id&blobtable=MungoBlobs&blobwhere=1461174783775&ssbinary=true): formato de 12/13 posiciones y digito verificador.
- [Verifica si estas registrado en el RFC](https://wwwmat.sat.gob.mx/cs/Satellite?c=SATAplicacion&childpagename=SatTyR%2FSATAplicacion&cid=1462228529073&packedargs=d%3DTouch&pagename=TySWrapper): consulta oficial de inscripcion.

## Uso en Kronik

Estas fuentes sustentan:

- Diferencia entre RFC de persona fisica y moral.
- Posicion del bloque de fecha.
- Caracteres y longitud.
- Calculo local del digito verificador.
- Distincion entre validar estructura y confirmar inscripcion.

## Limites

- La documentacion interna resume las fuentes; no las sustituye.
- El SAT puede cambiar documentos o rutas. Revisar enlaces y fecha antes de modificar reglas.
- Las reglas fiscales que excedan validacion de formato requieren revision especializada.
