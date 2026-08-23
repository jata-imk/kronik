# Backlog 03 - Productos crediticios

## Referencias

- Notion: https://app.notion.com/p/3a061db7db7f8123a76dc9e1b2271bac
- Rama: `feat/backlog-03-productos-crediticios`
- PR: https://github.com/jata-imk/kronik/pull/14
- ADR relacionados: ADR 0001, ADR 0006 y ADR 0007

## Objetivo

Implementar crédito simple V1 configurable, versionado, simulable y protegido contra cambios históricos.

## Estado actual

- Estado: ajustes finales de revisión manual implementados y validados
- Última actualización: 2026-08-22
- Último punto estable: reglas de CAT/opcionales, activación empresarial y explicación matemática implementadas; suite completa, E2E y recorrido visual correctos.

## Decisiones

- Se mantiene V1 en semanal, quincenal y mensual; los catálogos SIC no definen automáticamente las periodicidades contractuales.
- Se mantiene días reales/360.
- Las comisiones iniciales admiten pago separado, descuento de disposición o financiamiento. La modalidad queda en el snapshot.
- El límite del producto se aplica al saldo total financiado; los porcentajes se calculan sobre el monto solicitado.
- El detalle de fórmulas es visible en local o para Super Admin.
- `Incluir en CAT base` se conserva visible, pero es derivado por backend según obligatoriedad y momento; no es un parámetro manipulable.
- Las opcionales determinísticas pueden incluirse en el simulador sin alterar el CAT base. Evento y liquidación requieren escenarios futuros.
- Programar congela la versión al confirmar; su entrada en vigor usa fecha y zona horaria empresarial. Retirar no afecta créditos existentes.

## Evidencia

- Prueba de regresión: $5,000, 36 %, mensual, 12 pagos, disposición 2026-08-16, apertura $500 y administración 1 %. Primera mensualidad: capital $348.64, interés $155.00, comisión $50.00, pago $553.64 y saldo $4,651.36. Totales: interés $1,043.68, comisiones $1,100.00 y obligaciones $7,143.68.
- El motor puro cubre calendario, cuota nivelada, capital fijo, redondeo por periodo y residual final.
- Las tres modalidades iniciales tienen regresiones separadas para saldo financiado, efectivo entregado, flujo neto y pago inicial.
- La interfaz muestra errores por pestaña/campo, toasts del catálogo, ayudas financieras, fila cero, saldos, desglose de comisiones y acumulados.
- Pruebas: Laravel 139/139 (713 aserciones); Vitest 38/38; E2E específico 2/2; build Vite, Pint, Biome y `git diff --check` correctos.
- Migración aditiva `2026_08_16_120000_add_modalidad_cobro_to_producto_version_comisiones.php` aplicada en desarrollo sin seeders generales.
- Regresiones nuevas: matriz CAT derivada, payload manipulado, opcional financiada con CAT base estable, selección inválida, fechas 28/29/30/31, activación programada y filtro de originación.
- Verificación final 2026-08-22: suite Laravel 139/139 (713 aserciones), Vitest 38/38, E2E Chromium escritorio/móvil 2/2 y build Vite correcto.

## Hallazgos visuales

- Chrome/Playwright: catálogo, simulador y errores revisados a 1440×1000 y 390×844. Los controles se apilan en móvil y la tabla conserva scroll interno.
- La clave duplicada permanece en el diálogo, marca `Clave` como inválida y muestra el mensaje español junto al campo.
- El alta incompleta selecciona la primera pestaña con error, muestra contadores por pestaña y conserva los mensajes al navegar. La revisión descubrió y corrigió una clave cruda `validation.gte.numeric`.
- Al redimensionar con un Drawer abierto, PrimeVue emitió su error interno de realineación de overlay; no hubo fallo de petición ni de cálculo y el E2E en viewports independientes quedó verde.

## Bloqueos

- Ninguno de negocio.

## Siguiente paso

Actualizar el PR #14 con estos ajustes cuando se solicite el commit y push.

## Cierre

- Commit base: `100dc06`; PR #14 abierto.
- Pendientes diferidos: Backlog 02.5, crédito revolvente V2, IVA y calendario de días inhábiles.
