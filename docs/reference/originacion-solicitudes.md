# Solicitudes, evaluación y cumplimiento — P2/P3 parcial

## Alcance disponible

Crear borrador PF/MXN, reanudar captura, asignar responsable y enviar a revisión.
También permite devolver, corregir, reenviar, rechazar, cancelar y registrar
dictámenes humanos preliminares de evaluación y PLD. No habilita aprobación,
validación automática de cumplimiento, contratos ni dinero real. P3–P6 completarán la primera vertical. Alcance aprobado
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
6. Desde **Evaluación** o **Cumplimiento**, abrir la misma solicitud y registrar
   resultado, fundamento, fuentes y metodología/versionado usados. PLD exige
   riesgo; no permite concluir sin observaciones con riesgo sin determinar.
7. Devolver con motivo operativo y responsable activo con permiso de edición.
   El responsable corrige y reenvía: se genera otra revisión, sin alterar la previa.
   Los dictámenes anteriores quedan históricos, no aplicables a la nueva revisión.
8. Rechazar únicamente durante revisión; cancelar borrador, devolución o revisión.
   Cierres conservan evidencia, impiden edición/reasignación/reenvío y salen de
   Mi trabajo por defecto. Se recuperan mediante filtro de estado, no se reabren.
   Un cierre no muestra los dictámenes históricos como tareas pendientes.

La captura 0/7–7/7 no representa aprobación ni cumplimiento documental. El
expediente y las consultas SIC tienen enlaces propios. La tabla de revisión es
informativa, sin IVA; no constituye formalización ni saldo real.

## Acceso

- `read solicitudes` + `read clientes`: bandeja, detalle e historial operativo.
- `create solicitudes`: crear, además de lectura anterior.
- `update solicitudes`: editar/enviar en la sucursal actual responsable.
- `assign solicitudes`: asignar responsable en la sucursal actual responsable.
- `review solicitudes`: devolver o rechazar; `cancel solicitudes`: cancelar.
- `read evaluacion-solicitudes` / `read cumplimiento`: leer dictámenes reservados
  y acceder a la bandeja especializada, además de la lectura de solicitudes/clientes.
- `create evaluacion-solicitudes` / `create cumplimiento`: registrar dictámenes
  en revisión, además de lectura especializada y sucursal actual responsable.
- Las notas PLD no se envían como props a quien solo tiene lectura operativa.
  Los motivos de devolución/cierre sí son operativos y visibles en la solicitud;
  no deben contener detalles reservados de PLD o respuestas SIC.
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
`solicitud_resoluciones`: acción, revisión (opcional al cancelar borrador), actor,
responsable y motivo cifrado; inmutable.
`solicitud_dictamenes`: especialidad, resultado, revisión, actor y contenido cifrado;
inmutable. El nuevo dictamen reemplaza operativamente al previo, sin borrarlo.
La pantalla muestra últimos 20 por especialidad y últimas 30 resoluciones.
Respaldar la clave de cifrado de Laravel de forma segura: cambiarla sin estrategia
de rotación vuelve ilegibles las evidencias cifradas. No copiarla a documentación.

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

La aprobación sigue pendiente en P3: configuración explícita y versionada de
separación de funciones, política SIC por producto, requisitos documentales,
vigencia/límites y validación del perfil del operador. No hay valores por defecto
que autoricen aprobación. Un dictamen favorable es preliminar, no habilita SIC
manual por producto ni certifica cumplimiento. Las fuentes son referencias
documentadas por el revisor, todavía no adjuntos nuevos ligados al dictamen.
Pendientes: tareas por especialidad/asignación independiente, requisitos calculados,
consulta paginada de toda la historia y prueba concurrente multi-conexión.
Sin SIC productivo, selección de comisiones opcionales, exportación, acción masiva,
alertas externas ni movimientos monetarios en este incremento.
