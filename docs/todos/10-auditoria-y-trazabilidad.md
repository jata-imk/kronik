# TODO 10: Auditoria y trazabilidad

## Objetivo

Sustituir el log demostrativo por una politica de eventos auditables que cubra solicitantes, clientes, usuarios internos, integraciones y procesos automaticos.

## Actores

- Solicitante o cliente: sujeto afectado, aunque no tenga una sesion en Kronik.
- Usuario interno: administrador o empleado autenticado.
- Sistema: proceso programado, cola, integracion o API.

## Matriz de eventos

- [x] Inicio de sesion y segundo factor.
- [ ] Cierre de sesion y fallos de acceso.
- [ ] Altas, cambios, bajas, roles y permisos de usuarios.
- [x] Cambios de perfil de usuario.
- [x] Configuracion de empresa y sucursales.
- [x] Clientes y cambios de sus campos, sin copiar valores sensibles al log.
- [x] KYC, referencias, garantias, documentos y consentimientos.
- [ ] Creacion y transiciones de solicitudes de credito.
- [ ] Decisiones, autorizaciones, rechazos y motivos.
- [x] Consultas SIC realizadas, sin guardar credenciales ni respuestas.
- [ ] Contratos, firmas, desembolsos, pagos y cancelaciones.

## Contrato minimo de evento

- Actor, tipo de actor y usuario interno cuando aplique.
- Sujeto o recurso afectado.
- Accion y resultado.
- Fecha, IP, agente de usuario e identificador de peticion.
- Motivo operativo cuando la accion lo requiera.
- Valores anteriores y posteriores con datos sensibles censurados.

La matriz implementada y su contrato se documentan en [Eventos de auditoria](../reference/auditoria-eventos.md).

## Reglas

- [ ] Retencion y eliminacion.
- [x] Permisos de consulta y exportacion aislados por equipo, con acceso global para `Super Admin`.
- [ ] Integridad y deteccion de alteraciones.
- [x] Busqueda por actor, sujeto, evento y periodo.
- [x] Minimizacion tecnica: lista cerrada de metadatos y nombres de campos sin valores.
- [ ] Alertas para eventos de seguridad.

Nunca registrar contraseñas, tokens, API keys, secretos, respuestas SIC completas ni contenido completo de documentos.

## Dependencias

- TODO 04: originacion y solicitudes.
- TODO 05: CDC, score y SIC.
- TODO 06: PLD y cumplimiento.
- TODO 07: amortizacion, pagos y cobranza.

Los emisores existentes ya usan eventos tipados y la tabla administrativa consume el backend. La ampliacion a los modulos futuros debe seguir el contrato de referencia y actualizar esta matriz.
