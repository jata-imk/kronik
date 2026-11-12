# Desembolsos históricos y captura cronológica

## Referencias

- Notion: https://app.notion.com/p/41361db7db7f81f5ad52d7df919dee99
- Base funcional: main 67bba2c.
- Rama: feat/desembolsos-historicos; worktree output/desembolsos-historicos/worktree.
- Backend: [PR #79](https://github.com/jata-imk/kronik/pull/79), código 2b2ccb1; segunda dependiente feat/desembolsos-historicos-ui para UI/aceptación.
- [ADR 0045](../explanation/adr-0045-fechas-efectivas-captura-historica.md).

## Estado — 2026-11-11

Implementación en curso, sin integración, despliegue ni QA compartido. Árbol principal preservado; diff y hashes previos en output/desembolsos-historicos, fuera de la entrega. Worktrees anteriores intactos.

Definición recibida: activar borrador QA con vigencia pasada conservando activación real. Dos PR dependientes, revisión medium por cada una y barrido conjunto contra base funcional antes de dar por lista la entrega. Monolito modular; no modificar core para conocer canales/documentos ni equipos como tenants.

Backend implementa regla temporal, desembolso firmado pasado, control operativo separado y cierre idempotente con auditoría. Código 2b2ccb1: suite completa 955 aprobadas, una omitida preexistente y 47352 aserciones; Pint y build aprobados. Hook pre-push repitió backend/build sin omitir controles. [CI 43151430137](https://github.com/jata-imk/kronik/actions/runs/43151430137) aprobada, incluidas diez competencias multiconexión MariaDB por política (ocho existentes y dos nuevas de cierre/pago histórico). Sin integración ni despliegue.

## Evidencia y siguiente paso

Completar PR dependiente de UI y aceptación integral con BD nueva, reloj real y PDF real; regresiones, revisión medium propia y barrido conjunto contra 67bba2c. Actualizar esta nota y Notion con resultados efectivos. Migración aditiva de control operativo; no ejecutada en BD operativa. Sin dinero real ni fusiones/despliegues autorizados. Capacidad/proyecciones y canales conservan sus tareas; no se acredita capacidad para 10 mil créditos.

## Revisión medium del backend

Revisión inicial de cambios contra 67bba2c detectó dos hallazgos P2: seeder dual activaba ayer sin confirmación y confirmación histórica aceptaba booleanos HTML pero el servicio comparaba estrictamente true. Corregidos: confirmación explícita del fixture y normalización única antes del hash/validación, con regresión para true/1/"1". Revisión medium posterior sin defectos accionables. El revisor inspeccionó fuente; implementador ejecutó las suites separadamente para evitar colisiones.

La suite completa detectó colisión del disco fake al ejecutarse junto a pruebas del revisor; las nuevas pruebas usan disco por proceso. La nueva tabla también debe clasificarse en el inventario del reinicio QA existente: actualizado sin ejecutar el comando sobre ninguna base operativa. Añadido rechazo claro de vigencia repetida, sin retirar la versión activa.
