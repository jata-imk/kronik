# TODO 05: Círculo de Crédito, Score y SIC

## Objetivo

Formalizar la integración SIC para que use datos reales del expediente y genere resultados auditables.

## Estado Actual

Existen seeders para Buró de Crédito y Círculo de Crédito, APIs de CDC, modelos `SicQuery` y `SicQueryResult`, vistas de historial crediticio y servicios/repositories para varias consultas.

## TODO

- [ ] Construir request data desde `Cliente`, datos fiscales y domicilio real.
- [ ] Exigir consentimiento vigente antes de llamar una SIC.
- [ ] Guardar folio, proveedor, API, usuario, fecha, request mínimo auditable y response cruda.
- [ ] Normalizar resultados clave: score, razones, cuentas, consultas, mora y alertas.
- [ ] Manejar errores de validación, autenticación, timeout y respuesta incompleta.
- [ ] Separar sandbox/producción por configuración de financiera.
- [ ] Cifrar o proteger credenciales de CDC.
- [ ] Agregar pruebas con respuestas fake y sin llamadas externas.
