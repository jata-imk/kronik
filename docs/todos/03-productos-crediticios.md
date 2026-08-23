# TODO 03: Productos Crediticios

## Objetivo

Crear productos configurables para que cada financiera adapte su oferta.

## Crédito Simple V1

- Monto mínimo/máximo.
- Plazo mínimo/máximo.
- Periodicidad: semanal, quincenal, mensual.
- Tasa ordinaria.
- Tasa moratoria.
- Comisiones: apertura, administración, cobranza u otras.
- Días de gracia.
- Método de amortización.
- Reglas de prepago y liquidación anticipada.

## Crédito Revolvente V2

- Línea autorizada.
- Límite disponible.
- Disposiciones.
- Fecha de corte.
- Fecha límite de pago.
- Pago mínimo.
- Interés por saldo.
- Comisiones y cargos.
- Estado de cuenta.

## TODO

- [x] Diseñar tablas de productos y versiones de producto.
- [x] Separar parámetros comerciales de reglas de cálculo.
- [x] Crear CRUD de productos por financiera.
- [x] Crear simulador de crédito simple.
- [x] Calcular CAT informativo para productos donde aplique.
- [x] Validar comisiones contra catálogo configurado.
- [x] Bloquear edición destructiva de productos ya usados; usar versionado.
