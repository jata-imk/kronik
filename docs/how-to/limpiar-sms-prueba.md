# Retirar SMS anteriores de desarrollo o QA

Este procedimiento acompaña la sustitución directa de Infobip por LabsMobile en
I1. Solo existen datos desechables de desarrollo/QA. No migra históricos ni
convierte configuraciones anteriores. Ver [contrato SMS](../reference/canales-comunicacion.md)
y [variables](../reference/variables-entorno.md#sms-labsmobile-i1).

## Preparación

1. Confirmar instalación, BD, conexiones de cola y naturaleza exclusivamente QA.
   En VPS ejecuta los comandos el usuario; el agente no accede por SSH.
2. Mantener `COMMUNICATIONS_SEND_ENABLED=false` y
   `COMMUNICATIONS_SMS_SEND_ENABLED=false` en la configuración efectiva, incluido
   su caché. Detener workers y programador. Fuera de `local/testing`, mantener la
   aplicación en mantenimiento durante la aplicación de la limpieza.
3. Aplicar previamente la migración aditiva I1 en ese entorno confirmado. En la
   entrega automatizada solo se migran bases aisladas de prueba.
4. Obtener el diagnóstico, que no cambia datos ni contacta proveedores:

```powershell
php artisan comunicaciones:limpiar-sms-prueba
```

El inventario `comunicaciones:inventariar-sms` es opcional; no constituye una
barrera adicional ni una prueba de históricos.

## Aplicación selectiva

Revisados los conteos y las condiciones anteriores:

```powershell
php artisan comunicaciones:limpiar-sms-prueba --apply --confirm-qa
```

La operación invalida salidas SMS sin proveedor propio LabsMobile, elimina las
configuraciones SMS anteriores y sus secretos sin descifrarlos, desactiva sus
reglas y retira callbacks Infobip. Conserva las filas de salida canceladas como
destinos inertes de jobs antiguos; no conserva un motor o compatibilidad Infobip.
No borra créditos, pagos, proyecciones, eventos compartidos ni consentimientos.

Solo elimina jobs pendientes/fallidos cuyos nombres de clase y `deliveryId`
identifican una salida anterior. Lee JSON y el entero público de la serialización
conocida; nunca deserializa ni ejecuta PHP. La eliminación y su auditoría comparten
la transacción de dominio. Las colas database en otra conexión, Redis, SQS y
Beanstalkd se conservan y se informan como no inspeccionadas. También se conservan
payloads desconocidos o ambiguos. No ejecutar `queue:clear`, `queue:flush` ni una
purga global como sustituto.

Un job conservado no puede reenviar una salida cancelada ni reinterpretar una
salida anterior como LabsMobile. Los jobs de correo y Meta permanecen. Los errores
no imprimen secretos, payloads ni excepciones de proveedor. Si falla la auditoría,
la limpieza revierte. Repetirla sin cambios no produce nuevas mutaciones ni eventos.

## Resultado

Revisar conteos y auditoría de cancelaciones, reglas y
`communication.sms_test_data_cleaned`. El ejecutor CLI se registra como sistema;
los registros mantienen equipo y, para salidas/jobs, sucursal original. No se
atribuye un actor de negocio a partir del usuario del sistema operativo.

Configurar LabsMobile mediante el entorno y crear de nuevo la configuración
operativa SMS en Comunicaciones. Las reglas quedan desactivadas hasta una decisión
explícita. Una comprobación de saldo no activa barreras ni confirma elegibilidad
o entrega. Mantener SMS cerrado: seguimiento I2 y piloto autorizado siguen pendientes.
