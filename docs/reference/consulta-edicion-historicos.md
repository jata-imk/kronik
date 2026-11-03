# Consulta, edición e históricos

## Contrato

Lectura y escritura son capacidades distintas. El servidor autoriza cada petición
y calcula editabilidad según permiso y estado. Super Admin no omite inmutabilidad.
Reutilizar formularios para configuración; los documentos y movimientos conservan
sus visores especializados. Nunca serializar secretos ni ampliar lectura reservada.

El ID seleccionado se resuelve independientemente de la página y filtros. Un ID
ajeno o inexistente devuelve 404; no se reemplaza por la última versión. Al cargar
no mostrar datos anteriores como si correspondieran al nuevo ID. Cambiar recurso,
modo o contexto limpia formularios y cancela solicitudes; respuestas tardías se
descartan. Se conserva el contrato de [contexto y paginación](contexto-sucursal-consultas.md).

Consulta no guarda, activa, retira, duplica, sube recursos ni ejecuta autosave.
También protege Enter, atajos, pegado, arrastre y comandos del editor. Navegación,
ayuda, búsqueda de variables y PDF autorizado siguen disponibles. Las acciones
de gestión pueden estar fuera del formulario. GET no crea recursos de negocio;
los eventos de lectura expresamente auditables conservan sus emisores.

No completar históricos con defaults actuales. Mostrar ausencia y códigos no
soportados sin reinterpretarlos. El editor comparte campos con consulta, no su
normalización de captura. Faltantes explican qué falta, dónde y el siguiente paso,
con enlaces autorizados; una advertencia no introduce un bloqueo de negocio.

## Interfaces de productos

El catálogo admite producto_id, version_id y modo=consulta|edicion. Entrega
productoSeleccionado.versionSeleccionada separado de versiones y ultimaVersion,
con puede_editar según permiso y ausencia de usos. modoVersion expresa la entrada;
el formulario solo edita si puede_editar es verdadero. Sin modo abre el catálogo.
Cerrar conserva ID, filtros y páginas. Las mutaciones mantienen sus endpoints y
validaciones. Consultar no registra actividad de producto.

## Matriz de comportamiento y evidencia

Observación reproducida sobre base `33c79c4`; la nota de entrega distingue
código local, revisión, integración y QA. Las rutas de modificación conservan
su autorización: entrar en consulta no concede capacidad de escritura.

| Superficie | Esperado | Observado en la base | Brecha cubierta | Evidencia / pruebas |
| --- | --- | --- | --- | --- |
| Producto y versiones | Detalle completo de cualquier ID autorizado | Versiones no editables solo tenían resúmenes; los editores fiscal/mora ya tenían readonly | Mismo editor con datos guardados, capacidad backend y selección exacta | `ConsultaProductosTest`, `ProductosCrediticios/Index.test.js`, E2E productos; conservar pruebas de dominio existentes |
| Plantilla y versiones | Activa/retirada/histórica consultable, PDF autorizado | UI abría editor solo para borrador; instancia antigua de borrador podía modificar activa por servicio/modelo | Consulta con encabezado/cuerpo/pie/presentación; Quill sin comandos; estado fresco dentro de bloqueos | `Documentos/ConsultaPlantillasTest`, `DocumentEditor.test.js`, E2E consulta-versiones y documentos-editor; PDF/recursos existentes |
| Política | Número independiente, historia consultable, aviso temprano | GET exigía gestión; número de producto se confundía con política; creación sobre retirada permitida | Lectura separada; ID exacto, condiciones completas, bloqueo de creación en retirada y enlaces estructurados | `ConsultaPoliticasTest`, `SolicitudesTest`, componente política y E2E; aprobación y envío existentes |
| Usuarios | Leer formulario con asignaciones completas | Solo edición visible; URL directa de lector abría controles de escritura aunque PUT rechazaba | Consulta, sin defaults en asignaciones vacías, cancelación de búsquedas y recarga parcial | `Admin/ConsultaAdministracionTest`, `Admin/Users/*.test.js`, E2E consulta-administracion; ciclo de cuentas existente |
| Sucursales | Leer incluso inactiva y conservar Folios | Resumen/listado; formulario solo por acción de edición | Mismo formulario readonly, ID fuera de página, cierre completo por botón/Escape/X | `Admin/ConsultaAdministracionTest`, `Admin/SucursalTest`, `ListadosServidorTest` y E2E |
| Empresa | Consultar sin crear configuración | GET ejecutaba firstOrCreate; lector tenía controles aunque PUT rechazaba | GET puro, ausencia explícita, formulario readonly y secreto redactado | `Admin/ConsultaEmpresaMenuTest`, `Admin/EmpresaConfiguracionTest`, `ConsultaConfiguracion.test.js` y E2E |
| Menú | Consulta por ID y gestión autorizada/auditable | UI mostraba mutaciones a lectores; middleware protegía; no había emisor específico | Mismo formulario readonly con valor/parámetros exactos y eventos transaccionales de gestión | `Admin/ConsultaEmpresaMenuTest`, `ConsultaConfiguracion.test.js`, E2E; conservar resolución/seeder existentes |

