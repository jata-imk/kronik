# Menubar: usabilidad y configuración contextual

## Referencias y base

- Reanudación 2026-11-19: `git fetch origin --prune` confirma origin/main en
  8884a78d738f9623c5d72fc194c4ecc55b400874, sin avances desde la base funcional.
  Rama de trabajo limpia en 73b9c05 antes de reanudar; PR #85 pendiente.
- [Tarea en Notion](https://app.notion.com/p/41361db7db7f81968ea5cfc45a96b51d).
- Base tras actualizar origin: 8884a78d738f9623c5d72fc194c4ecc55b400874, PR #83 y #84 integradas.
- Primera entrega: feat/menubar-editor, worktree propio menubar-ui-worktree.
- Segunda entrega: [PR #88](https://github.com/jata-imk/kronik/pull/88),
  feat/menubar-navegacion, worktree propio menubar-nav-worktree; depende de #85.
- La rama auxiliar feat/menubar-catalogo no se publica como PR independiente.

## Estado al 2026-11-19

Catálogo/editor en la [PR #85](https://github.com/jata-imk/kronik/pull/85),
head documental 56c9bfc, base 8884a78; abierta, sin conflictos y CI verde
([run 44410875964](https://github.com/jata-imk/kronik/actions/runs/44410875964)).
El primer intento falló por timeout del renderer PDF; el reintento pasó sin
cambiar código/configuración PDF.

Barra y protección de captura publicadas para revisión en la PR #88.
Código funcional final 4221959289248564ba2ecb82ee4671938d9c69dd; a3b739e
añade un recorrido E2E sin modificar la implementación. Revisiones medium
propia y acumulada completadas sin hallazgos; 23 casos E2E finales aprobados.
CI del código funcional verde sobre 30ac59e
([run 44562730776](https://github.com/jata-imk/kronik/actions/runs/44562730776)).
El último ajuste solo modifica documentación y la espera del harness; su
actualización de CI puede consultarse en [Checks de #88](https://github.com/jata-imk/kronik/pull/88/checks).
Sin integración ni despliegue. La aceptación del sidebar sigue independiente.
Se preservan checkout, archivos, servicios y comunicaciones de Bryan.

## Evidencia de la segunda entrega

- Backend completo: 999 aprobadas, 2 omitidas, 47 865 aserciones; 490.75 s.
  `menubar-nav-backend-full.log` en el storage de pruebas del worktree.
- Frontend completo sobre 4221959: 75 archivos, 206 pruebas; 223.59 s.
  `menubar-nav-unit-full4.log`. Incluye formularios Inertia reales para producto,
  roles, solicitudes, permisos de tokens, expediente y diálogos.
- Build correcto sobre el código final: `menubar-nav-build5.log`, 58.62 s.
- Backend focalizado: 34 pruebas, 552 aserciones. La recarga parcial usa versión
  Inertia y comprueba retirada de capacidades sin mantener props antiguas.
- E2E finales del build revisado: 23 casos aprobados en ejecuciones focalizadas.
  Editor: siete, 5.8 min (`menubar-editor-e2e-final.log`). Barra/sidebar/sucursal:
  15 aprobados en el recorrido principal y uno repetido correctamente, 1.7 min
  (`menubar-nav-e2e-final.log`, `menubar-context-e2e-final.log`). Bases SQLite
  nuevas, puertos propios 8035/8037/8038 y proveedores geográficos sintéticos.
  El caso de contexto agotó primero los 90 s al esperar load de una entrada
  histórica que se sustituye durante la recuperación. Tras inspeccionar captura,
  vídeo, traza y consola, el harness espera el commit y verifica URL/alcance
  finales; admite 180 s para el recorrido completo. La captura anterior se
  retiró correctamente y no cambió código funcional por ese ajuste.
- MCP se usó el 18/11 durante inventario, propuesta e implementación. En esta
  reanudación no está disponible: se usa el harness Chromium reproducible.

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

Segunda entrega contra feat/menubar-editor (56c9bfc):

- 7e70241 y 290c8ca: corregidos selección por ID, cierres que ocultaban captura,
  bases de roles/productos y formularios de diálogos cerrados.
- 73b9c05: corregidos protección durante envíos, filtros que desmontan captura
  y base/cierre de permisos de tokens. También se protege seleccionar un cliente.
- 3223060: corregidos historial de filtros y bases/cierre de los siete diálogos
  del expediente, incluidos archivos y claves de idempotencia.
- 4221959: revisión propia final sin hallazgos (`menubar-review-nav-6.log`),
  con 12 pruebas focalizadas aprobadas por el revisor.
- 4221959: barrido acumulado de ambas entregas contra la base funcional
  8884a78d738f9623c5d72fc194c4ecc55b400874 sin hallazgos
  (`menubar-review-acumulada-2.log`); pruebas de guardas/editor aprobadas.
- Ajuste final del harness sobre 30ac59e: revisión medium del diff pendiente
  sin hallazgos (`menubar-review-harness-final.log`), manteniendo las aserciones
  de destino y sucursal activa.

Las revisiones interrumpidas por límite de uso o pausa no cuentan como completas.
Los commits posteriores al último código revisado solo añaden pruebas/evidencia
y documentación. No quedan hallazgos conocidos pendientes de estas revisiones.

## Siguiente paso y límites

Revisión humana de #85 y después #88; al integrar #85, cambiar la base de #88 a
main y comprobar su estado antes de una integración autorizada. Mantener Notion
en curso hasta aceptación. No hay migraciones,
variables nuevas ni ejecución del seeder en datos operativos. El seeder continúa
aditivo. Se reutilizan eventos auditables de gestión; consulta, previa y cambios
visuales no requieren eventos adicionales.
Las publicaciones finales omiten repetir el hook local porque su suite backend
y build ya pasaron y los cambios posteriores solo son evidencia/harness. Los
servidores propios de estas pruebas terminaron; no quedan listeners 8035/8037/8038.

Capturas sintéticas de la barra revisadas visualmente: [base 8884a78, 1440 px](../audits/menubar-2026-11-18/barra-base-8884a78.png),
[escritorio claro, 1920 px](../audits/menubar-2026-11-18/barra-escritorio-claro.png),
[escritorio oscuro, 1920 px](../audits/menubar-2026-11-18/barra-escritorio-oscuro.png) y
[móvil oscuro, 390 px](../audits/menubar-2026-11-18/barra-movil-oscuro.png).
Se conservan en el repositorio; muestran texto compacto, grupos con flecha y el
control Configurar neutro. La captura móvil muestra el menú abierto.

## Traspaso entre sesiones

Pausa solicitada el 18/11, conservada en 73b9c05 sobre código 4e97d10.
Se detuvieron únicamente los servidores propios 8033/8036 y la revisión en curso.
La reanudación del 19/11 volvió a actualizar referencias y verificar la base
antes de continuar. Las pendientes reales se enumeran arriba; la aceptación
del sidebar por el usuario sigue separada.
