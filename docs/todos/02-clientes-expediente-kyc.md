# TODO 02: Clientes, Expediente y KYC

## Objetivo

Extender el alta actual de clientes hacia un expediente completo de solicitante.

## Estado Actual

Ya existen clientes, datos fiscales, domicilios, país de nacimiento, teléfono, email, sexo y relación con consultas SIC.

## TODO

- [ ] Agregar campos de ocupación, actividad económica, ingresos, egresos y origen de recursos.
- [ ] Crear módulo de documentos por cliente: INE, comprobante de domicilio, constancia fiscal, comprobantes de ingreso y documentos adicionales.
- [ ] Registrar estado documental: pendiente, recibido, validado, rechazado y vencido.
- [ ] Agregar referencias personales y laborales.
- [ ] Agregar avales, obligados solidarios y garantías vinculadas a cliente/solicitud.
- [ ] Registrar consentimiento para consulta SIC con fecha, usuario, medio y evidencia.
- [ ] Corregir estrategia morph de `Cliente::getMorphClass()` antes de ampliar relaciones polimórficas.
- [ ] Agregar pruebas de creación/actualización de expediente completo.
