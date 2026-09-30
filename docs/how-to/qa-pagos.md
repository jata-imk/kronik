# Probar pagos manuales QA

Esta guía es documentación, no un módulo. Los recibos sí forman parte de la app.
No prueba autorización para dinero real ni sustituye la revisión fiscal/contractual.

## Preparación

1. En la instalación **QA**, aplicar las migraciones aditivas de P6 y ejecutar
   `php artisan db:seed --class=ModulesAndPermissionsSeeder` para crear permisos.
   No asigna automáticamente permisos a usuarios normales.
2. Asignar lectura de clientes/solicitudes/créditos, `pay creditos` a quien registra
   y, por separado, `reverse payments creditos` a quien corrige.
3. Habilitar `ORIGINACION_PAGOS_QA_HABILITADOS=true` solo en QA y reconstruir la
   caché de configuración. Mantener los controles anteriores de firma/desembolso.
4. Crear una versión de producto QA con fiscalidad explícita para ordinario,
   moratorio y cada comisión; definir política de atraso. Los créditos anteriores
   sin esta evidencia permanecen bloqueados: no editar sus snapshots para probar.
5. Crear una nueva solicitud, aprobar, preparar contrato, recibir/revisar firma y
   desembolsar en la fecha firmada. Seguir `qa-desembolso.md` para esos pasos.

## Registro y comprobantes

- Abrir Crédito → Registrar pago QA. Antes del primer vencimiento solo se pueden
  cubrir cargos ya exigibles, por ejemplo una comisión inicial de pago separado.
  No inventar fecha pasada ni adelantar vencimientos para probar capital.
- Capturar importe, fecha efectiva y referencia sintéticos. Revisar distribución:
  cuota antigua primero; comisión → moratorio → ordinario → capital. Conceptos
  gravados incluyen impuesto proporcional según su propia configuración.
- Ejemplo: comisión QA $500 + impuesto $80; abono $290 → $250 y $40. El capital
  no cambia al pagar esa comisión. Sin comisión y antes del vencimiento puede no
  existir deuda exigible; no equivale a tener capital liquidado.
- Cambiar importe, referencia o fecha: desaparece la previa y exige nueva revisión.
- Confirmar: debe abrir un recibo interno con asignaciones, fecha efectiva/captura
  y referencia. Volver al crédito: historial y situación deben reflejar el pago.
- Reintentar el mismo envío no crea otro pago. Una referencia ya utilizada se
  rechaza; no inventar otra para duplicar una recepción existente.

## Bloqueos y corrección

- Fecha futura, anterior al desembolso o con movimientos posteriores: rechazo sin
  modificar saldo. Dos operaciones del mismo día sí pueden registrarse en orden.
- Excedente: rechaza el importe completo; no recorta ni genera anticipo/saldo libre.
- B + sustitución durante gracia: rechaza parcialidad de la cuota afectada; admite
  cubrirla completa. No cambiar fecha/importes reales para evadirlo.
- Dos operadores revisan y uno confirma primero: el otro debe renovar su previa.
- Con permiso independiente, abrir el último recibo → Revertir último pago,
  explicar motivo y confirmar QA. Debe conservar el original y enlazar compensación.
  No borra movimientos, no devuelve dinero y no permite corregir pagos anteriores
  si ya hay historia posterior. Una referencia revertida sigue reservada.
- Probar usuario lector sin permiso, otra sucursal y bandera cerrada: no permitir
  registro ni reverso. No debe mostrarse una clave `validation.*` como error.

## Qué no cubre esta entrega

Anticipos/liquidación anticipada, cobranza ampliada, compensación automática de
ordinario/impuesto pagados en B + sustitución, conciliación/CFDI y dinero real.
El calendario firmado nunca se reemplaza por el saldo calculado. La situación se
calcula al consultar; el libro contabiliza devengos al aplicar pagos.

## Capturas de la prueba automatizada

[Vista previa escritorio](../assets/qa-pagos/pago-qa-desktop.png),
[captura móvil](../assets/qa-pagos/pago-qa-mobile.png) y
[recibo móvil](../assets/qa-pagos/recibo-qa-mobile.png).
Las tablas permiten desplazamiento horizontal para consultar todos los importes.
