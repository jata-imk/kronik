# Clientes: RFC genéricos

## Referencias

- Notion: https://app.notion.com/p/41461db7db7f8105908cdbc563d1174a
- Rama: `fix/clientes-rfc-genericos`, base `640dc5f`.
- Checkout aislado: `storage/framework/testing/rfc-worktree`.
- PR: [#78](https://github.com/jata-imk/kronik/pull/78).
- [ADR 0044](../explanation/adr-0044-rfc-genericos-clientes.md) y [contrato](../reference/validacion-rfc.md).

## Estado actual — 2026-11-10

Integrado en `main` el 2026-11-10 mediante PR #78, commit `2c092da`, por
autorización del usuario, después de PR #77 (`7852dc3`). Ambas entregas conviven
en esa base. Despliegue y QA del usuario pendientes.
Ambos genéricos se admiten y repiten para física/moral, alta/edición web/API.
Empresa conserva validación estricta; la UI informa identidad propia pendiente.
Consulta bloquea submit y watchers de normalización. Sin migraciones ni nuevas
variables productivas; `.env` del checkout contiene solo configuración sintética
para pruebas aisladas, nunca credenciales ni la base de desarrollo.

## Evidencia

- Backend focalizado: 39 pruebas / 442 aserciones aprobadas.
- SIC y originación QA con genéricos/corrección: 9 pruebas / 65 aserciones aprobadas.
- Componentes nuevos: 7 pruebas aprobadas.
- Suite backend en `fcedf23`: 913 aprobadas / 46741 aserciones / 1 omitida
  (SQLite en memoria).
- Build aprobado; frontend: 158 aprobadas en 60 archivos.
- E2E focalizado final: 2 aprobadas (física y moral, alta repetida, corrección,
  consulta móvil). Geolocalización IP del teléfono simulada en el harness,
  sin dependencia de disponibilidad o tiempos del proveedor.
- Los 46 escenarios activos del E2E general quedaron aprobados entre la ejecución
  inicial (45) y la revalidación de solicitud (1). Se corrigió el selector obsoleto
  de confirmación de política sin cambiar autorización ni omitir pasos.
  SIC QA de los tres servicios también pasó. Bullet y dual se verificaron con
  la rama compañera y en el CI propio. Logs bajo `storage/logs/rfc-e2e-*.log`.
- [CI funcional aprobado](https://github.com/jata-imk/kronik/actions/runs/42861524542)
  en `4c0121b`: backend MariaDB, Vue, build, concurrencia de folios/auditoría/Bullet,
  PDF real y E2E de ambos Bullet, dual y RFC. Capturas en `browser-evidence`
  (retención de CI: 7 días).
- Se comparte con PR #77 la corrección del presupuesto PDF detectada por CI:
  medición y generación comparten hasta 50 segundos, máximo 35 por subproceso,
  reservando persistencia dentro del job de 55. Smoke real de 9702 bytes aprobado;
  29 pruebas de documentos con Chromium real aprobadas en la rama compañera.
- Revisión medium final sobre `4c0121b`, base `640dc5f`: sin hallazgos accionables
  (`rfc-review-pdf-final.log`). Barrido acumulado sobre `2ff634a` (incluye esa rama
  y gracia `260a544`), misma base: sin hallazgos (`combinada-review-medium.log`
  en el checkout principal).
- Combinación local sin conflictos en `qa/dias-gracia-rfc`: 78 pruebas backend /
  849 aserciones (1 omitida por flag de Chromium), 160 frontend y build aprobados.
  Evidencia previa a integración en `storage/logs/combinada-*` del checkout principal.
  Los 3 E2E conjuntos (física, moral y gracia) pasaron; capturas sintéticas
  conservadas en `test-results-final/` del checkout principal.
- 141 enlaces locales revisados sin rotos; ADR indexado y accesible por temas.
- El hook pre-push duplicado se omitió tras ejecutar Pest y build manualmente;
  CI repitió las comprobaciones en el commit publicado.
- [CI final de PR aprobado](https://github.com/jata-imk/kronik/actions/runs/42863229143)
  en `f408505`, sin hilos de revisión pendientes al fusionar.
- Barrido de integración: `2c092da` conserva exactamente el código, recursos,
  pruebas y workflow de la combinación validada `2ff634a`; solo difieren las
  notas documentales finales y la justificación de auditoría PDF. Sin conflictos,
  migraciones ni comprobaciones funcionales nuevas necesarias por ese registro.

## Pendientes diferidos y siguiente paso

Seguir el procedimiento de despliegue/QA y registrar sus resultados cuando ocurran;
la integración ya está completada.
La unicidad de RFC propios queda en
[su tarea de Notion](https://app.notion.com/p/41461db7db7f81aea71aefbb4b912571)
por decisión del usuario, sin fecha nueva; evaluar datos existentes antes de definir alcance o migraciones.
No se acredita aceptación por un proveedor real, SIC productivo ni facturación.
