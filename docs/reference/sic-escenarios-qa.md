# Escenarios SIC QA

El backend puede enviar una identidad oficial de CDC independientemente del cliente
seleccionado. Conserva el cliente, su identidad, consentimiento, revisión y contexto
originales. El resultado describe el escenario sintético, no a ese cliente.

## Configuración y selección

- `SIC_QA_ESCENARIOS_HABILITADOS=false` habilita la modalidad al cambiar a `true`.
- `SIC_QA_APROBACION_SINTETICA_HABILITADA=false` controla por separado su aceptación
  para aprobación QA. Requiere también aprobación habilitada y guardas SIC activas.
- Las guardas de QA, sandbox, host, credenciales y rutas siguen siendo obligatorias.
  No basta con `APP_ENV`. Reconstruir configuración y reiniciar workers al desplegar.
- El POST existente acepta `escenario_qa`. Omitirlo selecciona el positivo del servicio:
  FICO 720, Fintech 404 (folio 20210304), Reporte 546, según sus colecciones.
- Claves desconocidas o de otro servicio se rechazan. La selección es por intento.
  El navegador recibe nombres y claves, nunca cuerpos de petición ni credenciales.

## Catálogo y evidencia

El catálogo versionado en servidor contiene 49 peticiones iniciales FICO v2,
27 Fintech y 8 Reporte + FICO. Full/Segmented tienen el mismo cuerpo; se deduplican.
Las operaciones /folio están inventariadas en las colecciones, pero no se ofrecen
ni se utilizan para recuperación. Los valores anunciados son expectativas, no
resultados comprobados por esta entrega.

Fuentes oficiales revisadas el 09/11/2026:

- [FICO v2](https://developer.circulodecredito.com.mx/sites/default/files/postman/1747669029/Ficoscore%20v2%20sandbox.postman_collection.json).
- [Fintech](https://developer.circulodecredito.com.mx/sites/default/files/postman/1669825486/fintech-score-sandbox-collection.json).
- [Reporte + FICO](https://developer.circulodecredito.com.mx/sites/default/files/postman/1675875186/reporte-de-credito-fico-score-sandox-collection.json).

Se conservan literalmente caracteres, fechas, folios y cuerpos deliberadamente
inválidos publicados. No se aplican validaciones de alta a estas identidades.
Los datos del expediente no se modifican; el domicilio original sin ciudad
SEPOMEX no impide enviar el escenario. El tratamiento productivo de Homun y otras
localidades sin ciudad continúa pendiente de confirmación con CDC.

Cada intento congela clave, versión, procedencia, definición y petición cifrada.
Las claves publicadas son estables; cualquier cambio de definición requiere
incrementar versión, sin reutilizar claves para otro caso.
Repetir la llave devuelve el intento original; cambiar el escenario con la misma
llave se rechaza. No se reinterpretan históricos con el catálogo actual.

Los casos internos (con/sin score, E2, persona no encontrada, validación,
autenticación, permisos, límite, fallo, respuesta incompleta e incertidumbre)
se identifican como simulaciones internas y no envían HTTP. Su respuesta se
congela al preparar. No sustituyen automáticamente un fallo real del proveedor.

## Lectura y aprobación

Historial y detalle muestran procedencia. Los escenarios no participan en evolución
personal; las gráficas del reporte conservan su significado dentro del caso.
HTTP 204, score cero, E2, 404.1, HTTP 401/403/429 y fallo técnico permanecen distintos.
Un score 404 no es HTTP 404. El HTTP 404 genérico no implica credenciales inválidas.

La aceptación sintética exige procedencia congelada, ambas guardas explícitas,
identidad/revisión/consentimiento vigentes y política compatible. Con score puede
acreditar el recorrido QA; sin score requiere evaluación manual permitida. E2,
errores e incertidumbre bloquean. Se conserva uso QA y hash del escenario en la
evidencia aprobada y se revalida al preparar paquete. Demostrativos antiguos sin
procedencia permanecen excluidos.

No hay reintentos POST automáticos. Una simulación incierta se concilia como tal;
una consulta CDC incierta requiere la revisión con el proveedor. Auditoría conserva
eventos de ciclo existentes con procedencia en `provider`, sin cuerpos ni secretos.

Véanse [guía QA](../how-to/qa-sic.md), [ADR 0043](../explanation/adr-0043-escenarios-sic-qa.md)
y [tema SIC](../topic/sic-circulo-credito.md).

[Captura móvil con datos E2E sintéticos](../assets/sic-escenarios-qa-mobile.png).
