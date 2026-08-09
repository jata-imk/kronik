# Expediente de Clientes

## Flujo

1. Crear el cliente con identidad, datos fiscales y domicilio.
2. El sistema redirige a `clientes/{cliente}/expediente`.
3. Completar perfil economico, documentos, referencias, vinculados, garantias y consentimiento SIC por secciones independientes.

## Perfil economico

- `ocupacion`
- `actividad_economica`
- `ingresos_mensuales`
- `egresos_mensuales`
- `origen_recursos`

Los importes son declaraciones mensuales en la moneda configurada para la instalacion. El flujo mostrado en UI es informativo y no es un dictamen de capacidad de pago.

## Documentos

Tipos base: INE, comprobante de domicilio, constancia fiscal y comprobante de ingresos. Tambien se admiten documentos adicionales con nombre libre.

Estados: `pendiente`, `recibido`, `validado`, `rechazado` y `vencido`.

- Formatos permitidos: PDF, JPG y PNG.
- Tamano maximo: 10 MB.
- Disco: `local`, cuya raiz es `storage/app/private`.
- Las rutas fisicas no se exponen en props Inertia ni recursos API.
- Reemplazar un documento crea una nueva version y conserva la anterior.
- Rechazar requiere motivo y registra fecha y usuario revisor.

## Permisos

`ClientePolicy` reutiliza los permisos Spatie del modulo clientes:

- `read clientes`: abrir expediente y descargar documentos/evidencias.
- `update clientes`: modificar perfil, documentos, referencias, vinculados, garantias y consentimientos.
- `create clientes` y `delete clientes`: alta y baja del cliente.

Los endpoints anidados validan que cada recurso pertenezca al cliente de la URL.

La interfaz oculta todas las acciones de escritura a quienes solo tienen `read clientes`. Los mensajes de exito se muestran unicamente despues de que el backend confirma la operacion.

## Relaciones y garantias

- Una relacion es visible desde ambos clientes y el listado muestra el total de relaciones entrantes y salientes.
- El nombre y la accion de cada cliente relacionado abren su expediente KYC.
- El propietario de una garantia puede ser el titular o un cliente relacionado en cualquiera de las dos direcciones.
- No se puede eliminar un vinculo mientras alguno de los dos clientes lo use como propietario de una garantia; primero debe reasignarse esa garantia.
- Esta visibilidad no amplia permisos: abrir el expediente relacionado sigue sujeto a `read clientes`.

## Fechas y validacion

Los campos de fecha muestran `DD-MM-YYYY` y se envian al backend como `YYYY-MM-DD`, sin conversion UTC que cambie el dia. Los errores visibles deben cumplir [Validacion de formularios](validacion-formularios.md).

## Consentimiento SIC

Registra fecha, usuario capturista, medio, evidencia, vencimiento opcional y revocacion. No autoriza por si solo una llamada externa: Backlog 05 debe aplicar la regla de consentimiento vigente antes de consultar una SIC.

## Evidencia visual

- [Workspace de consentimiento en escritorio](../assets/backlog-02/expediente-consentimiento-desktop.png)
- [Perfil economico en viewport movil](../assets/backlog-02/expediente-mobile.png)

Las capturas se generaron con Chrome local mediante Playwright. La revision cubrio todas las secciones, el guardado del perfil, los dialogos y la ausencia de errores o advertencias de consola.