Las consultas nuevas no son acciones de lectura expresamente auditables y no
requieren eventos adicionales. Se conservan los eventos existentes de PDF privado,
expediente y evidencia; no se registra contenido documental ni criterios de política.
El alta, edición y eliminación de menú sí se incorporan a la matriz canónica.

Las navegaciones entre selecciones usan visitas Inertia que cancelan la anterior;
no aplican respuestas HTTP manuales al formulario. Las búsquedas remotas de usuario
usan AbortController y revisión por búsqueda, y se cancelan al desmontar. El editor
se identifica por recurso/modo; la misma consulta se actualiza al recargar props y
la misma edición conserva captura/errores. El editor Quill se remonta por versión,
modo y sección para limpiar historial y recursos. No se añaden autosaves.

## Inventario contrastado

BD local, SELECT de modules, 2026-11-02: 19 módulos, coincidentes con el seeder.
Estado de esta entrega: ver [Agent Note](../agent-notes/consulta-edicion-historicos.md).

| Módulo | Tratamiento |
| --- | --- |
| productos-crediticios | Consulta completa implementada en incremento 1; originación en incremento 3 |
| plantillas-documentos | Consulta/editor e inmutabilidad: incremento 2 |
| users | Consulta del formulario: incremento 4 |
| sucursales | Consulta del formulario y Folios: incremento 4 |
| configuracion-empresa | Consulta sin escritura e inicialización solo al guardar: incremento 5 |
| menubar-items | Consulta por ID, capacidades y auditoría: incremento 5 |
| roles | Matriz readonly existente; conservar |
| teams | Detalle existente y capacidades del equipo; conservar |
| clientes | Formulario readOnly y expediente existentes; conservar |
| solicitudes | Detalle existente; historia completa de revisiones queda como continuación |
| evaluacion-solicitudes | Consulta reservada de dictámenes existente; paginar historia posteriormente |
| cumplimiento | Consulta de especialidad existente; paginar historia posteriormente |
| creditos | Calendarios y recibos históricos existentes; ampliar condiciones en otra entrega |
| documentos | Visor privado e históricos existentes |
| historial-crediticio | Evidencia especializada y contexto original existentes |
| circulo-credito | Captura de nueva operación; resultado se consulta en historial |
| activity-log | Consulta especializada existente |
| dashboard | Indicadores/navegación; no requiere formulario readonly |
| admin | Navegación; no requiere formulario readonly |

La matriz de UX no sustituye la [matriz única de auditoría](auditoria-eventos.md).
No hay permisos nuevos ni aislamiento por sucursal de productos/plantillas.

## Interfaces administrativas

Usuarios conserva `edit_user_id` como ID exacto y añade `modo=consulta|edicion`.
El servidor entrega `modoUsuario`: sin actualización efectiva fuerza consulta.
Invitar sigue requiriendo creación; la consulta no usa preselecciones de captura
para completar asignaciones vacías. Cambiar usuario o capacidad remonta el mismo
formulario y cancela búsquedas pendientes; errores de la misma edición se conservan.

Sucursales conserva `selected_sucursal_id`, selección independiente y Folios.
`modoSucursal` distingue el formulario de consulta/edición del panel de folios.
El modo efectivo también comprueba permisos compartidos; inactiva se puede
consultar y su edición conserva las reglas existentes. No se montan widgets de
captura postal ni telefónica en consulta. PUT sigue autorizado en backend.
Estas consultas no agregan eventos: no cambian usuarios, asignaciones ni folios;
los eventos existentes de gestión mantienen actor y contexto originales.

## Empresa y menú: props de consulta

Empresa entrega `configurada`, `puedeEditar` y `modoEmpresa`; GET ausente no
crea datos y PUT inicial sigue autorizado y auditado. `modo=consulta` permite al
gestor consultar. Las capacidades compartidas se comprueban también en el formulario.

Menú entrega `menubarSeleccionado` por ID exacto y `modoMenu` efectivo. Se
conservan `menubarCatalogo` paginado y `menubarItems` de navegación como props
diferentes. La consulta no reconstruye JSON condicional, parámetros, orden ni rutas
de activación. El padre seleccionado se conserva aunque quede fuera de página.
