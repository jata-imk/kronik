# Probar anticipos y liquidación QA

Crédito desembolsado QA, fiscalidad/mora definidas, sucursal actual y lectura de
cliente/solicitud/crédito. Habilitar pagos y `ORIGINACION_ANTICIPOS_QA_HABILITADOS=true`.
Permisos: `prepay creditos`, `settle creditos`, `reverse payments creditos`.

1. Abrir **Anticipos y liquidación**: operación, fecha efectiva, total recibido y
   referencia única. Liquidación permite importe vacío para cotizar; al confirmar
   exige coincidencia exacta con el total devuelto.
2. Revisar capital aplicado, devengos, impuestos y cargos del evento. El mínimo
   contractual aplica al capital anticipado. Las comisiones opcionales requieren
   selección compatible con el evento.
3. Revisar cuotas/gráfica de capital: supone pagos futuros puntuales; no representa
   movimientos ya registrados.
4. Cambiar captura elimina previa. Otro movimiento también la invalida al confirmar.
5. Confirmar QA; verificar recibo, referencia, distribución, historial y selector
   de calendarios. El original y sus versiones posteriores permanecen.
6. Cotizar/confirmar liquidación: **Liquidado · QA**, saldo cero, sin cuotas futuras
   ni enlace para pagos nuevos.
7. Revertir última liquidación con motivo: compensación enlazada, crédito activo y
   calendario restaurado con nueva versión.

Probar rechazo de importe distinto de cotización, mínimo incumplido, comisiones
heredadas ambiguas y falta de permisos. No modificar reloj ni contrato para
facilitar pruebas. Una referencia no se reutiliza aunque se revierta la operación.

No ejecuta transferencias, reembolsos ni CFDI. Política B + sustitución conserva
su bloqueo durante gracia. [ADR 0023](../explanation/adr-0023-anticipos-liquidacion-qa.md).
Capturas: [escritorio](../assets/anticipo-qa-desktop.png),
[móvil](../assets/anticipo-qa-mobile.png).
