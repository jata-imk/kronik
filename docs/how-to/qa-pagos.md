# Pagos manuales QA: pruebas detalladas

Esta es una guía **complementaria**. Si es tu primera vez en Kronik, empieza por el
[guion principal de la vida de un crédito simple](qa-credito-simple.md), que explica
cómo llegar hasta un crédito desembolsado. Aquí se amplía únicamente la etapa de
pagos y reversos. No registra ni devuelve dinero real.

## Preparar el caso

Necesitas un crédito en modo QA, sucursal activa correcta, fiscalidad explícita de
interés ordinario y moratorio, política de mora conservada en su versión y la puerta
`ORIGINACION_PAGOS_QA_HABILITADOS=true` en la instalación de prueba. El operador
requiere `read clientes`, `read solicitudes`, `read creditos` y `pay creditos`.
Para el reverso, concede `reverse payments creditos` a la cuenta que lo probará.

En el día del desembolso no hay cuotas futuras exigibles. Para registrar un pago
inmediato, el producto debe incluir desde el inicio una comisión inicial de
**pago separado**. Ese pago no reduce el capital. Para pagar capital e interés de
una cuota, espera a su vencimiento; no cambies fechas ni el reloj para simularlo.

## Registrar y comprobar el recibo

1. Abre **Créditos → crédito QA → Registrar pago QA**. Captura importe, fecha
   efectiva (entre desembolso y hoy) y referencia sintética única.
2. Revisa la previa: cuota más antigua primero; dentro de cada cuota, comisión →
   moratorio → ordinario → capital. El impuesto de cada concepto gravado se cobra
   proporcionalmente al abono de ese concepto, según la fiscalidad del producto.
3. Ejemplo exclusivamente de prueba: una comisión de $500 con impuesto configurado
   de $80 admite abono de $290, asignando $250 a comisión y $40 al impuesto. No
   interpretes ese porcentaje como tratamiento fiscal universal.
4. Cambia importe, fecha o referencia: la previa debe quedar obsoleta y pedir otra
   revisión. Confirma una vez y abre el recibo interno; coteja referencia,
   asignaciones, fechas efectiva/de captura, historial y situación actual.
5. Reintenta exactamente el mismo envío: no debe crear otro pago. Una referencia
   ya usada se rechaza incluso si su pago fue revertido.

## Bloqueos y corrección

- Fecha futura o anterior al desembolso: rechazo sin movimientos. Una fecha pasada
  también se rechaza si existen movimientos posteriores; prueba esto solo cuando
  tengas un caso con historia en días distintos.
- Excedente respecto de lo exigible: rechazo completo; no crea anticipo ni saldo a
  favor. Si todavía no hay concepto exigible, no habrá un pago de cuota que registrar.
- Política B + moratorio que sustituye al ordinario: durante la gracia se bloquea
  la parcialidad de la cuota afectada, incluidos conceptos e impuestos; cubrirla
  completa sí es posible. La compensación trazable sigue pendiente.
- Dos operadores revisan la misma deuda y uno confirma primero: el segundo debe
  renovar su previa antes de confirmar.
- Con `reverse payments creditos`, abre el último recibo, indica motivo y confirma
  reverso QA. El pago original permanece y se enlaza a movimientos compensatorios.
  Solo se revierte el último pago permitido, sin movimientos posteriores; el
  sistema no ejecuta reembolso.
- Un usuario sin `pay creditos`, sin lectura o en otra sucursal no registra pagos.
  Prueba también el acceso por URL directa; los errores visibles deben estar en
  español, sin claves `validation.*`.

El calendario firmado no es el saldo exigible calculado al día. El libro registra
devengos al aplicar pagos; consultar el crédito no crea movimientos. Anticipos,
liquidación anticipada, conciliación bancaria, CFDI y dinero real quedan fuera de
esta entrega. Consulta la [decisión técnica de pagos QA](../explanation/adr-0020-pagos-manuales-qa.md).

Capturas sintéticas: [previa en escritorio](../assets/qa-pagos/pago-qa-desktop.png),
[captura móvil](../assets/qa-pagos/pago-qa-mobile.png) y
[recibo móvil](../assets/qa-pagos/recibo-qa-mobile.png).
