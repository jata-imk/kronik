# Desplegar y comprobar Bullet en QA

Esta guía cubre ambas políticas con datos sintéticos. El usuario ejecuta el
despliegue y el recorrido en la VPS compartida; las pruebas automatizadas usan
bases aisladas. Consultar [casos y cifras independientes](../reference/bullet-casos-definicion.md)
y [ADR 0042](../explanation/adr-0042-habilitacion-ciclo-bullet-qa.md).

## Preparar el despliegue

Tras integración y CI aprobadas, seguir el [procedimiento de actualización de la
VPS](desplegar-vps-nuevo.md#12-despliegues-posteriores): respaldo, mantenimiento,
actualización de `main`, dependencias y build. Conservar la configuración y rutas
Node/Chrome del servidor. Registrar el commit con `git rev-parse HEAD`.

Usando el PHP del sitio, comprobar y aplicar únicamente migraciones pendientes:

```bash
php8.3 artisan migrate:status
php8.3 artisan migrate --force
php8.3 artisan optimize:clear
php8.3 artisan optimize
php8.3 artisan queue:restart
php8.3 artisan up
```

La migración Bullet `2026_11_05_220000_add_bullet_conditions_to_product_rules`
añade dos columnas nullable; no rellena históricos. No hay migraciones adicionales,
nuevos permisos ni seeders operativos por esta habilitación. No ejecutar el
runner E2E, `migrate:fresh` ni seeders de desarrollo sobre la base compartida.

Comprobar las banderas existentes según la [guía del ciclo simple](qa-credito-simple.md):
aprobaciones, paquetes QA, firmas QA, desembolsos QA, pagos QA y anticipos QA.
Mantener SIC productivo y transferencias reales deshabilitados. Verificar `/up`,
login, reinicio del worker y generación del PDF desde la interfaz.

## Recorrido para cada política

1. En la sucursal QA, usar cliente sintético con expediente y permisos vigentes.
   Crear un producto/versión Bullet separado: motor 2, tasa fija MXN, Act/360,
   duración mensual de dos periodos, capital al final y ordinario sobre saldo
   insoluto. Elegir explícitamente una base moratoria admitida y conservar fecha
   en el anticipo. Configurar fiscalidad/comisiones de prueba y política de
   originación manual; no reutilizar valores comerciales como política universal.
2. Repetir con **Intereses periódicos** y **Intereses al vencimiento**. Guardar y
   consultar las bases; activar con fecha empresarial actual. La versión queda
   inmutable y disponible en Solicitudes. No deben aparecer diaria, capitalización
   ni reducción de plazo Bullet.
3. Simular la misma fecha, monto y duración. En periódicos, cada vencimiento cobra
   interés y el último añade capital. Al vencimiento, el interés se exige al final;
   los intervalos intermedios conservan el calendario para CAT. Revisar importes
   de comisiones/impuestos por separado.
4. Capturar, enviar y aprobar conforme a la separación de funciones configurada.
   Preparar contrato, abrir/descargar PDF, recibir copia firmada sintética y
   aceptar firma. Revisar política de interés, bases y «conservar vencimiento» en
   el anexo. Registrar una sola vez el desembolso QA de hoy, coincidente con la
   tabla firmada.
5. Consultar capital pendiente, ordinario devengado, aún no exigible, vencido y
   exigible a hoy. Abrir anticipos, revisar cotización y confirmar un anticipo
   parcial. Conservar recibo y calendario firmado; el nuevo calendario mantiene
   vencimientos y reduce únicamente interés futuro.
6. Cotizar y confirmar liquidación exacta. Revisar estado liquidado y ausencia de
   operaciones nuevas; revertir la última liquidación con motivo, comprobar
   compensaciones y reapertura sin borrar historia. Repetir confirmaciones solo
   para verificar idempotencia del mismo envío, sin crear referencias nuevas.
7. Comprobar consulta autorizada sin permisos de mutación, cambio de sucursal,
   selección de calendarios históricos y escritorio/móvil. Revisar actividad:
   actor/contexto correctos y una emisión por operación, sin importes, motivos
   reservados, referencias ni contenido contractual en sus metadatos.

El recorrido compartido conserva reloj y fechas reales. El mismo día del
desembolso el devengado ordinario es cero; no forzar un pago sin deuda exigible
ni adelantar el reloj para crear atraso. Los escenarios de interés conservado,
mora y vencimiento también pueden reconstruirse con el recorrido histórico
enlazado al final; las pruebas aisladas conservan fronteras exactas.

Capturas del escenario aislado tras el anticipo, con capital 8880 e interés
conservado 120: periódicos en [escritorio](../assets/bullet/periodicos-desktop.png)
y [móvil](../assets/bullet/periodicos-mobile.png); al vencimiento en
[escritorio](../assets/bullet/al_vencimiento-desktop.png) y
[móvil](../assets/bullet/al_vencimiento-mobile.png).

## Pruebas automatizadas aisladas

```bash
php artisan test --compact
npm run test:unit
npm run build
E2E_BULLET_FIXTURES=true npm run test:e2e -- bullet-ciclo.spec.js productos-bullet.spec.js
E2E_ORIGINACION_DUAL=true npm run test:e2e -- solicitudes-dual.spec.js
```

En PowerShell, establecer la variable del escenario en el proceso antes de
ejecutar el comando y retirarla al cambiar de escenario. Para PDF puede indicarse
`DOCUMENTOS_CHROME_PATH` con el Chromium instalado. El harness reinicia únicamente
SQLite dedicada o `kronik_e2e`; genera productos por servicios reales, sin injertar
snapshots. Su reloj de 2027 es exclusivo de pruebas y no se instala en QA.

CI ejecuta `php tests/Concurrency/bullet.php` sobre MariaDB 11, con
`APP_ENV=testing`, driver MySQL y base `kronik_test`. Se requieren esas condiciones
antes de invocarlo; no usar la base de `.env`. Prueba ocho competencias por
política: dos desembolsos, anticipo idempotente, reverso idempotente,
anticipo/liquidación con previas competidoras, referencia entre créditos y
pagos/reversos competidores. Ver [configuración CI](../reference/ci.md).

## Registrar el resultado

Compartir commit, entorno, fecha, navegador, políticas recorridas, productos/
folios sintéticos y resultado o error concreto. Conservar capturas de ambas
políticas y sus recibos/calendarios. Actualizar Agent Note y Notion diferenciando
implementado, integrado, desplegado y validado. No cerrar la tarea principal solo
por el merge: quedan pendientes despliegue y QA hasta recibir su evidencia.

## Recorrido con fechas pasadas

La [guía de desembolsos históricos](qa-desembolsos-historicos.md) complementa el caso de hoy con vigencia/fecha efectiva pasada, registro cronológico y captura incompleta. Permite reconstruir atrasos con reloj real y documentos actuales conservados; no adelantar el reloj ni cambiar condiciones firmadas. Las pruebas aisladas mantienen sus fronteras temporales exactas.
