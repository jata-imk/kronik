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
2. Capturar perfil legal, contacto y domicilio fiscal.
3. Definir moneda, zona horaria, pais base y parametros operativos.
4. Configurar integraciones sin exponer secretos ya guardados.
5. Cambiar `estatus` a `activa` solo cuando los datos obligatorios esten completos.
6. Guardar cambios.

## Sucursales

1. Ir a `Administracion > Sucursales`.
2. Crear cada sucursal operativa con clave unica.
3. Capturar domicilio, horario y prefijos/consecutivos de folios.
4. Desactivar sucursales que ya no operen; no se eliminan fisicamente desde el CRUD.

## Evidencia de Cierre

- Ejecutar pruebas Pest del modulo.
- Ejecutar build frontend si hubo cambios visuales.
- Actualizar referencia y TODOs si cambia la decision de modelo.
