# Sistema MenubarItems

## Barra y navegación

La barra usa PrimeVue Menubar con texto y flecha en los grupos superiores e iconos
opcionales dentro. En escritorio usa 13 px; en móvil mantiene objetivos de 44 px.
Configurar es un control neutro: Agregar acción aquí requiere creación y
Administrar menú requiere lectura. No depende de Super Admin. Las acciones del
sistema se distinguen por identidad, sin reservar etiquetas de grupos del usuario.

Un fallo de resolución se muestra como error con reintento, distinto de un menú
vacío. La vista previa y el menú real comparten el renderizador accesible.
Los enlaces internos ordinarios usan Inertia; conservan href, modificadores,
nueva pestaña, sitios externos y visores documentales con navegación nativa.

Los formularios Inertia de las pantallas operativas se registran mediante
useGuardedForm. Al salir a otro recurso, volver/avanzar en el historial o cambiar
equipo/sucursal se confirma el descarte. Cancelar conserva los datos en memoria.
Los filtros que conservan el estado del formulario y los envíos de guardado no
se bloquean. La captura sigue protegida ante otras salidas durante un envío
pendiente; los filtros que desmontan la captura también requieren descarte. Recarga,
salida externa y cierre de pestaña usan la confirmación nativa del navegador,
cuyo texto decide el navegador. No se crean borradores persistentes ni autosave.
Los cambios forzados de contexto o sesión limpian el registro antes de salir:
no se ofrece conservar datos del contexto anterior.
El historial confirma también entre filtros de una misma ruta, porque Inertia
restaura esas entradas desmontando el componente anterior.

Las bases de formularios con objetos o listas se mantienen independientes de la
captura, para detectar también cambios anidados. Al aceptar la cancelación de
un diálogo se limpia su formulario; no queda un borrador invisible bloqueando
salidas. Cancelar la confirmación conserva diálogo, datos y errores.

Las activaciones históricas se resuelven por la pantalla exacta guardada, aunque
su asociación de módulo sea anterior al catálogo canónico.

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
no efectos de vista previa y rollback de auditoría. Editor y barra reutilizan este
contrato. La [guía de validación](how-to/validar-menu-contextual.md) describe las
regresiones; la evidencia por commit se mantiene en la nota de la tarea.

## Editor guiado y agregado rápido

Administración ofrece un árbol con etiquetas humanas, búsqueda de descendientes a
cualquier profundidad y selección por ID exacto. El mismo formulario sirve para
consulta, creación, edición y agregado rápido, sin duplicar reglas de captura.

El recorrido habitual ofrece acción existente, etiqueta, icono opcional, grupo,
orden y pantallas. Qué abre no cambia al modificar Dónde aparece; los parámetros
no se regeneran por módulos. Un grupo vacío puede guardarse para organizarlo después.
Sin activaciones, las acciones quedan guardadas sin aparecer en la barra.

Las opciones avanzadas permiten consultar/editar destinos y parámetros originales.
No se reconstruyen condiciones desconocidas al abrir ni al editar una etiqueta.
La vista previa solo se solicita al pulsar Ver vista previa. Permite abrir grupos,
carece de href/comandos y no persiste. Cambiar borrador/pantalla invalida respuestas
anteriores; cerrar cancela solicitudes pendientes. El agregado rápido preselecciona
la ruta completa de la pantalla real, no una acción index supuesta.

Guardar aplica un elemento. Cancelar/cerrar una edición sucia permite Seguir
editando o Descartar cambios. Escape dentro de un desplegable cierra ese desplegable,
sin cerrar el formulario ni abrir otra confirmación. Consulta no ofrece Guardar,
selectores de captura activos ni envíos mediante Enter.
