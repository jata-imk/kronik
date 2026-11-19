# ADR 0047: catálogo compartido y contexto del Menubar

- Estado: aceptado para implementación por el usuario el 2026-11-18.
- Base: `8884a78d738f9623c5d72fc194c4ecc55b400874`.
- Tarea: https://app.notion.com/p/41361db7db7f81968ea5cfc45a96b51d

## Contexto

El menú ya tiene jerarquía, destinos y asociaciones con pantallas, pero configurarlo exige rutas y parámetros técnicos. La referencia de escritorio expresa grupos/submenús, no otra navegación ni comandos arbitrarios. Equipos no equivalen a financieras.

## Decisión

Conservar las tablas actuales como catálogo compartido por instalación y los permisos existentes, evaluados en el equipo efectivo. Cada elemento comparte etiqueta, icono, grupo y orden entre sus pantallas. No crear preferencias personales.

`config/menubar.php` es la fuente de acciones para el seeder y el editor. Los parámetros derivan de las rutas registradas, nunca del singular supuesto de un módulo. Se excluyen escrituras y búsquedas JSON. Las configuraciones existentes permanecen; editar metadatos avanzados no reconstruye su contenido.

`MenubarService` mantiene un solo resolvedor para navegación y previsualización, jerarquía completa y orden estable. Previsualizar requiere lectura y, para un borrador, gestión; no guarda ni ejecuta destinos ni genera auditoría. Sin registro, explica el contexto faltante sin elegir IDs.

Los destinos reutilizan Gate y políticas del dominio. Configurar no concede acceso; el servidor destino mantiene autorización propia. Los cambios por elemento y su auditoría comparten transacción. Se impiden ciclos y eliminación accidental en cascada de grupos con contenido.

La navegación ordinaria interna usa Inertia y un registro de formularios activos
derivado de isDirty, sin duplicar sus datos. Un guardián central confirma salidas,
cambios de selección/contexto y navegación histórica; protege también recargas
mediante beforeunload. Los metadatos del historial conservan el payload de Inertia.
Un cambio forzado de contexto limpia el registro antes de redirigir. Las
activaciones históricas permanecen válidas por su ruta exacta aunque estuvieran
asociadas a un módulo distinto del catálogo nuevo.

## Consecuencias

No hay migraciones, permisos nuevos, variables ni operaciones financieras. El seeder sigue siendo aditivo/idempotente y no se ejecuta automáticamente en datos operativos. El contexto temporal de permisos se restaura después de navegar o gestionar.

El editor guiado, la barra compacta y la protección de formularios son incrementos dependientes. Se mantiene PrimeVue Menubar con estilos propios. La barra cambia por pantalla, no ofrece los mismos accesos en toda la aplicación.

## Referencias

- [Contrato del Menubar](../menubar-items.md).
- [Configurar catálogo contextual](../how-to/configurar-menu-contextual.md).
- [Seguimiento](../agent-notes/menubar-usabilidad.md).
