# Mantenimiento transversal — reinicio de datos QA

## Referencias

- Notion: https://app.notion.com/p/3fd61db7db7f812cb9b8db0580fbf39e
- Rama: `feat/reset-qa-minimo`, desde `main` en `6058126`.
- PR y evidencia final: registradas en el seguimiento de Notion anterior.
- ADR: [0024](../explanation/adr-0024-reinicio-integral-qa.md).

## Objetivo

Preparar una nueva ronda de accesos QA mediante un reinicio completo que conserve una cuenta global y sus contextos mínimos. Mantenimiento solicitado por el usuario; no modifica el orden del roadmap ni reabre los backlogs integrados.

## Estado actual

- Estado: implementado, pendiente de revisión e integración.
- Última actualización: 2026-10-17.
- Último punto estable: 34 pruebas específicas aprobadas, 280 aserciones; Pint correcto. Incluyen limpieza del menú contextual, vista previa, rollback y acceso administrativo posterior. La PR y Notion conservan el resultado de la regresión final, build y CI MariaDB.
- Sin ejecutar reinicios ni migraciones en la VPS o en la base local de la aplicación. Las mutaciones de prueba usan bases aisladas y discos simulados.

## Decisiones pendientes

- Ninguna de implementación. El operador debe elegir el administrador y comprobar el respaldo antes de ejecutar el procedimiento en la instalación QA.
- Ajuste solicitado después de la primera entrega: eliminar también el menú contextual por módulos y sus asociaciones. Mínimo/vacío quedan sin entradas; demo reconstruye los valores predeterminados. Conserva catálogo de módulos, navegación principal y configuración de almacenamiento.

## Evidencia

- Pruebas: modos mínimo/demo/vacío, vista previa, credenciales/2FA, empresa y contextos, repetición, rollback, archivos pendientes recuperables, bloqueo de datos fuera de QA, configuraciones externas y tablas sin clasificar. Incluye ciclo real de servicios QA con desembolso, anticipo y reverso, evidencias SIC y documentos.
- Revisión manual: [guía operativa](../how-to/reset-datos-desarrollo.md) y cambio CLI; sin cambios visuales que requieran Playwright.
- Migración: `2026_10_18_000000_create_development_data_resets_table.php`, registro duradero y manifiesto cifrado. No cambia `.env` ni incorpora secretos. Modo mínimo no ejecuta seeders.

## Siguiente paso

Revisar e integrar la PR; desplegar y migrar; usar vista previa y el procedimiento documentado antes de cualquier borrado real del entorno QA.

## Cierre

- Commit/PR: consultar seguimiento Notion enlazado.
- Pendientes diferidos: limpieza de archivos huérfanos y servicios externos, coordinación distribuida del bloqueo y habilitación de otros almacenes de sesiones/colas. No hay autorización de dinero real ni SIC productivo.
