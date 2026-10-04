# Auditoría visual de QA — octubre de 2026

Revisión del ambiente QA y del flujo prioritario descrito en [`qa-credito-simple.md`](../how-to/qa-credito-simple.md). Las capturas de trabajo están en `.playwright-mcp/` y se excluyen del control de versiones porque pueden mostrar datos de prueba del entorno compartido.

## Cobertura del catálogo

La tabla `modules` de la base QA contiene **19 registros** al momento de la revisión: `dashboard`, `admin`, `configuracion-empresa`, `sucursales`, `users`, `roles`, `menubar-items`, `activity-log`, `clientes`, `historial-crediticio`, `circulo-credito`, `teams`, `productos-crediticios`, `plantillas-documentos`, `documentos`, `solicitudes`, `evaluacion-solicitudes`, `cumplimiento` y `creditos`. Los grupos de navegación nuevos cubren sus destinos operativos y administrativos; `documentos` comparte pantalla con `plantillas-documentos`. Expedientes KYC es una vista del módulo de clientes y no figuraba como módulo independiente, por eso se le añadió una ruta y entrada visibles. Perfil y equipo actual dependen de Jetstream y tampoco son registros separados en `modules`.

| Área | Hallazgo | Dirección |
| --- | --- | --- |
| Productos crediticios | Buena jerarquía, explicación de reglas y superficies contenidas. | Referencia de densidad y acciones. |
| Documentos y plantillas | Editor enfocado, estados y herramientas claros. | Referencia para edición y gestión documental. |
| Expediente KYC | Resumen y secciones útiles; faltaba entrada global. | Mantener patrón y añadir acceso directo. |
| Tablero | Solo bienvenida y enlace a Mi trabajo. | Resumen de sucursal, pendientes y accesos rápidos. |
| Clientes | Tabla muy ancha, botones de colores dispares. | Encabezado común, acciones semánticas y revisión móvil de columnas. |
| Solicitudes, evaluación, cumplimiento | Comparten bandeja, pero el encabezado y las acciones no seguían la misma jerarquía. | Encabezado común y tarea primaria visible; distinguir el propósito de cada bandeja. |
| Créditos y pagos | Degradado violeta y texto desactualizado sobre pagos. | Superficie Kronik y mensajes fieles al flujo QA. |
| Administración | Conjunto de botones sin agrupación clara. | Secciones Organización, Empresa y Control. |
| Perfil y equipo actual | Estructura Jetstream comprensible. | Conservar flujo y normalizar tokens y encabezados al renovarlos. |
| Empresa, sucursales, roles, actividad | Funcionales pero con variaciones de densidad y estilos. | Adoptar componentes y patrones globales en siguientes cambios del área. |
| Portada pública y autenticación | Componentes Sakai y textos ingleses visibles en la auditoría inicial. | Portada Kronik y acceso en español implementados. |

## Estado de implementación

Se establecieron tokens globales, menú de hasta tres niveles, entrada KYC, tablero operativo, agrupación del índice de administración, portada pública y encabezados del flujo principal. Los listados de clientes, expedientes, solicitudes y créditos usan tarjetas en móvil. El detalle KYC muestra todas sus secciones en una cuadrícula móvil. El menú contextual muestra solo acciones específicas de la página. El sistema compartido cambia tipografía, foco, superficies y botones PrimeVue en toda la app. Se revisaron la captura de solicitud, el desembolso bloqueado, sucursales y roles a 320px; las advertencias largas ya no ensanchan la cuadrícula. Queda por revisar el formulario de pago con un crédito sintético completo y las demás pantallas largas de administración.

Segunda pasada: los encabezados de rutas operativas, catálogos, administración, actividad y cuenta usan `PageHeader` con la misma jerarquía y fondo dependiente del tema. El resumen oscuro de Créditos permanece como bloque de contenido; el detalle KYC conserva su cabecera de expediente como patrón especializado. Se recorrieron en Playwright las rutas visibles del menú a 390px y se corrigió el desbordamiento de Productos crediticios. El selector de énfasis/superficie y el modo oscuro siguen activos; se comprobó su aplicación sobre la nueva cabecera. La validación visual profunda de formularios largos y estados secundarios continúa siendo trabajo posterior.

## Orden recomendado para la renovación restante

1. Terminar el recorrido prioritario: listado y formulario de clientes, expediente, solicitud, dictámenes, paquete, desembolso y pago. Revisar estados vacío/error y permisos por rol.
2. Normalizar tablas de administración y configuración (empresa, sucursales, roles, usuarios, equipos, actividad).
3. Completar la localización de las demás pantallas de autenticación y perfil que aún conserven textos Jetstream en inglés.
4. Cerrar con auditoría a 320/390/768/1024/1440px, tema oscuro, teclado y zoom 200%; adjuntar evidencia sin datos personales.

## Pendientes inmediatos tras la ronda visual

- **Mapa QA (403):** el servidor comunitario de mosaicos OSM bloquea el dominio QA. Se corrigió la URL al host oficial y se añadió atribución y aviso de error, pero hace falta contratar o seleccionar un proveedor autorizado para QA y producción, configurar la URL por entorno y verificar política de caché y cabeceras `Referer`.
- **Dos factores:** se corrigió la referencia a un formulario inexistente que rompía el renderizado al habilitarlo. El QR y el campo OTP vuelven a mostrarse en local; falta probar en QA el flujo completo hasta confirmar, iniciar sesión con OTP, usar recuperación y desactivar.
- **Despliegue visual:** verificar la landing con capturas, el login, la navegación móvil, la tabla de productos y el zoom 200 % en QA. Las capturas de la landing difuminan las filas de datos de prueba.

Estos pendientes quedaron registrados como [tarea de prioridad alta en Notion](https://app.notion.com/p/3ee61db7db7f8167a6b4c07215f0fb4e), asociada al proyecto Kronik.
