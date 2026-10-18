# Reiniciar datos de desarrollo o QA

`dev:reset-data` elimina datos de una instalación **exclusivamente de prueba**, conservando los catálogos SAT/SEPOMEX y la configuración técnica del sistema. No se debe usar sobre datos reales, aunque la instalación también tenga registros de prueba.

## Elegir el resultado

| Modo | Opciones | Resultado |
| --- | --- | --- |
| Mínimo, para preparar nuevos accesos QA | `--minimal --admin-email=administrador@ejemplo.com` | Conserva un superadministrador existente, un equipo, Matriz y la configuración de empresa; sin clientes, productos ni plantillas demo. |
| Demo local | Sin selector de modo | Elimina datos y ejecuta `SystemSeeder` y `DevelopmentSeeder`; recrea cuentas y clientes demo. |
| Vacío | `--no-seed` | Elimina también todos los usuarios, equipos, sucursales y la configuración de empresa; no crea accesos. |

Todos los modos de borrado requieren `--confirm-qa`. `--minimal` y `--no-seed` son excluyentes. `--admin-email` solo se acepta con `--minimal`.

El modo demo crea `test@example.com`, `consulta.clientes@example.test`, `editor.expedientes@example.test` y `sin.acceso.clientes@example.test`, con contraseña conocida `password`. No sirve para preparar accesos de colaboradores.

## Preparar la instalación QA antes de compartirla

1. Despliega los cambios y ejecuta las migraciones, incluida la tabla `development_data_resets`:

   ```bash
   php artisan migrate --force
   ```

2. Confirma que el correo elegido corresponde a una cuenta existente, activa, verificada y marcada como **superadministrador global**. Comprueba que puedes iniciar sesión con ella. El comando no crea contraseñas ni cambia las existentes.

3. Revisa el alcance sin modificar datos ni archivos:

   ```bash
   php artisan dev:reset-data --minimal --admin-email=administrador@ejemplo.com --dry-run
   ```

   La salida identifica entorno, conexión, base, conteos actuales, cantidad de archivos y los IDs que se conservarán. Los conteos son registros actuales por tabla, no promesas de cuántos desaparecerán: se conservan las excepciones del modo mínimo. No se muestran contraseñas, claves SIC ni contenido de documentos.

4. Antes del borrado, conserva fuera del árbol que se limpiará un respaldo verificado de la base y de los archivos privados/públicos, junto con la `APP_KEY` protegida. Es necesaria para recuperar datos cifrados. Este comando **no genera respaldos**.

5. Activa mantenimiento y detén los workers, procesos de generación de documentos, el programador y cualquier otro escritor de esta instalación. `down` y `queue:restart` por sí solos no detienen todos esos procesos. Usa el administrador de procesos de tu servidor y espera a que terminen los trabajos activos. En despliegues con varios nodos, detén escritores en todos los nodos.

   ```bash
   php artisan down --retry=60
   ```

6. Ejecuta exclusivamente sobre la instalación descartable que revisaste:

   ```bash
   php artisan dev:reset-data --minimal --admin-email=administrador@ejemplo.com --confirm-qa
   ```

   Guarda el identificador del registro de reinicio. No continúes si el comando devuelve error o deja archivos pendientes.

7. Tras un resultado correcto, reconstruye la caché de configuración si forma parte del despliegue, reactiva los procesos detenidos y sal de mantenimiento:

   ```bash
   php artisan optimize:clear
   php artisan optimize
   php artisan up
   ```

8. Inicia sesión de nuevo, verifica el equipo y Matriz, revisa la configuración conservada de empresa y crea los productos/plantillas que se usarán en la nueva ronda. Crea cuentas individuales para los colaboradores y asigna sus roles y sucursales. El superadministrador conserva el acceso global.

Con `APP_ENV=production`, el comando solo permite el modo mínimo, con mantenimiento activo y `--confirm-qa`. Esto permite limpiar una VPS configurada técnicamente como producción que **solo contiene datos QA**; la bandera no autoriza eliminar operaciones reales. No cambies `APP_ENV` para sortear esta restricción.

## Qué elimina

- Clientes, datos fiscales, direcciones, referencias, avales/vínculos, garantías, documentos y consentimientos SIC.
- Consultas SIC, resultados, eventos y evidencias, incluidos registros históricos de simulación.
- Solicitudes, revisiones, dictámenes, resoluciones, eventos, paquetes, firmas y formalizaciones.
- Créditos QA, cronogramas y sus versiones, desembolsos, movimientos, pagos, anticipos, liquidaciones y reversos.
- Productos, versiones, periodicidades, reglas, comisiones asociadas, usos y políticas de originación.
- Plantillas, versiones, recursos gráficos y documentos generados.
- Todas las entradas y submenús de **Administración → Menú contextual por módulos**, junto con sus asociaciones a módulos y rutas (`menubar_items`, `menubar_item_module`). En mínimo y vacío quedan sin entradas; en demo se eliminan y `SystemSeeder` reconstruye el catálogo de navegación con rutas de activación vacías. Para cargarlo después del modo mínimo usa el [seeder existente del menú](configurar-menu-contextual.md).
- Usuarios, equipos y sucursales, salvo las excepciones del modo mínimo; invitaciones, membresías, tokens API, sesiones, solicitudes de recuperación de contraseña y recordar sesión.
- Roles de equipo y asignaciones directas de roles/permisos. En modo mínimo se copian nuevamente las plantillas de roles globales al equipo conservado, sin asignarlas a otros usuarios.
- Actividad histórica QA, trabajos en cola, fallidos, lotes y tablas de caché de esta base.
- Archivos **registrados** de documentos de clientes, consentimientos SIC, documentos generados, firmas, recursos gráficos y fotos de usuarios eliminados.

