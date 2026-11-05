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
