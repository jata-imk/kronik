# Corrección SIC — domicilio sandbox

## Referencias

- Notion: [manual visual QA](https://app.notion.com/p/3fe61db7db7f812d878ccccc1e90d651).
- Rama: `fix/sic-domicilio-sandbox`, base main `d218a0c`.
- PR: [#43](https://github.com/jata-imk/kronik/pull/43), implementación522d0f0,
  merge79d155d en main.
- ADR: [0022, SIC QA](../explanation/adr-0022-sic-qa-evidencia.md).

## Objetivo

Resolver SIC-DOM-001, encontrado después de corregir SIC-TZ-001 (PR #42).
José autorizó ampliar las correcciones a los bloqueos del envío SIC sandbox y
usar casos sintéticos oficiales cuando se necesiten, manteniendo aprobación y
despliegue manual. Otros hallazgos permanecen registrados sin corrección.

## Estado actual

- En progreso, 25/10/2026. El sandbox FICO rechazó la consulta3 por ausencia de
  ciudad; Reporte+FICO5 señaló ciudad ausente y paréntesis no admitidos en colonia.
  Son respuestas finales HTTP400, no intentos inciertos. Fintech4 devolvió404.1
  «No se encontró a la persona»: no se considera score ni evidencia aprobatoria.
- Cliente sintético8, consentimiento4 y SOL-6 revisión2 (ID8); no se reenvían
  esos intentos ni se modifican sus evidencias. Empresa moral sintética activa:
  restaurar sus campos originales al terminar/pausar el manual.
- La ciudad existe en el catálogo de CP06000: nombre_ciudad Ciudad de México.
  Se usa ese dato, sin inventar que municipio y ciudad son equivalentes.
- Corrección local implementada: ciudad según servicio y normalización de
  paréntesis/espacios solo en la petición; datos originales conservados.
- Desarrollo/E2E: se completa la ciudad del domicilio sintético mínimo; no
  requiere ejecutar ese seeder en QA ni modificar su catálogo existente.
- Integrado con CI aprobada y despliegue confirmado por José. En QA consultas6/7
  dejan de recibir el400 por domicilio y terminan404 sin resultado. La petición
  del cliente10 conserva ciudad CIUDAD DE MEXICO, colonia MONTEVIDEO y datos
  originales de catálogo. El folio de escenario Fintech es otro bloqueo.

## Evidencia

- Respuestas ya conservadas leídas sin enviar consultas ni escribir en QA.
- Regresión inicial:9 casos fallan sin la corrección; tras corregir,30 pruebas
  SIC aprobadas y200 aserciones; se añadió además regresión de ciudad opcional
  en Fintech. Suite completa previa a esa última aserción:426 aprobadas,1 omitida,
  40475 aserciones; el hook final registrará el total vigente.
- Hook final local:427 aprobadas,1 omitida,40479 aserciones y build aprobado.
- E2E `sic-qa.spec.js`: aprobado (tres servicios y reporte con cinco gráficas),
  con fixtures aislados, sin conexión al proveedor; Pint aprobado. CI verde.
- Los ejemplos oficiales y el SDK marcan ciudad obligatoria en FICO/Reporte;
  ver las [fuentes SIC](../fuentes/circulo-credito.md).
- Sin migraciones, permisos, nuevas variables ni habilitación productiva.

## Siguiente paso

Continuar con [el folio de escenario Fintech](sic-folio-fintech.md). Domicilio
corregido y validado; conservar errores históricos y no convertir un404 en score.

## Cierre

- Commit/merge:522d0f0, PR43, main79d155d; despliegue reportado por José.
- Diferidos: SIC productivo; otros hallazgos del manual.
