# Desembolsos históricos y captura cronológica

## Referencias

- Notion: https://app.notion.com/p/41361db7db7f81f5ad52d7df919dee99
- Base funcional: main 67bba2c.
- Rama: feat/desembolsos-historicos; worktree output/desembolsos-historicos/worktree.
- PR: pendiente backend; segunda dependiente para UI/aceptación.
- [ADR 0045](../explanation/adr-0045-fechas-efectivas-captura-historica.md).

## Estado — 2026-11-11

Implementación en curso, sin integración, despliegue ni QA compartido. Árbol principal preservado; diff y hashes previos en output/desembolsos-historicos, fuera de la entrega. Worktrees anteriores intactos.

Definición recibida: activar borrador QA con vigencia pasada conservando activación real. Dos PR dependientes, revisión medium por cada una y barrido conjunto contra base funcional antes de dar por lista la entrega. Monolito modular; no modificar core para conocer canales/documentos ni equipos como tenants.

Backend implementa regla temporal, desembolso firmado pasado, control operativo separado y cierre idempotente con auditoría. Evidencia de pruebas, review y concurrencia en preparación; no atribuir resultados de la línea base a esta capacidad.

## Evidencia y siguiente paso

Completar pruebas backend y revisión medium de primera PR. Después UI y recorrido integral con BD nueva, reloj real y PDF real; regresión, concurrencia y barrido final. Actualizar esta nota con commits/PR/resultados efectivos. Migración aditiva de control operativo; no ejecutada en BD operativa. Sin dinero real ni fusiones/despliegues autorizados.

## Revisión medium del backend

Revisión inicial de cambios contra 67bba2c detectó dos hallazgos P2: seeder dual activaba ayer sin confirmación y confirmación histórica aceptaba booleanos HTML pero el servicio comparaba estrictamente true. Corregidos: confirmación explícita del fixture y normalización única antes del hash/validación, con regresión para true/1/"1". Revisión posterior pendiente.

La suite completa detectó colisión del disco fake al ejecutarse junto a pruebas del revisor; las nuevas pruebas usan disco por proceso. La nueva tabla también debe clasificarse en el inventario del reinicio QA existente: actualizado sin ejecutar el comando sobre ninguna base operativa. Añadido rechazo claro de vigencia repetida, sin retirar la versión activa.
