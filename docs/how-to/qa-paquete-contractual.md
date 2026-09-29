# Probar el paquete contractual QA (P4a)

Continuación P4b: la pantalla se llama **Contrato y tabla de pagos**. La recepción
y revisión manual de firma se prueban con el [guion de firma](qa-firma-digitalizada.md).
Los límites siguientes describen el incremento P4a original, no toda la aplicación.

## Alcance

Desde una solicitud aprobada se prepara un PDF privado con una plantilla de
contrato y su anexo informativo. Conserva las versiones/datos utilizados y muestra
**Documento de prueba QA — sin validez contractual**. Firma, crédito, desembolso,
impuestos operativos y pagos todavía no forman parte de este incremento.

## Preparar la instalación QA

Aplicar el despliegue habitual con respaldo, mantenimiento, dependencias y build.
Dentro del servicio PHP del Compose utilizado por la instalación:

```sh
php artisan migrate --force
php artisan db:seed --class=ModulesAndPermissionsSeeder --force
php artisan permission:cache-reset
```

Este seeder agrega el permiso `prepare package solicitudes`; no asigna roles.
No ejecutar `migrate:fresh`, seeders de desarrollo/E2E ni restablecer menús en la VPS.
La migración crea `solicitud_paquetes` y conserva sus relaciones. Si hay paquetes,
su rollback se bloquea: restaurar un respaldo coherente en lugar de borrar evidencia.

En la configuración **exclusivamente QA**, habilitar explícitamente:

```dotenv
ORIGINACION_APROBACIONES_HABILITADAS=true
ORIGINACION_PAQUETES_QA_HABILITADOS=true
```

Si Compose entrega estas variables al contenedor, recrear los servicios afectados;
editar un archivo que no consume el contenedor no cambia su configuración. Luego:

```sh
php artisan optimize
php artisan queue:restart
php artisan up
```

El worker necesita la misma configuración, clave de cifrado y almacenamiento
privado que el servicio web, además del Chromium/Node requerido por Documentos.
No modificar la clave de cifrado al desplegar. Supervisar que el gestor de procesos
reinicie el worker después de `queue:restart`.

Asignar al operador: `read clientes`, `read solicitudes`, `read documentos`,
`generate documentos`, `prepare package solicitudes`; para descargar, también
`download documentos`. Seleccionar la sucursal responsable y comprobar que siga
activa. El administrador global tampoco omite ese control de sucursal.

Crear/activar una plantilla **de prueba** de tipo Contrato en Documentos y
plantillas. Estar activa no representa aprobación jurídica. Las variables
permitidas siguen siendo las existentes; las condiciones financieras se agregan
en el anexo, no mediante nuevas variables arbitrarias.

## Guion funcional

1. Usar un escenario sintético nuevo, sin alterar la solicitud manual ya revisada.
   Completar cliente PF, expediente, solicitud, evaluación, PLD y aprobación.
2. Entrar a **Solicitud → Paquete contractual QA**. Comprobar cliente/sucursal,
   requisitos fiscales y enlaces de regreso/expediente. Para nuevos paquetes,
   usar un producto con fiscalidad definida de prueba en ordinarios y comisiones
   aplicadas. Si falta, la pantalla debe orientar a crear una nueva versión y
   devolver/corregir/aprobar la solicitud; no editar una versión ya utilizada.
3. Preparar: elegir contrato activo y confirmar uso QA. Omitir la confirmación
   primero: debe aparecer un error claro en español sin crear paquete.
4. Confirmar una vez. La solicitud sigue aprobada, no formalizada. Durante la
   generación se muestra cola/proceso; el sondeo termina o permite actualizar si
   vence su espera. Con cola síncrona el PDF puede estar listo inmediatamente.
5. Abrir **Ver PDF QA**, comprobar encabezado/marca en todas las páginas,
   variables de cliente, tasas, número de pagos, tabla, fechas estimadas y aviso
   de proyección fiscal QA, impuestos y desglose por periodo/concepto. Comparar
   con el simulador usando iguales condiciones e impuestos habilitados. La fila
   cero no es un desembolso registrado ni suma otra vez los impuestos financiados.
6. Repetir la petición o recargar no debe producir otro paquete de la misma
   aprobación. Cambiar productos/plantillas después no modifica el PDF original.
7. Verificar permisos con un lector sin preparación; debe consultar pero no generar.
   Sin lectura de solicitudes, debe negarse también estado, visor y descarga directa.
   Sin permiso de descarga, no debe ofrecerse descargar desde el visor.
8. Usar otra sucursal: debe bloquear preparación y explicar cómo corregirla.
   Deshabilitar la bandera QA debe bloquear nuevas preparaciones, no borrar historia.
9. En un escenario separado, cambiar evidencia o usar aprobación vencida: bloquear
   preparación y orientar a devolver/reenviar. No intentar corregir editando el PDF.
10. Devolver, corregir, reenviar, registrar dictámenes y aprobar nuevamente. El
    siguiente paquete debe conservarse separado del histórico, con otra huella.
11. Simular fallo del renderizador únicamente en entorno controlado. Tras reparar
    el servicio, **Reintentar PDF** usa los mismos datos congelados. No debe habilitarse
    para un documento en cola o que ya produjo original. Un archivo original perdido
    se restaura desde respaldo; no se reconstruye silenciosamente.
12. Abrir un paquete P4a anterior: debe conservar importes anteriores a impuestos,
    huella y PDF. Reintentar un fallo conserva ese mismo formato. No se migra a
    proyección fiscal: para otro paquete se requiere una nueva aprobación.

La integración fiscal no añade migraciones, permisos ni banderas. La marca QA
permanece incluso si la configuración fiscal declara uso institucional; esto no
certifica el contrato. Firma y formalización siguen pendientes. La revisión
profesional para operación real está en [QA fiscal](qa-simulacion-fiscal.md).

El listado genérico del expediente no mezcla estos paquetes con documentos
operativos. Se consultan desde su solicitud para conservar contexto y permisos.

## Referencias visuales

Capturas con datos sintéticos: [escritorio](../assets/qa-paquete-desktop.png) y
[móvil](../assets/qa-paquete-mobile.png). No contienen información de la VPS.

Integración fiscal: [escritorio](../assets/paquete-fiscal-desktop.png) y
[móvil](../assets/paquete-fiscal-mobile.png), con desglose abierto y datos sintéticos.

## Pruebas aisladas para desarrollo

```sh
php artisan test --filter=paquete
npm run test:unit -- resources/js/Pages/Solicitudes/Paquete.test.js
```

En PowerShell, después de `npm run build`:

```powershell
$env:E2E_ORIGINACION_DUAL='true'
npm run test:e2e -- solicitudes-dual.spec.js
Remove-Item Env:\E2E_ORIGINACION_DUAL
```

El runner prepara exclusivamente su base E2E y almacén de pruebas. Incluye
capturista y aprobador normales, separación dual, preparación y visor del paquete.
No ejecutar backend y E2E simultáneamente en esta instalación Windows.
