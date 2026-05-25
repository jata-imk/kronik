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

- [ ] Diseñar tablas de productos y versiones de producto.
- [ ] Separar parámetros comerciales de reglas de cálculo.
- [ ] Crear CRUD de productos por financiera.
- [ ] Crear simulador de crédito simple.
- [ ] Calcular CAT informativo para productos donde aplique.
- [ ] Validar comisiones contra catálogo configurado.
- [ ] Bloquear edición destructiva de productos ya usados; usar versionado.
