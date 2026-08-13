# Productos crediticios

## Alcance V1

El catálogo es global para la instalación. Crédito simple admite montos, tasas anuales, días de tolerancia de mora, periodicidades semanal/quincenal/mensual, cuota nivelada o capital fijo, prepago, liquidación y comisiones del catálogo interno. Crédito revolvente permanece diferido.

## Convenciones

- Dinero decimal; presentación y cuotas half-up a centavos.
- Tasas como porcentaje nominal anual, nunca fracción ni `float`.
- Interés días reales/360; mora sobre capital vencido.
- Plazo como número de pagos.
- Fechas +7 días, +15 días o ancla mensual. V1 no ajusta inhábiles.
- Comisión fija o porcentual; V1 no la financia ni agrega IVA.

## Versiones

`borrador → programada/activa → retirada`. Solo el borrador sin uso es editable. `productos:activar-versiones` procesa vigencias diariamente.

Backlog 04 debe llamar `ProductoVersionService::registrarUso($version, 'solicitudes', $id)` dentro de la transacción de captura.

## CAT

El simulador lo muestra solo cuando la versión indica que aplica. Incluye pagos y comisiones obligatorias del escenario. Los cargos por eventos no ocurridos quedan fuera. Es informativo, sin IVA y para comparación; requiere revisión legal antes de uso contractual o publicitario.

## Permisos

`read`, `create`, `update`, `activate`, `retire`, `version`, `simulate` y `manage commissions` sobre `productos-crediticios`.
