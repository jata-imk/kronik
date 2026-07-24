---
type: how-to
area: configuration
status: active
---

# Configurar Empresa

## Requisitos

- Usuario autenticado.
- Permiso `read configuracion-empresa` para ver.
- Permiso `update configuracion-empresa` para guardar.

## Pasos

1. Ir a `Administracion > Configuracion de empresa`.
2. Elegir si la empresa opera como persona fisica o moral.
3. Capturar el RFC. El sistema comprueba longitud, fecha y digito verificador segun el tipo de persona.
4. Elegir el regimen fiscal. La lista solo muestra regimenes compatibles.
5. Capturar de tres a cuatro digitos del codigo postal para ver sugerencias y completar los cinco digitos o elegir una sugerencia.
6. Elegir la colonia cuando el codigo postal tenga mas de un asentamiento. Estado, municipio y pais se completan desde el catalogo postal.
7. Capturar calle, numeros y datos de contacto.
8. Elegir pais base y zona horaria en sus catalogos.
9. Capturar el telefono con el selector de pais. Se guarda en formato E.164, por ejemplo `+525512345678`.
10. Definir moneda y parametros operativos.
11. Configurar integraciones sin exponer secretos ya guardados.
12. Cambiar `estatus` a `activa` solo cuando los datos obligatorios esten completos.
13. Guardar cambios.

Si hay errores, cada mensaje se muestra junto a su control y el foco se mueve al primer campo invalido.

## Datos requeridos para activar

- Razon social.
- RFC localmente valido.
- Regimen fiscal compatible.
- Correo operativo.
- Calle.
- Codigo postal.
- Estado.

La validacion local del RFC no comprueba que el registro exista o este activo en el SAT. Consulte [Validar un RFC](validar-rfc.md) para conocer el alcance.

## Sucursales

1. Ir a `Administracion > Sucursales`.
2. Crear cada sucursal operativa con clave unica.
3. Capturar domicilio, horario y prefijos/consecutivos de folios.
4. Desactivar sucursales que ya no operen; no se eliminan fisicamente desde el CRUD.

## Evidencia de Cierre

- Ejecutar pruebas Pest del modulo.
- Ejecutar build frontend si hubo cambios visuales.
- Actualizar referencia y TODOs si cambia la decision de modelo.