No se borran directorios completos de almacenamiento. Archivos huérfanos sin registro, exportaciones manuales, respaldos, logs técnicos, correos ya enviados, registros de proveedores y otros almacenes externos no forman parte de esta limpieza. Si contienen información que quieras retirar, requieren inventario y tratamiento separado.

## Qué conserva

- Países, regímenes fiscales SAT, divisiones administrativas y códigos postales SEPOMEX. Se verifican sus conteos dentro de la transacción; pueden aumentar en modo demo si faltaban catálogos mínimos.
- Módulos, permisos, roles globales y sus permisos, proveedores/APIs SIC y catálogo de conceptos de comisión. En modo demo `SystemSeeder` vuelve a sembrarlos idempotentemente; en mínimo y vacío no se modifica su configuración.
- `.env`, credenciales de proveedores, `APP_KEY`, configuración técnica y código.
- Configuración de almacenamiento (`config/filesystems.php` y sus variables de entorno): define las carpetas privadas/públicas donde Laravel guarda archivos, o la conexión a un servicio como S3. El comando elimina los archivos registrados incluidos en su alcance y mantiene esas ubicaciones y conexiones disponibles para archivos nuevos.
- El historial de mantenimiento `development_data_resets`, separado de la actividad operativa QA que se borra.
- En modo mínimo: la configuración de empresa y su logotipo; el ID, contraseña, verificación, 2FA y foto del administrador seleccionado; su primer equipo propio por ID y Matriz si existen. Si faltan, se crean el equipo «Administración» y Matriz. Ambos quedan activos y el administrador queda situado en ellos.

No se reinician secuencias de IDs ni los consecutivos existentes de Matriz. Las sesiones y tokens anteriores se revocan; se mantienen contraseña y 2FA para volver a iniciar sesión.

## Integridad y recuperación

Las eliminaciones y la configuración mínima se ejecutan en una transacción con `DELETE`, sin `TRUNCATE` ni desactivar claves foráneas. Un fallo anterior al commit revierte la base y no elimina archivos. El comando rechaza tablas nuevas sin clasificar y operaciones financieras cuyo `modo` no sea `qa`, o consultas SIC con un `ambiente` explícito distinto de `qa`. Los registros SIC antiguos sin ambiente solo se aceptan bajo la declaración explícita del operador de que toda la instalación es QA; no es una detección automática de datos reales.

Los archivos se eliminan **después** del commit. Un manifiesto cifrado en `development_data_resets` conserva las rutas pendientes; los datos operativos ya no son necesarios para reintentar. El registro guarda modo, cuenta conservada, operador del sistema, conteos y fechas. Si se interrumpe el proceso tras confirmar la base, revisa los registros con `completed_at` vacío. No ejecutes otro reinicio mientras quede alguno pendiente.

Para reintentar únicamente los archivos del registro mostrado, mantén la instalación y sus escritores detenidos:

```bash
php artisan dev:reset-data --resume-cleanup=IDENTIFICADOR --confirm-qa
```

El reintento no elimina nuevos registros, no vuelve a sembrar y es idempotente si un archivo ya no existe. Si falla una ruta o un disco, corrige el acceso/configuración y repite el mismo identificador. No cambies la `APP_KEY` ni reasignes el disco a otro almacenamiento durante una limpieza pendiente.

Se validan los discos y prefijos que utilizan los servicios actuales; se rechazan rutas ajenas, subrutas inesperadas, recorridos `..` y enlaces simbólicos locales. Un archivo histórico fuera de esos formatos debe revisarse antes de reiniciar; no amplíes el borrado indiscriminadamente para sortearlo.

El bloqueo de ejecución evita dos reinicios simultáneos en el mismo nodo. No sustituye detener escritores ni coordinar nodos que compartan base de datos.

La implementación admite las sesiones de esta base (`sessions`) y la cola de esta base (`jobs`, `job_batches`, `failed_jobs`), además de sesiones en memoria y cola síncrona para pruebas. Rechaza configuraciones externas de sesiones, colas o actividad para evitar dejar datos sin limpiar. La caché SQL se limpia en la base; otros almacenes de caché o servicios externos requieren un procedimiento propio.

## Desarrollo local y pruebas

```bash
php artisan dev:reset-data --dry-run
php artisan dev:reset-data --confirm-qa
php artisan dev:reset-data --no-seed --confirm-qa
php artisan test --filter=ResetDevelopmentData
```

Las pruebas locales usan SQLite en memoria y discos simulados; CI ejecuta también el comportamiento sobre MariaDB aislada. No ejecutes un reinicio manual para probar el código sobre la base configurada en `.env`. Consulta [Restaurar catálogos](restaurar-catalogos.md) si faltan SAT/SEPOMEX: el modo mínimo no los descarga ni reconstruye.
