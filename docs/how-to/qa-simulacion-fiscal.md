# Probar la simulación con impuestos y preparar la revisión profesional

Esta guía complementa la [prueba integral del crédito simple](qa-credito-simple.md).
Se centra en la **simulación fiscal QA** y en las preguntas para el contador. La
simulación no equivale a firma, desembolso, pago ni operación real; esas etapas QA
se prueban por separado. No agrega migraciones; si vienes de antes de PR #24,
aplica sus migraciones con el procedimiento habitual, sin reinicializar la base.

El 16 % de los ejemplos siguientes es una **configuración ficticia para comprobar
cálculos**, no una tasa que el sistema deba aplicar a todos los intereses o
comisiones. La institución y su asesor deben definir cada concepto por separado.

## Recorrido de prueba

1. Crear un producto de prueba o duplicar uno existente. En Fiscalidad, marcar
   Prueba / QA, definir ordinario gravado al 16 % sobre importe del concepto y
   moratorio sin definir. El moratorio no interviene en esta tabla sin atraso.
   **Este borrador sirve solo para el simulador**: antes de usar una versión para
   el recorrido hasta pagos, define también la fiscalidad moratoria.
2. Simular $10,000, mensual, tres pagos, capital fijo, tasa ordinaria anual 36 %,
   disposición 01/01/2026, sin comisiones. Activar Incluir impuestos proyectados.
3. La primera cuota debe mostrar interés $310.00, impuesto $49.60 y pago $3,692.93.
   El capital no aparece como base del impuesto. El saldo final de la última
   cuota debe ser cero y el total fiscal debe conciliar con sus filas.
4. Abrir el desglose por periodo/concepto; comprobar tratamiento, base e importe.
5. Cambiar el ordinario a Sin definir en un borrador: no debe producir un total
   fiscal. Debe explicar qué concepto definir y conservar el formulario.
6. Desmarcar impuestos: mostrar aviso Antes de impuestos, no un total fiscal cero.
   Cambiar cualquier dato debe retirar el resultado anterior hasta volver a calcular.
7. Probar una comisión de $100 gravada al 16 %, con interés ordinario exento y tasa
   ordinaria cero para facilitar la conciliación. Con $10,000 solicitados:

| Modalidad | Saldo financiado | Efectivo | Pago separado inicial | Impuesto total |
| --- | ---: | ---: | ---: | ---: |
| Financiada | $10,116 | $10,000 | $0 | $16 |
| Descuento del desembolso | $10,000 | $9,884 | $0 | $16 |
| Pago separado | $10,000 | $10,000 | $116 | $16 |

8. El impuesto financiado no debe reaparecer como impuesto adicional en cada
   pago. Con tasa ordinaria 36 %, el primer interés sobre $10,116 es $313.60;
   si está gravado al 16 %, su impuesto es $50.18.
9. Probar comisión periódica opcional sin definir: mientras no se seleccione no
   bloquea; seleccionada debe bloquear. Definirla gravada permite calcularla por
   cuota. Comprobar independencia de otras comisiones exentas/no causantes.
10. Comparar CAT base antes/después: mantiene el escenario base existente, separado
    de esta proyección fiscal. Verificar escritorio y móvil, errores y reintento.

## Lista para contador o asesor fiscal — antes de operación real

No necesitas cerrar estos puntos para continuar QA. Al solicitar revisión, entrega
la versión exacta del producto, esta tabla, la configuración fiscal y un escenario
numérico completo; conserva el documento de respaldo de la institución.

| Tema | Qué pedir que confirme |
| --- | --- |
| Aplicabilidad | Régimen y características de la institución, producto y operación que determinan el tratamiento de cada interés/comisión. |
| Tratamiento | Gravado, exento o no causa para ordinario, moratorio y cada comisión por separado; fundamento y vigencia. |
| Base y tasa | Si procede importe íntegro del concepto y qué tasa corresponde. Bases especiales requieren ampliar el motor, no aproximarlas con capital o tasas arbitrarias. |
| Causación | Momento de generación/cobro relevante y tratamiento de pagos parciales, mora, anticipos, cancelaciones y reversos. La proyección por cuota no resuelve la contabilidad fiscal. |
| Comisiones financiadas | Procedencia de financiar su impuesto, interés sobre saldo financiado y presentación contractual. |
| Redondeos | Precisión, redondeo por concepto/periodo y conciliación contra comprobantes y contabilidad. |
| Cumplimiento fiscal | Comprobantes, retenciones tributarias, acreditamientos y declaraciones aplicables; no están implementados por esta proyección. |
| Información al cliente | Conciliación entre tabla, cargos, impuestos y presentación del CAT según el producto aplicable. |

El contenido contractual y su autorización requieren además revisión jurídica de
la institución. Guardar una referencia fiscal o activar una plantilla no equivale
a obtener esos vistos buenos. Registrar versión, responsable, fecha, fundamento y
restricciones antes de habilitar operación real.

## Evidencia técnica

- Motor: `ImpuestoConceptoService`, `SimuladorCreditoSimple`; ADR 0017.
- Backend: `php artisan test`.
- Frontend: `npm run test:unit`.
- Navegador aislado: `npm run test:e2e -- productos-crediticios.spec.js`.
- Paquetes contractuales QA existentes no cambian ni se regeneran.
- [Simulador en escritorio](../assets/simulacion-fiscal-desktop.png).
- [Resumen en móvil](../assets/simulacion-fiscal-mobile.png).
