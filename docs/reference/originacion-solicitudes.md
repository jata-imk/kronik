# Solicitudes y Mi trabajo — P2

## Alcance disponible

Crear borrador PF/MXN, reanudar captura, asignar responsable y enviar a revisión.
No habilita aprobación, PLD, contratos ni dinero real. El detalle comunica esos
pendientes expresamente; P3–P6 completarán la primera vertical. Alcance aprobado
en [Notion](https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666).

## Operación

1. Seleccionar sucursal activa. En **Solicitudes → Nueva solicitud**, buscar un
   cliente de esa sucursal; desde el expediente se puede precargar el cliente.
2. Guardar solo el cliente para crear borrador o capturar las condiciones disponibles.
3. Completar versión vigente de producto, monto, periodicidad, número de pagos,
   método, fecha estimada y destino. Este incremento usa solo comisiones obligatorias;
   la selección de opcionales sigue disponible en el simulador de productos.
4. Enviar a revisión. El servidor verifica condiciones con el simulador existente,
   fotografía cliente/términos/producto y conserva el uso de producto por revisión.
5. Asignar responsable autorizado de la sucursal. **Mi trabajo** muestra las
   solicitudes asignadas; aún no se emiten notificaciones externas.

La captura 0/7–7/7 no representa aprobación ni cumplimiento documental. El
expediente y las consultas SIC tienen enlaces propios. La tabla de revisión es
informativa, sin IVA; no constituye formalización ni saldo real.

## Acceso

- `read solicitudes` + `read clientes`: bandeja, detalle e historial operativo.
- `create solicitudes`: crear, además de lectura anterior.
- `update solicitudes`: editar/enviar en la sucursal actual responsable.
- `assign solicitudes`: asignar responsable en la sucursal actual responsable.
- El responsable debe estar activo, tener membresía en sucursal y permisos de
  lectura en el contexto de equipo que está realizando la asignación.
- Lectura institucional global, siguiendo ClientePolicy; escritura contextual por
  sucursal. Incluso Super Admin selecciona la sucursal correspondiente antes de operar.
- No se conceden permisos automáticamente a roles operativos al desplegar.

## Persistencia y concurrencia

`solicitudes`: borrador, responsable, sucursal histórica, versión de edición.
`solicitud_revisiones`: fotografía inmutable y hash SHA-256.
`solicitud_eventos`: actividad inmutable; vista muestra los últimos 30 eventos.
`producto_version_usos`: referencia `solicitud_revisiones` con ID de revisión.

Creación usa UUID por actor y verifica payload; reintento idéntico no duplica.
Escrituras bloquean la solicitud y comparan `lock_version`; cambios concurrentes
devuelven mensaje español y requieren recargar. Doble envío no genera otra revisión.
Un cliente con solicitudes no puede eliminarse: se comprueba antes de tocar archivos
y las claves foráneas restringen borrado. Traslado de cliente no mueve solicitudes.
No hay ruta de eliminación ni edición de revisiones/eventos. La aplicación no
pretende impedir escrituras SQL privilegiadas: conservar controles de acceso a BD.

## Despliegue

Tras respaldo y revisión del PR, ejecutar en la instalación objetivo:

```shell
php artisan migrate --force
php artisan db:seed --class=ModulesAndPermissionsSeeder --force
php artisan permission:cache-reset
npm run build
```

No ejecutar `DevelopmentSeeder`, `E2eSeeder` ni `migrate:fresh` en una instalación
con datos reales. El nuevo seeder de permisos es idempotente y no reasigna roles.
La migración crea tablas aditivas con FK restrictivas; su rollback elimina esas
tablas y sus datos: respaldar y verificar ausencia de operaciones antes de revertir.

## Verificación

Pest: creación/idempotencia, validación española, producto retirado/rangos, locks,
permisos, sucursal, snapshot, asignación y protección del cliente.
Vitest: acciones por permisos, progreso y estado enviado.
Playwright: borrador incompleto → corregir → revisión; escritorio/móvil y capturas.
El cambio de orientación reveló un fallo de PrimeVue 4.3.1 al posicionar un overlay
inexistente. `primeVueSelectOverlay` protege únicamente esa llamada en Select
(también Paginator), sin modificar dependencias. Pruebas unitarias y E2E cubren la
protección; retirarla cuando una actualización supere esa regresión sin ella.
SQLite local valida comportamiento; MariaDB en CI valida compatibilidad. Prueba
simultánea multi-conexión de contención queda pendiente; no inferirla de SQLite.

## Límites siguientes

No permite devolver, rechazar, cancelar ni aprobar todavía. Esos estados requieren
P3 con motivos, permisos, requisitos aplicables y políticas configuradas. Tampoco
hay modificaciones después del envío hasta introducir una nueva revisión segura.
Sin SIC productivo, selección de comisiones opcionales, exportación, acción masiva,
alertas externas ni movimientos monetarios en este incremento.
