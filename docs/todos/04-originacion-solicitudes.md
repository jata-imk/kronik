# TODO 04: Originación y Solicitudes

## Objetivo

Implementar el flujo desde solicitud hasta autorización o rechazo.

## Flujo Base

1. Seleccionar o crear cliente.
2. Elegir producto y monto solicitado.
3. Validar expediente mínimo.
4. Registrar consentimiento SIC.
5. Consultar score/historial.
6. Evaluar capacidad de pago.
7. Generar dictamen.
8. Aprobar, rechazar o pedir información.
9. Generar contrato.
10. Registrar firma y desembolso.

## TODO

- [ ] Diseñar modelo de `solicitudes_credito`.
- [ ] Definir estados: borrador, en revisión, pendiente documentos, aprobada, rechazada, cancelada, desembolsada.
- [ ] Asociar solicitud con cliente, producto, asesor, sucursal/equipo y consultas SIC.
- [ ] Crear checklist documental por producto.
- [ ] Agregar dictamen manual con comentarios y adjuntos.
- [ ] Preparar punto de extensión para reglas automáticas de decisión.
- [ ] Crear vistas de bandeja, detalle y captura.
- [ ] Agregar pruebas de transiciones de estado.
