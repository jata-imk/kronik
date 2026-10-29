# Equipo: tarjetas y filtros de bitácora

## Referencias

- Solicitud del usuario y decisiones confirmadas el 2026-10-28.
- Rama: `feat/equipo-tarjetas-bitacora`; base `f5455e6`.
  [PR #55](https://github.com/jata-imk/kronik/pull/55).
- Decisión: [ADR 0031](../explanation/adr-0031-consulta-actividad-equipo-sucursal.md).

## Estado actual

- 2026-10-28: integrado en `main` mediante PR #55, merge
  `74d9657add8d20ac0d817f65bad6e0daa799e6a6`, HEAD revisado `f2512e3`.
  Revisión medium y pasada final medium sin hallazgos; no se necesitaron
  correcciones de código. CI de la PR aprobado, incluida concurrencia MariaDB.
  El usuario confirmó su despliegue en VPS y revisión: solo reportó tarjetas
  demasiado grandes cuando hay pocos roles. Sin SHA desplegado ni versiones
  informados. La corrección visual se sigue en `fix/equipo-tarjetas-pocos-roles`.
- Acciones horizontales, resumen de roles con personas activas en tarjetas
  cuadradas y tarjeta administrativa primera. Se conserva el catálogo completo
  de roles para administrar/invitar y solo el enlace contextual a bitácora.
- Corrección visual: reservar las columnas disponibles aunque haya una o dos
  tarjetas, conservando el tamaño de la cuadrícula con más roles. No cambia
  filtrado, permisos ni acciones; no requiere nuevos eventos auditables.
- Cierre autorizado por el usuario: completar e integrar la corrección y dejar
  `main` local limpio. El despliegue posterior de la corrección queda a su cargo;
  la autorización de cierre no constituye verificación de ese despliegue.
- Equipo/Sucursal compartidos en listado y CSV. Permiso del equipo permite todas
  sus sucursales; Super Admin global conserva historia sin equipo. Consulta y
  opciones centralizadas; no cambia equipo/sucursal actuales.
- Sin nuevas acciones auditables: presentación y consulta; no se alteran emisores
  de negocio ni se duplica actividad por filtrar. Matriz y contrato revisados.

## Evidencia

- HEAD `f2512e3`: backend completo aprobado, 528 pruebas, 1 omitida y 41 431
  aserciones. Build aprobado en el pre-push. Backend dirigido: 22 pruebas,
  328 aserciones, también aprobadas en revisión medium.
- Suite completa Vue: 112 pruebas aprobadas en la implementación `9c8d2d0`.
  Pint aprobado; 115 enlaces locales sin faltantes. El revisor no pudo iniciar
  Vue por el sandbox de Vite; se conserva la evidencia local completa independiente.
- [CI del HEAD integrado](https://github.com/jata-imk/kronik/actions/runs/40816334356)
  aprobado: documentación, build, Vue, backend y concurrencia real MariaDB.
  La pasada final fue estática sobre `main...HEAD`, sin modificar archivos.
- Tres E2E Chromium aprobados: tarjetas/acciones/filtros/CSV, contexto/transferencia
  de responsable y acciones de integrantes. Tarjetas cuadradas entre 320 y 1440 px
  con nombres largos. El evento de transferencia se comprueba en Bitácora tras
  retirar el listado de Equipo conforme a lo solicitado.
- Corrección de pocas tarjetas: build y E2E Chromium de Equipo/bitácora aprobados.
  La regresión visual comprueba que una y dos tarjetas conservan el ancho de la
  cuadrícula con cuatro a 1920 px; mantiene cuadrados y recorridos entre 320 y
  1440 px. [Captura con dos tarjetas](../assets/equipo-dos-tarjetas-desktop.png).
- Capturas: [escritorio](../assets/equipo-tarjetas-desktop.png),
  [móvil](../assets/equipo-tarjetas-mobile.png),
  [oscuro](../assets/equipo-tarjetas-dark.png),
  [filtros](../assets/bitacora-contextos-desktop.png).
- Confirmación anterior del usuario: despliegue normal y QA satisfactorio en
  VPS de estatus/folios; se actualizan sus notas y Notion. Esta confirmación no
  acredita despliegue ni QA del ajuste nuevo.
- Sin migraciones, variables o seeders productivos nuevos. Solo fixtures E2E
  sintéticos en la base aislada.

## Siguiente paso

Validar el ajuste de pocas tarjetas en navegador, publicar PR, revisión medium
y pasada final antes de integrar con CI aprobado. Después desplegar únicamente
esta corrección visual y comprobar el tamaño con pocos roles. El despliegue y
revisión de PR #55 ya fueron reportados por el usuario; no volver a dejarlos
como pendientes. No requiere migraciones, seeders ni variables nuevas.

Logs locales: `storage/framework/testing/equipo-push-final.log`,
`equipo-review-medium.log`, `equipo-review-final.log`, `equipo-vue.log` y
`equipo-e2e-responsive.log`. El QA anterior de Empresa/Folios ya está registrado
en sus dos páginas Notion y notas; ambas tareas permanecen cerradas. Este ajuste
posterior se acredita en PR #55 sin atribuirle el despliegue anterior.
