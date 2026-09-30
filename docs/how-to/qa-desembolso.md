# Probar crédito y desembolso manual QA

## Preparar

Seguir el despliegue habitual con respaldo, mantenimiento y migraciones.
Nueva migración: `2026_09_29_100000_create_creditos_tables`. Ejecutar el seeder
aditivo `ModulesAndPermissionsSeeder`, limpiar caché de permisos y asignar
`read creditos` / `disburse creditos` según rol. Lectura requiere además lectura
de solicitudes/clientes; desembolso requiere lectura documental.

Habilitar `ORIGINACION_DESEMBOLSOS_QA_HABILITADOS=true` solo en QA y reconstruir
la caché de configuración. No cambiar las demás puertas para eludir requisitos.
No ejecutar `migrate:fresh` ni fixtures E2E sobre la VPS.

## Recorrido

1. Crear una solicitud sintética con fecha estimada de hoy en la zona institucional.
   Completar aprobación y [firma QA](qa-firma-digitalizada.md).
2. Desde solicitud o Contrato y tabla de pagos abrir **Registrar desembolso QA**.
   Revisar cliente/sucursal, fecha, efectivo, capital financiado y retenciones.
3. Si la fecha firmada es futura, esperar. Si ya pasó o cambió, devolver y renovar
   revisión/aprobación/contrato/firma. No manipular fechas del servidor ni documentos.
4. Abrir confirmación. Fecha/importe vienen del contrato y no se recapturan.
   Escribir referencia única del comprobante sintético y confirmar QA.
5. Esperar **Crédito CR-n / Desembolso registrado**. Debe haber un crédito,
   cronograma versión 1 y movimiento inicial. No se llamó al banco.
6. Regresar a solicitud: estado **Desembolsada · QA** y acceso al crédito.
   No debe permitir cancelar/devolver. Ya no aparece como pendiente por defecto.
7. En Créditos buscar nombre o número sin prefijo CR-, filtrar sucursal y paginar.
   No confundir capital inicial con saldo actualizado ni calendario con devengo.
8. Reintentar el mismo envío: mismo crédito, ningún movimiento adicional. Un nuevo
   envío o referencia previamente registrada se rechaza, sin registros parciales.
9. Lector no registra; usuario sin lectura no accede por URL. Cambiar sucursal
   bloquea incluso a Super Admin. Requisitos obsoletos o archivos alterados impiden registrar.
10. Cambiar producto/plantilla posteriormente no modifica el crédito ni cronograma.

## Qué no hace todavía

Capturas del recorrido automatizado: [desembolso](../assets/desembolso-qa-desktop.png),
[crédito en escritorio](../assets/credito-qa-desktop.png) y
[crédito en móvil](../assets/credito-qa-mobile.png).

No ejecuta transferencias, no acredita contenido contractual ni fiscalidad y no
ofrece pagos/reversos. Los cargos separados iniciales no se marcan cobrados.
P6 agregará devengo y pagos; no usar esta proyección como saldo exigible.

## Verificación aislada

Ejecutar backend y navegador secuencialmente:

```sh
php artisan test
npm run test:unit
npm run build
```

```powershell
$env:E2E_ORIGINACION_DUAL='true'
npm run test:e2e -- solicitudes-dual.spec.js
Remove-Item Env:\E2E_ORIGINACION_DUAL
```
