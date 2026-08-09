# Eventos de auditoría

## Alcance actual

La tabla `activity_log` contiene eventos reales emitidos por autenticación, perfil de usuario, configuración de empresa, sucursales y el expediente de clientes. La interfaz administrativa consulta estos registros con filtros, paginación y exportación CSV.

Los procesos futuros de originación, decisiones, contratos, desembolsos, pagos y cobranza siguen en el backlog 10.

## Contrato

Cada evento nuevo debe declararse en `App\Enums\ActivityEvent` y registrarse mediante `App\Services\ActivityLogService`. El servicio agrega automáticamente:

- `event`: código estable y apto para filtros.
- `description`: explicación legible de la acción.
- actor (`causer`) y sujeto (`subject`) cuando aplican.
- `team_id` para aislar la consulta por equipo.
- `sucursal_id` para conservar el contexto operativo en que ocurrio el evento.
- `ip`, `user_agent` y `request_id` de la petición.

Los metadatos funcionales permitidos son:

- `changed_fields`: nombres de campos modificados, nunca sus valores.
- `related`: tipo e identificador de un recurso relacionado.
- `state`: estado no sensible.
- `provider` y `product`: integración consultada.
- `result`: resultado operativo.

No se deben registrar nombres, correos, teléfonos, RFC, CURP, domicilios, contenido documental, respuestas SIC, contraseñas, tokens, secretos ni valores anteriores o posteriores de campos sensibles.

## Matriz implementada

| Área | Eventos |
| --- | --- |
| Autenticación | `login`, `login.2fa_completed` |
| Usuario | `user.profile.updated` |
| Empresa | `empresa.updated` |
| Sucursales | `sucursal.created`, `sucursal.updated`, `sucursal.deactivated` |
| Clientes | `cliente.created`, `cliente.updated`, `cliente.deleted`, `cliente.sucursal.transferred`, `cliente.kyc.updated` |
| Referencias | `cliente.referencia.created`, `cliente.referencia.updated`, `cliente.referencia.deleted` |
| Vínculos | `cliente.vinculo.created`, `cliente.vinculo.deleted` |
| Garantías | `cliente.garantia.created`, `cliente.garantia.updated`, `cliente.garantia.deleted` |
| Documentos | `cliente.documento.received`, `cliente.documento.status_updated`, `cliente.documento.downloaded` |
| Consentimiento SIC | `cliente.consentimiento_sic.created`, `cliente.consentimiento_sic.revoked`, `cliente.consentimiento_sic.evidence_downloaded` |
| Consultas SIC | `cliente.sic.fico_score_v2.queried`, `cliente.sic.fintech_score.queried`, `cliente.sic.credit_report_fico.queried` |

## Registros históricos

Los registros antiguos cuyo campo `event` es nulo no se reescriben ni eliminan. La interfaz y el filtro los exponen como `legacy.unclassified`, con la etiqueta **Sin clasificar (histórico)**. Los códigos antiguos no reconocidos se conservan y muestran con su valor original.

## Consulta y exportación

Super Admin global puede consultar actividad de todos los equipos. Un usuario con permiso `read activity-log` solo puede consultar y exportar la actividad de su equipo actual. Backlog 02.5 definira el filtrado y la matriz de acceso por sucursal. Los valores CSV que podrian interpretarse como formulas se prefijan para que la hoja de calculo los trate como texto.

## Agregar un evento

1. Crear el caso y su etiqueta, severidad e icono en `ActivityEvent`.
2. Emitirlo exclusivamente con `ActivityLogService`.
3. Incluir solo metadatos de la lista permitida.
4. Agregar una prueba que verifique código, equipo, sujeto y ausencia de datos sensibles.
5. Actualizar esta matriz.
