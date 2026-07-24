# TODO 10: Auditoria y trazabilidad

## Objetivo

Sustituir el log demostrativo por una politica de eventos auditables que cubra solicitantes, clientes, usuarios internos, integraciones y procesos automaticos.

## Actores

- Solicitante o cliente: sujeto afectado, aunque no tenga una sesion en Kronik.
- Usuario interno: administrador o empleado autenticado.
- Sistema: proceso programado, cola, integracion o API.

## Matriz pendiente de eventos

- [ ] Autenticacion, cierre de sesion y fallos de acceso.
- [ ] Altas, cambios, bajas, roles y permisos de usuarios.
- [ ] Configuracion de empresa y sucursales.
- [ ] Datos personales, fiscales y domicilios.
- [ ] KYC, referencias, garantias, documentos y consentimientos.
- [ ] Creacion y transiciones de solicitudes de credito.
- [ ] Decisiones, autorizaciones, rechazos y motivos.
- [ ] Consultas SIC y recepcion de resultados, sin guardar credenciales.
- [ ] Contratos, firmas, desembolsos, pagos y cancelaciones.

## Contrato minimo de evento

- Actor, tipo de actor y usuario interno cuando aplique.
- Sujeto o recurso afectado.
- Accion y resultado.
- Fecha, IP, agente de usuario e identificador de peticion.
- Motivo operativo cuando la accion lo requiera.
- Valores anteriores y posteriores con datos sensibles censurados.

## Reglas por definir

- [ ] Retencion y eliminacion.
- [ ] Permisos de consulta y exportacion.
- [ ] Integridad y deteccion de alteraciones.
- [ ] Busqueda por actor, sujeto, evento y periodo.
- [ ] Tratamiento de datos personales y minimizacion.
- [ ] Alertas para eventos de seguridad.

Nunca registrar contraseñas, tokens, API keys, secretos, respuestas SIC completas ni contenido completo de documentos.

## Dependencias

- TODO 04: originacion y solicitudes.
- TODO 05: CDC, score y SIC.
- TODO 06: PLD y cumplimiento.
- TODO 07: amortizacion, pagos y cobranza.

El diseño de esta matriz debe aprobarse antes de reemplazar los mocks por eventos considerados definitivos.
