# Roles - Aislamiento y etiquetas

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81a1a945fdc60db68fc4
- Rama: `fix/roles-aislamiento-etiquetas`
- PR: https://github.com/jata-imk/kronik/pull/47.
- ADR: [0025](../explanation/adr-0025-roles-aislamiento-presentacion.md).

## Objetivo

Cerrar las escrituras entre equipos y corregir gestión y presentación de roles.

## Estado actual

- Estado: integrado en `main` mediante `f16b3ed` (PR #47), con CI aprobado; despliegue normal en VPS y QA general satisfactorio reportados por el usuario.
- Última actualización: 2026-10-28.
- Último punto estable: pruebas, build y revisión medium completados.

## Decisiones pendientes

- Ninguna; plantillas solo en seeders y sin descripción opcional de roles.

## Evidencia

- Pruebas: 10 backend, 59 aserciones; 2 Vue; build y Pint aprobados; E2E Chromium aprobado (persistir/vaciar permisos y ayuda sin cambiar selección), sin desbordamiento en 390/768/1280 px y ambos temas.
- Revisión medium: corregidos conflicto de nombre con plantilla global y propagación del clic de ayuda; segunda pasada sin hallazgos. Vitest local pasó independientemente de la restricción del sandbox del revisor.
- Capturas: [escritorio](../assets/roles-etiquetas-desktop.png) y [móvil oscuro](../assets/roles-etiquetas-mobile-dark.png).
- Migraciones: `2026_10_25_210000_add_permission_catalog_labels`; agrega metadatos y completa registros existentes.
- Review medium acumulado de PR #46–#50 sobre `a6057a0..6c30e3c`: sin hallazgos pendientes; 43 pruebas backend focalizadas y 323 aserciones aprobadas. Nueve E2E de la iniciativa aprobados antes del último merge.
- QA posterior del usuario: confirmación general del 2026-10-28 («ya comprobé todo y quedó re bien»); su consulta posterior incluye esta entrega en el lote de siete. Sin SHA desplegado ni versiones informados; no se atribuyen resultados individuales no descritos.

## Siguiente paso

Sin pendientes de esta entrega. Notion conserva estado Done y cierre de QA; la mejora posterior de Equipo/bitácora (PR #55) se sigue por separado.

## Cierre

- Commit o merge: `f16b3ed`, PR #47.
- Pendientes diferidos: ninguno dentro de esta entrega.
