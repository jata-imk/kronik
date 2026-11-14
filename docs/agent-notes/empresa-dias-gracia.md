# Días de gracia predeterminados de Empresa

## Referencias

- Notion: https://app.notion.com/p/41461db7db7f8169bbc5cbebcda5ee19
- Rama: `fix/empresa-dias-gracia`; base `640dc5f`.
- PR: [#77](https://github.com/jata-imk/kronik/pull/77).
- Contrato: [Empresa](../reference/configuracion-empresa.md#días-de-gracia-predeterminados).

## Estado actual — 2026-11-10

Integrado en `main` el 2026-11-10 mediante PR #77, commit `7852dc3`, por
autorización del usuario. PR #78 se integró después en `2c092da`; ambas entregas
conviven en esa base. Despliegue y QA del usuario pendientes.
Empresa prellena nuevas capturas, respeta cero y usa 3 al faltar el valor.
Los productos existentes conservan condiciones. Sin migraciones, seeders ni variables nuevas.
Se conservan los eventos actuales; el prellenado no registra actividad.

## Evidencia

- Pruebas focalizadas backend: 11 aprobadas / 106 aserciones.
- Suite backend en `d730cae`: 890 aprobadas / 46378 aserciones / 1 omitida
  (SQLite en memoria). Frontend: 153 aprobadas en 58 archivos. Build aprobado.
- Los 45 escenarios activos del E2E general quedaron aprobados entre la ejecución
  inicial y revalidaciones. Los 4 omitidos por flags se ejecutaron aparte:
  productos Bullet, ambos ciclos completos y dual, todos aprobados.
- Se corrigieron selectores de controles visibles/tarjetas móviles, creación de
  políticas y tiempos de navegación/recorridos largos; geolocalización IP simulada.
  Logs: `dias-gracia-e2e-general.log`, `gracia-e2e-final.log`,
  `gracia-e2e-bullet-final.log` y `gracia-e2e-dual.log`, bajo `storage/logs/`.
- [CI funcional aprobado](https://github.com/jata-imk/kronik/actions/runs/42861498665)
  en `260a544`: backend MariaDB, Vue, build, concurrencia de folios/auditoría/Bullet,
  PDF real y E2E de gracia, ambos Bullet y dual. Capturas en su artefacto
  `browser-evidence` (retención de CI: 7 días).
- El preflight detectó un límite fijo de 10 segundos en la medición PDF. Se
  corrigió compartiendo el presupuesto con la generación, acotado a 50 segundos
  totales y 35 por subproceso, sin cambiar el job de 55. Las 29 pruebas de
  documentos con Chromium real pasaron; el smoke produjo 9702 bytes.
- Revisión medium individual sobre `d62ecc2` y barrido acumulado sobre `2ff634a`
  (incluye `260a544` y RFC `4c0121b`), contra `640dc5f`: sin hallazgos accionables.
  Logs `gracia-review-publicado.log` y `combinada-review-medium.log`.
- Combinación local `qa/dias-gracia-rfc` sin conflictos: 78 pruebas backend / 849
  aserciones (1 omitida por flag de Chromium), 160 frontend y build aprobados.
  Evidencia previa a integración en `storage/logs/combinada-*`.
  Los 3 E2E conjuntos (física, moral y gracia) pasaron; capturas sintéticas
  conservadas en `test-results-final/` del checkout principal.
- Enlaces locales y cobertura de índices/temas revisados sin rotos.
- El hook pre-push duplicado se omitió tras ejecutar Pest y build manualmente;
  CI volvió a ejecutar las comprobaciones en el commit publicado.
- [CI final de PR aprobado](https://github.com/jata-imk/kronik/actions/runs/42863203847)
  en `7a98536`, sin hilos de revisión pendientes al fusionar.
- Barrido de integración: `2c092da` conserva exactamente el código, recursos,
  pruebas y workflow de la combinación validada `2ff634a`; solo difieren las
  notas documentales finales y la justificación de auditoría PDF. Sin conflictos,
  migraciones ni comprobaciones funcionales nuevas necesarias por ese registro.

## Siguiente paso

Seguir el procedimiento de despliegue/QA; la integración ya está completada.
Registrar esos estados en esta nota y Notion cuando ocurran; las pruebas automatizadas
no sustituyen QA del usuario. La entrega funcional está validada, sin cambios de BD.
