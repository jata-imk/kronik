# Estado de la integración SIC

Actualización: 10/10/2026. P7 recupera lectura histórica y tres servicios de
simulación mediante adaptador nuevo. Los seis puntos de entrada del SDK antiguo
siguen cerrados por los motivos de [ADR 0011](../explanation/adr-0011-integracion-sic-segura.md).
Véase [ADR 0022](../explanation/adr-0022-sic-qa-evidencia.md).

## Servicios QA

| Clave | Ruta sandbox | Resumen |
| --- | --- | --- |
| `fico_score_v2` | `/sandbox/v2/ficoscore` | Score, modelo, razones y fecha. |
| `fintech` | `/sandbox/v1/fintechscore/` | Score, modelo, razones y fecha. |
| `rc_fico_score` | `/sandbox/v1/rcficoscore` | Base común y secciones presentes de cuentas, consultas, domicilios, empleos, identidad y mensajes. |

El catálogo conserva siete APIs; solo estas tres tienen adaptador. La respuesta
original se conserva cifrada aunque un campo no tenga gráfica. Las gráficas
separan monedas MX/US/UD, distinguen cero de dato ausente y no inventan rangos de
score ni umbrales de riesgo. La evolución compara el mismo servicio, modelo
identificado y ambiente.

## Acceso y evidencia

El domicilio enviado incluye la ciudad de `codigos_postales.datos_adicionales.nombre_ciudad`.
FICO v2 y Reporte de Crédito + FICO la requieren; si falta o supera 65 caracteres,
la consulta se rechaza en español antes de conservar un intento o enviar al proveedor.
No se sustituye por el municipio o el estado. Fintech admite omitirla cuando el
catálogo no dispone de ese dato.

En la petición, los textos del domicilio se convierten a mayúsculas sin acentos,
se reemplazan los paréntesis por espacios y se compactan los espacios. Esto permite
enviar colonias como `Centro (Área 1)`; el expediente y el catálogo conservan su
nombre original. No se alteran las respuestas históricas.

- Historial/detalle: lectura de historial y clientes; política de acceso al cliente.
- Consulta: permiso de creación SIC, actualización del cliente, equipo/sucursal
  activos, consentimiento vigente con archivo y revisión actual si corresponde.
  La [fecha de otorgamiento](expediente-clientes.md#consentimiento-sic) conserva
  el instante enviado por el navegador aunque la aplicación use una zona distinta de UTC.
- Conciliación: permiso adicional `reconcile circulo-credito`.
- Intento: actor, equipo, sucursal, consentimiento, revisión opcional, hashes e
  idempotencia. Petición/respuesta/resultado/conciliación cifrados e inmutables.
- SIC requerido: APIs y regla sin score explícitas en política versionada; revisión,
  identidad y consentimiento vigentes. Evaluación manual y PLD siguen exigidos.
- Históricos de procedencia desconocida y datos demo no acreditan aprobación.
  QA no habilita originación productiva.

## Estados y operación

`preparada → enviando → completada/error/incierta`. Cambios de requisitos antes
del envío cancelan el intento. Un timeout/interrupción no se reintenta. El
scheduler marca envíos interrumpidos tras dos minutos. Conciliación documentada
cierra como `conciliada_sin_resultado`, sin fabricar score ni consultar de nuevo.

TLS verificado; `SIC_CA_BUNDLE` opcional configura autoridades confiables de PHP.
Guard de QA/sandbox/host/key y redirecciones rechazadas. Firma nativa P384/SHA256
probada; transporte productivo cerrado. Migración conserva respuestas existentes.
`sic:cifrar-historico` archiva y verifica; retirar texto plano requiere respaldo.
[Guía SIC QA](../how-to/qa-sic.md).
