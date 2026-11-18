# Menubar: usabilidad y configuración contextual

## Referencias y base

- [Tarea en Notion](https://app.notion.com/p/41361db7db7f81968ea5cfc45a96b51d).
- Base tras actualizar origin: 8884a78d738f9623c5d72fc194c4ecc55b400874, PR #83 y #84 integradas.
- Primera entrega: feat/menubar-editor, worktree propio menubar-ui-worktree.
- Segunda entrega dependiente: feat/menubar-navegacion, worktree propio menubar-nav-worktree.
- La rama auxiliar feat/menubar-catalogo no se publica como PR independiente.

## Estado al 2026-11-18

Catálogo y editor implementados y verificados; [PR #85 publicada](https://github.com/jata-imk/kronik/pull/85)
con head 9c6257b y base 8884a78, pendiente de revisión humana e integración. La
barra compacta y la protección de navegación están en verificación en la segunda
entrega. Sin integración ni despliegue autorizados. La aceptación del sidebar
sigue pendiente y es independiente. Se preservan checkout, archivos, servicios
y trabajo de comunicaciones de Bryan.

## Decisiones confirmadas

Configuración compartida por instalación; 19 módulos; acciones contextuales por
pantalla; permisos existentes separados; guardado explícito por elemento.
Opciones técnicas avanzadas conservadas. PrimeVue Menubar con grupos de texto y
flecha, iconos opcionales en submenús y Configurar neutro. Tipografía compacta
solo en la barra, objetivos móviles de 44 px. Descarte confirmado, sin borradores
persistentes ni altas silenciosas. [ADR 0047](../explanation/adr-0047-menubar-contextual-compartido.md).

Se agrupan catálogo y editor en una PR para evitar que el validador de pantallas
canónicas deje incompatible el editor anterior. La segunda depende de esta y
cubre barra/navegación. No se rehace el sidebar ni se integran otras iniciativas.

## Evidencia de la primera entrega

- Código funcional dd19836: push con hook completo, 997 pruebas backend aprobadas,
  2 omitidas, 47 827 aserciones; 314.78 s. Build correcto.
- Suite frontend: 69 archivos, 190 pruebas aprobadas.
- E2E con harness Chromium, SQLite sintética y puertos propios: siete casos del
  Menubar aprobados en ejecuciones focalizadas (cuatro combinaciones de viewport
  y tema, consulta/teclado/recarga sin escrituras, validación española y lector
  sin gestión). Tres recorridos de consulta administrativa también aprobados.
- MCP durante inventario, prototipo y UI real. La propuesta no se guardó. Las
  capturas finales son de la implementación, con fixtures sintéticos:
  [editor](../audits/menubar-2026-11-18/editor-vista-previa.png),
  [previa oscura](../audits/menubar-2026-11-18/editor-previa-oscuro.png) y
  [previa móvil](../audits/menubar-2026-11-18/editor-previa-movil.png).
- Se inspeccionaron trazas, capturas, vídeo y consola de fallos. Los ajustes del
  harness esperan selección, cierre y actualización de URL antes de continuar;
  usan filtro visible del catálogo y teclado de InputNumber. Los inicios de
  sesión de perfiles distintos usan contextos de prueba independientes para no
  heredar la URL intended de consultas geográficas de una pantalla anterior.

## Revisiones medium

Todas contra la base funcional 8884a78, salvo indicación:

- 64b5a5c: corregir autorización de roles en equipo seleccionado.
- 720644d: agrupar backend/editor y alinear destinos aceptados con autorización.
- 692cd7c: explicar rechazo al borrar grupo con contenido y alinear orden de borrador.
- 5e41891: conservar agregado rápido al validar y activaciones históricas de otro módulo.
- c1d4f66: corregir fixture de pivote y refrescar grupos tras eliminación.
- dd19836: revisión completada sin hallazgos; 32 pruebas focalizadas de Menubar,
  514 aserciones. El intento previo interrumpido por límite de uso no cuenta como
  revisión completada.

Los cambios posteriores de esta rama son evidencia, documentación y harness E2E.
La segunda entrega tendrá revisión propia y barrido acumulado contra 8884a78.

Segunda entrega: revisiones de 7e70241 y 290c8ca contra la primera PR corrigieron
selección por ID, cancelaciones que ocultaban captura, base de roles, referencias
anidadas de producto y formularios de diálogos ya cerrados. Se añaden regresiones
con useForm real, no solo mocks. Los metadatos del menú se refrescan también en
recargas parciales para retirar capacidades revocadas. Dos E2E de navegación
(salida Inertia y atrás/recarga) aprobados; agregado y enlace modificado se repiten
con las mismas respuestas geográficas sintéticas del harness. Suite completa,
revisión propia final y barrido conjunto todavía en curso.

## Siguiente paso y límites

La primera entrega está publicada en la PR #85. Finalizar pruebas/revisión de la
barra y los formularios, conservar capturas, publicar la segunda PR dependiente y registrar el barrido
conjunto. Mantener Notion en curso hasta aceptación. No hay migraciones,
variables nuevas ni ejecución del seeder en datos operativos. El seeder continúa
aditivo. Se reutilizan eventos auditables de gestión; consulta, previa y cambios
visuales no requieren eventos adicionales.

## Pausa solicitada por el usuario — 2026-11-18

Tarea pausada, sin integración ni despliegue. Main verificado en
8884a78d738f9623c5d72fc194c4ecc55b400874. La PR
[#85](https://github.com/jata-imk/kronik/pull/85) permanece abierta.
La segunda entrega está guardada localmente en `feat/menubar-navegacion`,
commit funcional/de pruebas 4e97d10, sin publicar una segunda PR.

Antes de la pausa terminaron correctamente el build y la suite frontend
(71 archivos, 199 pruebas); también las dos regresiones de diálogos ejecutadas
por separado. Evidencia local: `menubar-nav-build4.log`,
`menubar-nav-unit-full2.log` y `menubar-nav-unit-dialogs.log`, en
`storage/framework/testing/` de este worktree. La última revisión medium
(`menubar-review-nav-3.log`) se interrumpió por la pausa y no cuenta como
completada. Se detuvieron los servidores propios de exploración 8033 y 8036.

Al retomar: actualizar referencias y registrar de nuevo la base real; completar
la revisión medium, validar el cambio de recargas parciales en backend, repetir
los dos E2E de navegación pendientes con geocodificación sintética y comprobar
sidebar/contexto de sucursal. Conservar las capturas finales locales, completar
documentación y barrido acumulado contra la base funcional antes de publicar
la PR dependiente. La aceptación del sidebar por el usuario continúa pendiente.
