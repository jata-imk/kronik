# Comandos Artisan

## Lecturas financieras del tablero

- `php artisan creditos:proyectar`: actualiza hoy y cierres mensuales; el programador lo ejecuta cada cinco minutos sin solaparse.
- `php artisan creditos:proyectar --reconstruir`: recalcula desde evidencia original y audita el resultado global.
- `php artisan creditos:proyectar --diagnosticar`: compara sin escrituras ni actividad adicional.
- `--credito=ID` limita cualquiera de esos recorridos a un crédito existente.

Revisar [preparación y validación del tablero](../how-to/validar-tablero.md), sus
fuentes, límites y disponibilidad antes de operar sobre QA.

## Comunicaciones

- `php artisan comunicaciones:preparar-vencimientos`: registra candidatos preventivos desde la lectura vigente según los días de anticipación de cada regla; la frecuencia se configura con `COMMUNICATIONS_DUE_SCHEDULE_MINUTES` (60 por defecto).
- `php artisan comunicaciones:despachar`: prepara salidas con consentimiento y canal/regla activos, recupera trabajos interrumpidos y encola el envío después del commit; la frecuencia se configura con `COMMUNICATIONS_DISPATCH_SCHEDULE_MINUTES` (5 por defecto). Requiere un worker de cola para ejecutarlo cuando `QUEUE_CONNECTION=database` y `COMMUNICATIONS_SEND_ENABLED=true`.

Ver [contrato de canales](canales-comunicacion.md) y [pruebas](../how-to/probar-comunicaciones.md). `schedule:list` no demuestra que el cron del servidor esté activo.

## Desarrollo

```bash
php artisan serve
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
php artisan dev:reset-data --dry-run
php artisan mail:preview
php artisan test
```

`mail:preview` genera muestras HTML/TXT/EML ficticias y privadas, sin enviar
mensajes ni crear usuarios; solo local/testing. Consulta
[Probar correos de acceso](../how-to/probar-correos-acceso.md).

Para revisar o preparar una instalación exclusivamente QA sin recrear demos:

```bash
php artisan dev:reset-data --minimal --admin-email=administrador@ejemplo.com --dry-run
php artisan dev:reset-data --minimal --admin-email=administrador@ejemplo.com --confirm-qa
```

El borrado requiere `--confirm-qa` en todos los modos. Fuera de local/testing
requiere mantenimiento; con `APP_ENV=production` solo permite el modo mínimo.
Respaldo, detención de procesos, alcance y recuperación de archivos pendientes:
[Reiniciar datos de desarrollo o QA](../how-to/reset-datos-desarrollo.md).

`phpunit.xml` fuerza SQLite `:memory:` para la ejecución local. CI reemplaza
esa configuración con una base MariaDB aislada. Las pruebas no deben usar la
base configurada para la aplicación.

La suite completa configura `memory_limit=512M` únicamente en PHPUnit/Pest.
La ampliación evita agotar el límite PHP de 128 MB al acumular fixtures y evidencia
cifrada de la suite; no modifica PHP-FPM, el servidor de aplicación ni acredita
capacidad de cartera. `php artisan test` y `vendor/bin/pest` usan esa configuración.

## Catalogos

```bash
php artisan db:seed --class=PaisesSeeder --force
php artisan sat-cfdi-v4:update
php artisan sepomex:update
php artisan db:seed --class=SystemSeeder --force
```

`sat-cfdi-v4:update` necesita `libreoffice --headless` si el archivo disponible
es XLS. Consulte [Restaurar catálogos](../how-to/restaurar-catalogos.md).

Solo en entornos que usan exclusivamente datos de prueba:

```bash
php artisan db:seed --class=DevelopmentSeeder --force
```

## Permisos

Para cargar el catálogo contextual después de un reinicio QA, sin activar sus
entradas en las páginas:

```bash
php artisan db:seed --class=MenubarItemsSeeder --force
```

Consulta [Cargar el catálogo del menú contextual](../how-to/configurar-menu-contextual.md).

```bash
php artisan permission:create-permission "read clientes" web --module-id=1
php artisan permission:create-role "Operador" web "read clientes|read solicitudes" --team-id=1
```

Los dos comandos persisten actividad en la misma transacción que sus cambios.
El ejecutor es el sistema; no se atribuyen a un usuario de consola supuesto.
Crear un rol registra su matriz inicial una sola vez; repetir sin cambios no
añade eventos. En roles, omitir `--team-id` opera sobre una plantilla global;
proporcionarlo exige un ID positivo de equipo existente. La opción no cambia
preferencias de usuarios y no sustituye un rol del equipo por uno global.
Los nombres personalizados y valores de permisos no se copian a actividad.
Consultar la [matriz de auditoría](auditoria-eventos.md).

## Colas

### Inventario previo al corte SMS

`php artisan comunicaciones:inventariar-sms` consulta conexiones SMS, salidas,
callbacks y colas de comunicaciones sin escribir ni enviar. `--json` produce el
informe procesable; retorna 1 si la observación está incompleta. No inspecciona
Redis/SQS/Beanstalkd ni certifica preparación para el corte. Ver el
[contrato del inventario](inventario-sms.md) antes de ejecutarlo en otro entorno.

```bash
php artisan queue:work
```

En desarrollo, `composer dev` ejecuta servidor Laravel, queue listener y Vite en paralelo.

En producción use `queue:work` bajo Supervisor u otro monitor y reinícielo
después de desplegar:

```bash
php artisan queue:restart
```

## Producción

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan down --retry=60
php artisan up
```

No use `migrate:fresh`, `dev:reset-data` o `DevelopmentSeeder` sobre una
instancia con datos reales.

## Completar folios históricos

`php artisan folios:completar --dry-run` valida prefijos y cuenta pendientes sin escribir. `php artisan folios:completar` asigna los pendientes sin regenerar documentos. Confirmar entorno y respaldo, activar mantenimiento y detener workers. [Contrato y recuperación](folios.md).

### Limpieza selectiva SMS de prueba

`php artisan comunicaciones:limpiar-sms-prueba` muestra conteos sin escribir.
`--apply --confirm-qa` aplica la retirada de configuración, callbacks y jobs
anteriores inequívocamente identificados, invalida salidas y desactiva reglas SMS.
Exige ambas barreras cerradas; fuera de local/testing exige mantenimiento.
No purga colas compartidas ni modifica correo/Meta. Revisar el
[procedimiento y sus límites](../how-to/limpiar-sms-prueba.md).
