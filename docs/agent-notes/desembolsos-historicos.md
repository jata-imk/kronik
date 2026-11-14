# Desembolsos históricos y captura cronológica

## Referencias y estado — 2026-11-11

- [Tarea Notion](https://app.notion.com/p/41361db7db7f81f5ad52d7df919dee99), base funcional main 67bba2c.
- Backend: [PR #79](https://github.com/jata-imk/kronik/pull/79), código 2b2ccb1, evidencia documental 4057d70, rama feat/desembolsos-historicos.
- UI/aceptación: [PR #80](https://github.com/jata-imk/kronik/pull/80), dependiente de #79, código 86aa88f, rama feat/desembolsos-historicos-ui.
- [ADR 0045](../explanation/adr-0045-fechas-efectivas-captura-historica.md), [contrato](../reference/captura-historica-creditos.md), [recorrido QA y oráculos](../how-to/qa-desembolsos-historicos.md).

Implementado y probado en ramas, sin integración, despliegue ni aceptación manual del usuario en QA compartido. CI de los últimos heads se registra en las PR; no confundir su resultado con despliegue. No fusionar ni operar dinero real sin indicación.

El usuario autorizó activar borrador QA con vigencia efectiva pasada conservando activación real. Monolito modular: Producto/LOS valida, documentos conserva evidencia, aplicación autoriza/bloquea/registra/audita y los motores existentes calculan. Equipos son contextos de permisos de una financiera por instalación, no tenants. No hay cambios de fórmulas ni interpretación con versiones actuales.

Captura pendiente es control operativo separado del libro. Cierre explícito/idempotente no liquida, mueve dinero ni envía avisos acumulados. No hay emisor de atraso en esta base; consumidores futuros deben comprobar elegibilidad al programar y ejecutar. Canales y proyecciones mantienen sus tareas.

## Evidencia de implementación

- Backend 2b2ccb1: 955 aprobadas, una omitida preexistente y 47352 aserciones. [CI 43151430137](https://github.com/jata-imk/kronik/actions/runs/43151430137) aprobada: diez competencias multiconexión MariaDB por política (ocho anteriores, dos nuevas de cierre/pago histórico).
- UI 86aa88f: 958 backend aprobadas, una omitida preexistente y 47373 aserciones; 164 frontend aprobadas en 61 archivos. Cuatro guardas Node del harness aprobadas, repetidas tras renombrarlas fuera del patrón de descubrimiento de Playwright.
- E2E histórico: dos políticas aprobadas con operador sin bandera Super Admin, reloj real, PDF real, oráculo independiente de centavos, pagos/anticipos cronológicos, reverso, PDF conservado y cierre. Regresiones: tres recorridos Bullet/Productos y uno de crédito simple dual aprobados.
- Todas estas ejecuciones E2E crearon bases SQLite nuevas; nunca usaron BD operativa. Migración aditiva sin backfill; no aplicada en instalación operativa. Fixtures exclusivamente sintéticos.
- Pint y build aprobados. Hooks pre-push repitieron backend/build sin omitir controles. Tras rebase 60b4c88 → 86aa88f solo cambió la Agent Note; código funcional idéntico.
- Diagnósticos de intentos fallidos revisados: traza, captura, video y consola. Corregidos input-id de DatePicker y serialización civil; cliente de verificación Inertia incorpora versión para evitar 409. PDF requiere navegador con permisos del runner. Recorridos finales completos aprobados.

## Revisiones

Backend: review medium contra 67bba2c encontró dos P2 (fixture dual sin confirmación de vigencia pasada y true/1/"1" tratados distinto). Corregidos con regresión; segunda revisión sin defectos accionables. Disco fake por proceso evita colisión de ejecuciones; inventario del reinicio QA reconoce la tabla hija, probado solo en BD aislada.

UI 86aa88f: review medium contra 4057d70 sin defectos accionables. Barrido medium conjunto #79/#80 contra main 67bba2c, head 86aa88f: sin defectos accionables, incluyendo cronología, documentos/snapshots, permisos, auditoría, idempotencia y migración. Revisores inspeccionaron fuente sin ejecutar tests; implementador ejecutó suites separadas. El cierre posterior contiene documentación, capturas sintéticas y nombre del archivo de guardas; su revisión medium contra 86aa88f tampoco encontró defectos accionables. Detalles del head y CI final en las PR.

## Capacidad y pendientes reales

Consulta por servicios reales, Windows/PHP 8.3.16/SQLite en memoria, historias de 30/365/1825 días: tres consultas cada una, sin escrituras; observación local 9.069/628.607/5751.555 ms y memoria incremental 32744/11392/11440 bytes. No es SLA ni prueba de 10 mil créditos. El avance diario existente cuesta más para historias largas; seguimiento de proyecciones/capacidad permanece abierto.

Sin decisiones de negocio bloqueantes. Pendientes: revisión del usuario, autorización de integración en orden #79 → #80, despliegue y aceptación manual de QA siguiendo la guía; no se acredita cierre de la validación integral Bullet ni del roadmap amplio.

Árbol principal preservado: 14 hashes de archivos preexistentes idénticos y main local/remoto 67bba2c al cierre de implementación. Worktrees anteriores intactos; ambos trabajos en worktrees aislados bajo output/desembolsos-historicos. Diff/hashes previos y logs locales quedan fuera de la entrega.
