# Mantenimiento transversal — reinicio de datos QA

## Referencias

- Notion: https://app.notion.com/p/3fd61db7db7f812cb9b8db0580fbf39e
- Rama: `feat/reset-qa-minimo`, desde `main` en `6058126`.
- PR: [#39](https://github.com/jata-imk/kronik/pull/39), integrada en `main` mediante `0a7656c`.
- ADR: [0024](../explanation/adr-0024-reinicio-integral-qa.md).

## Objetivo

Preparar una nueva ronda de accesos QA mediante un reinicio completo que conserve una cuenta global y sus contextos mínimos. Mantenimiento solicitado por el usuario; no modifica el orden del roadmap ni reabre los backlogs integrados.

## Estado actual

- Estado: integrado y ejecutado satisfactoriamente en QA según confirmación del usuario.
- Última actualización: 2026-10-18.
- Último punto estable: 43 pruebas de reinicio/menú aprobadas, 647 aserciones; Pint correcto. Incluyen limpieza del menú, catálogo inactivo, idempotencia, conservación de personalizaciones y acceso administrativo. La PR y Notion conservan el resultado de la regresión final, build, E2E y CI MariaDB.
- El desarrollo se comprobó con bases aisladas y discos simulados. Después de integrar y desplegar, el usuario ejecutó el dry run y confirmó que el reinicio mínimo funcionó correctamente en la VPS QA; el asistente no ejecutó ese borrado remoto.

## Decisiones pendientes

- Ninguna de implementación. En futuras ejecuciones, el operador debe elegir el administrador y seguir el procedimiento de respaldo y detención de escritores.
- Ajuste solicitado después de la primera entrega: eliminar el menú contextual y sus asociaciones. Mínimo/vacío quedan sin entradas. Se modifica el `MenubarItemsSeeder` existente para cargar 19 módulos/45 acciones sin rutas de activación; demo usa ese catálogo y puede cargarse manualmente después del mínimo. Conserva personalizaciones al reejecutarse. Los fixtures E2E activan expresamente los atajos de clientes.

## Evidencia

- Pruebas: modos mínimo/demo/vacío, vista previa, credenciales/2FA, empresa y contextos, repetición, rollback, archivos pendientes recuperables, bloqueo de datos fuera de QA, configuraciones externas y tablas sin clasificar. Incluye ciclo real de servicios QA con desembolso, anticipo y reverso, evidencias SIC y documentos.
- Revisión manual: [guía operativa](../how-to/reset-datos-desarrollo.md) y cambio CLI; sin cambios visuales que requieran Playwright.
- Navegación con el seeder actualizado: 7 recorridos E2E de administración/clientes aprobados en Chromium. Se corrigió un selector que elegía la tarjeta móvil oculta; el recorrido de clientes se repitió con acceso de red permitido tras errores de recursos externos del sandbox. Base E2E SQLite separada, sin cambios de interfaz.
- Migración: `2026_10_18_000000_create_development_data_resets_table.php`, registro duradero y manifiesto cifrado. No cambia `.env` ni incorpora secretos. Modo mínimo no ejecuta seeders.

## Siguiente paso

Entrega cerrada con confirmación de ejecución QA. Para futuros reinicios, usar la
vista previa y el procedimiento documentado; conservar los límites de limpieza.

## Cierre

- Commit/PR: consultar seguimiento Notion enlazado.
- Pendientes diferidos: limpieza de archivos huérfanos y servicios externos, coordinación distribuida del bloqueo y habilitación de otros almacenes de sesiones/colas. No hay autorización de dinero real ni SIC productivo.
