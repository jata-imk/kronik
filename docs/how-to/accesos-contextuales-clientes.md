# Accesos del cliente desde el menubar

En **Clientes → Ver información** y **Clientes → Editar**, la tira superior
contiene los accesos al expediente y a una nueva solicitud. El encabezado ya no
repite esos botones.

- **Expediente KYC / Abrir expediente:** abre el expediente del cliente visible.
- **Nueva solicitud:** abre `solicitudes.create?cliente_id=ID` con ese cliente
  seleccionado. Requiere lectura de clientes y solicitudes, creación de
  solicitudes y tener seleccionada la sucursal del cliente.
- Cambiar la sucursal no cambia al cliente de la pantalla. Para crear su solicitud,
  vuelve a seleccionar su sucursal; el servidor también verifica el enlace directo.

Los rótulos e iconos personalizados existentes se conservan. Los dos accesos se
ubican al nivel principal de la tira, fuera del desplegable Clientes. La lectura
del expediente conserva los permisos actuales; no exige permisos para crear solicitudes.

## Actualización en QA

La migración `2026_09_26_000000_add_cliente_menubar_shortcuts` instala los accesos
sin volver a ejecutar el catálogo completo de menús. Ejecutar las migraciones
habituales del despliegue es suficiente; no se requiere un nuevo permiso ni bandera.
En instalaciones nuevas se incluyen al sembrar los menús.

El ajuste es idempotente: reutiliza una entrada configurada de tipo `route:name`
con destino `solicitudes.create` o `clientes.expediente.show` y vinculada al módulo
Clientes. Las rutas usan el cliente actual aunque la entrada no tenga parámetros.
Los enlaces estáticos personalizados no se interpretan ni se reescriben.

La reversión de esta migración no elimina menús: se conserva cualquier
personalización posterior. Si se necesita revertir su ubicación, puede editarse
desde Administración → Menubar.

## Guion de comprobación

1. Abre un cliente de tu sucursal. Comprueba que los dos accesos están en la tira
   superior y no se repiten como botones junto al título.
2. Abre su expediente desde la tira; comprueba el nombre del cliente.
3. Regresa y abre Nueva solicitud; comprueba que el cliente está precargado.
4. Repite desde Editar cliente.
5. Con lectura de clientes pero sin creación de solicitudes, confirma que puedes
   abrir el expediente pero no aparece Nueva solicitud.
6. Con otra sucursal seleccionada, confirma que Nueva solicitud desaparece y que
   abrir manualmente su URL devuelve acceso denegado. También aplica a Super Admin.

Esta corrección no implementa formalización, firma ni movimientos monetarios.
