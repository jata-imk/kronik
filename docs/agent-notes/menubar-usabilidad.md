# Menubar: usabilidad y configuración contextual

## Estado final — 2026-11-19

Entrega implementada, integrada y aceptada por Jose en QA. Tras recibir los
comandos de despliegue, el usuario confirma que ya la probó, que le gusta el
resultado y que las acciones configuradas reciben automáticamente el cliente.

Evidencia de QA reportada por el usuario:

- Aceptación de la experiencia del Menubar y su configuración.
- Agregar Abrir expediente navega con el parámetro del cliente resuelto.
- Agregar Nueva consulta SIC navega con el cliente precargado.

Se registra como validación del usuario en QA, sin acceso del agente a la VPS.
No se compartieron el hash efectivo del servidor, navegador ni viewport; no se
atribuyen esos datos ni casos adicionales a esta comprobación. La entrega
asociada es main 74461ab, entregada para despliegue antes de esta confirmación.
La aceptación cierra el alcance acordado del Menubar; la tarea del sidebar
conserva su aceptación independiente y pendiente.

## Entregas y base funcional

- [Tarea en Notion](https://app.notion.com/p/41361db7db7f81968ea5cfc45a96b51d).
- Base funcional: 8884a78d738f9623c5d72fc194c4ecc55b400874, con #83/#84.
- [PR #85](https://github.com/jata-imk/kronik/pull/85): catálogo y editor,
  merge be67e60b6c99e492f54ad411ce826ca902520626.
- [PR #88](https://github.com/jata-imk/kronik/pull/88): barra/navegación,
  retarget a main tras #85 y merge 1f4808962f06eff71b3a826ef0580a68f4db907c.
- [PR #89](https://github.com/jata-imk/kronik/pull/89): integración y guía QA,
  merge 74461ab89508320d003a05d8d1bb5dc2c7a5a49b.

Antes de integrar se verificó CI verde sobre los heads 56c9bfc (#85,
run 44410875964) y a7db44f (#88, run 44564537556), sin hilos abiertos.
El árbol funcional integrado coincide con a7db44f; #89 solo cambia documentación.
Las ramas de implementación fueron feat/menubar-editor y feat/menubar-navegacion;
feat/menubar-catalogo fue auxiliar. Se preservaron los checkouts, servicios y
trabajo de comunicaciones de Bryan.

## Contrato final

Configuración compartida por instalación, 19 módulos y permisos separados.
Destino y pantallas de aparición son independientes; se conserva configuración
avanzada y se autoriza cada destino con el contexto real. Sin recurso válido se
explica lo que falta, sin elegir IDs alternativos. Guardado explícito y vista
previa sin navegación, escrituras ni comandos.

PrimeVue Menubar/Menu: texto compacto, grupos con flecha, iconos opcionales en
submenús, Configurar neutro y objetivos móviles de 44 px. Navegación Inertia con
confirmación de descarte, historial y diálogos; los cambios forzados de contexto
retiran la captura anterior. Sin borradores persistentes ni altas silenciosas.

No hubo migraciones, dependencias, permisos ni variables nuevas. El seeder sigue
aditivo y opcional para completar catálogo; no se da por ejecutado en QA sin
reporte. Se conserva auditoría transaccional de gestión; consulta, vista previa,
apertura de grupos y guardas no requieren eventos adicionales.

## Evidencia automatizada y revisión

- #85, código dd19836: 997 backend, 2 omitidas, 47 827 aserciones; build correcto.
  Frontend: 190 pruebas. Siete E2E del menú y tres administrativos aprobados.
- Código funcional final 4221959: backend completo 999 aprobadas, 2 omitidas,
  47 865 aserciones; 490.75 s. Frontend completo: 75 archivos, 206 pruebas,
  223.59 s. Build: 58.62 s.
- E2E finales: 23 casos aprobados en ejecuciones focalizadas (7 editor y 16
  barra/sidebar/sucursal). El principal aprobó 15; el de recuperación de contexto
  se repitió correctamente tras ajustar la espera del harness. Se inspeccionaron
  captura, vídeo, traza y consola antes de corregir esa espera; no cambió código
  funcional. SQLite sintéticas, puertos propios y geocodificación simulada.
- Playwright MCP el 18/11 para inventario, propuesta e implementación; harness
  Chromium reproducible el 19/11. No se usó la base operativa para las pruebas.
- Review medium de #85 sobre dd19836 contra 8884a78 sin hallazgos después de las
  correcciones. Review propio de #88 sobre 4221959 contra 56c9bfc y barrido
  acumulado de ambas entregas contra 8884a78 sin hallazgos. Review adicional del
  ajuste final del harness sin hallazgos. Los intentos interrumpidos no cuentan.
- #89: review medium b574c15 contra 1f48089 sin hallazgos, 16 enlaces válidos y
  sintaxis Bash comprobada sin ejecutar despliegue. Sin pruebas funcionales nuevas.

Los logs permanecen en storage/framework/testing del worktree menubar-nav-worktree:
menubar-nav-backend-full.log, menubar-nav-unit-full4.log, menubar-nav-build5.log,
menubar-editor-e2e-final.log, menubar-nav-e2e-final.log,
menubar-context-e2e-final.log, menubar-review-nav-6.log,
menubar-review-acumulada-2.log y menubar-review-harness-final.log.

## Referencias y capturas

- [Contrato](../menubar-items.md) y [ADR 0047](../explanation/adr-0047-menubar-contextual-compartido.md).
- [Configurar el catálogo](../how-to/configurar-menu-contextual.md) y
  [despliegue/validación](../how-to/validar-menu-contextual.md).
- [Editor](../audits/menubar-2026-11-18/editor-vista-previa.png),
  [previa oscura](../audits/menubar-2026-11-18/editor-previa-oscuro.png) y
  [previa móvil](../audits/menubar-2026-11-18/editor-previa-movil.png).
- Barra: [antes, 1440 px](../audits/menubar-2026-11-18/barra-base-8884a78.png),
  [claro, 1920 px](../audits/menubar-2026-11-18/barra-escritorio-claro.png),
  [oscuro, 1920 px](../audits/menubar-2026-11-18/barra-escritorio-oscuro.png) y
  [móvil oscuro abierto, 390 px](../audits/menubar-2026-11-18/barra-movil-oscuro.png).

Capturas sintéticas inspeccionadas y conservadas. La evidencia automatizada y la
aceptación reportada acreditan los recorridos descritos; no cierran la tarea del
sidebar ni amplían el alcance a empleo, variables o comunicaciones.
