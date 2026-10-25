# Roles - Aislamiento y etiquetas

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81a1a945fdc60db68fc4
- Rama: `fix/roles-aislamiento-etiquetas`
- PR: pendiente; enlace y merge finales en Notion.
- ADR: [0025](../explanation/adr-0025-roles-aislamiento-presentacion.md).

## Objetivo

Cerrar las escrituras entre equipos y corregir gestión y presentación de roles.

## Estado actual

- Estado: entregado; integración y CI final verificables en la PR y Notion.
- Última actualización: 2026-10-25.
- Último punto estable: pruebas, build y revisión medium completados.

## Decisiones pendientes

- Ninguna; plantillas solo en seeders y sin descripción opcional de roles.

## Evidencia

- Pruebas: 10 backend, 59 aserciones; 2 Vue; build y Pint aprobados; E2E Chromium aprobado (persistir/vaciar permisos y ayuda sin cambiar selección), sin desbordamiento en 390/768/1280 px y ambos temas.
- Revisión medium: corregidos conflicto de nombre con plantilla global y propagación del clic de ayuda; segunda pasada sin hallazgos. Vitest local pasó independientemente de la restricción del sandbox del revisor.
- Capturas: [escritorio](../assets/roles-etiquetas-desktop.png) y [móvil oscuro](../assets/roles-etiquetas-mobile-dark.png).
- Migraciones: `2026_10_25_210000_add_permission_catalog_labels`; agrega metadatos y completa registros existentes.

## Siguiente paso

Consultar CI y merge en Notion; continuar con invitaciones tras integrar.

## Cierre

- Commit o merge: consultar PR y Notion.
- Pendientes diferidos: despliegue independiente.
