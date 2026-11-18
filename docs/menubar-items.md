# Sistema MenubarItems

## Modelo compartido

El catálogo es común para toda la instalación. Cada elemento comparte etiqueta,
icono, grupo y orden entre sus pantallas asignadas; la visibilidad cambia según
la pantalla. No hay preferencias individuales ni separación por financiera.
Ver [ADR 0047](explanation/adr-0047-menubar-contextual-compartido.md) y
[seguimiento](agent-notes/menubar-usabilidad.md).

`MenubarItem` conserva `type`, `value`, `params`, `parent_id` y `sort_order`.
`menubar_item_module.routes` contiene nombres completos de las pantallas donde
aparece. Destino y aparición son conceptos distintos. Los grupos se muestran
cuando tienen descendientes disponibles, aunque ellos mismos no tengan activación.

## Catálogo de acciones y pantallas

`config/menubar.php` alimenta `MenubarItemsSeeder` y `MenubarCatalogoService`.
Los parámetros se derivan de las rutas registradas, nunca de un singular supuesto
del módulo. Se excluyen escrituras y búsquedas JSON (`solicitudes.clientes`,
`consultas-sic.clientes`). Las entradas ya guardadas no se borran al actualizar.
El seeder sigue siendo aditivo/idempotente y no activa entradas nuevas.
Consultar [carga y operación](how-to/configurar-menu-contextual.md).

## Resolvedor y autorización

`HandleInertiaRequests` comparte `menubarItems`, calculado por `MenubarService`.
El resolvedor carga la jerarquía completa, mantiene orden `sort_order, id` y
produce claves estables y `kind=system|group|action`. Inicio/Regresar son entradas
automáticas independientes del catálogo.

`MenubarDestinoService` reutiliza Gate y políticas del dominio. Configurar el
menú no concede permisos; el servidor destino conserva su autorización propia.
Los contextos temporales de permisos por equipo se restauran al terminar.

Una URL requiere el recurso exacto disponible en la ruta, con autorización y
relaciones válidas (por ejemplo pago perteneciente al crédito). Los marcadores
como `{cliente}` se resuelven por el nombre real del parámetro; no se eligen
registros recientes ni IDs alternativos. Una acción sin contexto puede responder
`disabled` y `reason`, sin URL, si el perfil tiene los permisos básicos requeridos.
Destinos desconocidos/no autorizados no se anuncian. URL externas admiten HTTP/HTTPS;
las internas deben corresponder a destinos autorizados. Regresar no usa orígenes externos.

## Tipos compatibles

| Tipo | Significado |
| --- | --- |
| `menu` | Grupo sin destino propio |
| `route:name` | Página registrada, con parámetros estructurados |
| `route:static` | Dirección fija segura |
| `route:referer_fallback` | Regresar, con destino de reserva |
| `route:dynamic` | Condiciones guardadas y destino predeterminado |

Las condiciones dinámicas usan coincidencia positiva de la ruta de origen y un
fallback explícito. No existe el antiguo `!preg_match()` indicado en documentación
previa. La consulta muestra contenido guardado sin reconstruirlo. Editar otros
metadatos conserva el contenido avanzado; cambiarlo exige validación.

## Interfaces administrativas

Se conservan CRUD y props `menubarCatalogo`, `menubarSeleccionado`, `modoMenu`
y `listado`. La selección por ID es independiente de filtros y paginación.
Consultar requiere lectura, no creación/actualización. La gestión conserva sus
permisos separados y auditoría transaccional en `MenubarItemService`.

| Interfaz | Contrato |
| --- | --- |
| `GET /admin/menubar-items/catalogo` | Devuelve `acciones`, `pantallas`, `grupos`. Lectura o gestión; los grupos guardados solo se incluyen con lectura. |
| `POST /admin/menubar-items/previsualizacion` | Recibe `screen`, `selected_item_id` opcional y `draft` opcional con campos CRUD. Lectura y, para borrador, creación/actualización. Devuelve `items`, `message`; no persiste ni ejecuta destinos. |
| `GET /admin/menubar-items/routes` | Compatibilidad avanzada: nombres de rutas GET; requiere lectura. |

Las respuestas auxiliares usan `Cache-Control: private, no-store`.
`modules: []` retira todas las activaciones. Se rechazan ciclos, orden inválido,
destinos no GET, direcciones inseguras y nuevas asociaciones a pantallas inválidas.
Eliminar un grupo con contenido requiere mover o eliminar sus elementos primero.

## Auditoría y validación

Crear/modificar/eliminar emite los eventos existentes `menubar_item.*` dentro de
la transacción. Un guardado sin cambios no emite un evento duplicado. Consulta,
previsualización y apertura visual de grupos no agregan eventos ni mutan negocio.
Ver [matriz de auditoría](reference/auditoria-eventos.md).

Pest cubre catálogo, permisos, contexto, rutas, parámetros, jerarquía, persistencia,
no efectos de vista previa y rollback de auditoría. El editor guiado y la barra
compacta se entregan en incrementos dependientes; la evidencia vigente se mantiene
en la nota de la tarea.
