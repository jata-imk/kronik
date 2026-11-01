# Revisión transversal del contexto de sucursal

## Referencias

- Notion: https://app.notion.com/p/40561db7db7f81188563fb756bd07b75
- Rama: `fix/contexto-sucursal-transversal`, base `ee940259de4b1552945f05577edfb8d9855e41cc`.
- PR: [#66](https://github.com/jata-imk/kronik/pull/66), commit de código
  `bd860d8d36f84e417141d0ec17df409ce6fdef28`; PR #65 de Bullet es ajena.
- [Contrato y matriz de los 19 módulos](../reference/contexto-sucursal-consultas.md).
- Decisiones: ADR 0005, 0031 y [0036](../explanation/adr-0036-alcance-consulta-sucursal.md).

## Estado — 2026-11-01

Implementación integrada en `main` mediante [PR #66](https://github.com/jata-imk/kronik/pull/66)
el 2026-11-01, merge `ae4f398553b37907fb0009900354650d3385f523`, autorizado
por el usuario. Se integró el head revisado `007fdcf` sin conflictos, con
[CI aprobado](https://github.com/jata-imk/kronik/actions/runs/41332496120).
Validación acumulada terminada. Sin despliegue ni QA del usuario acreditados;
Notion permanece Doing por ese seguimiento. No hay decisiones de negocio abiertas.

Se reprodujo la mezcla de sucursales en Solicitudes, Mi trabajo,
Evaluación/Cumplimiento e historial SIC; API dependía del contexto Spatie anterior;
la lectura SIC registraba sucursal del lector. La activa ahora acota consultas
operativas por defecto; lectura institucional sigue explícita y autorizada.
Se conserva la excepción de Clientes y el contrato de bitácora del ADR 0031.
Conciliar SIC usa sucursal original del intento, decisión confirmada por el usuario.

El usuario confirmó **todos los listados**, incluidos administración y catálogos.
Usuarios, Equipos/integrantes/invitaciones/roles, Roles, Sucursales, Productos,
Plantillas/versiones y menú ya filtran y paginan en servidor. Selecciones se
resuelven aparte de la página; agregaciones abarcan la consulta completa.
Bitácora y biblioteca ya lo hacían y no se duplicaron. Recursos globales siguen
globales; equipos determinan permisos, no propiedad de créditos.

Se añadieron selectores remotos, conservación de asignaciones históricas,
revalidación de pestañas/foco/historial y marcador siempre presente en Inertia.
Las operaciones y capacidades visuales exigen sucursal responsable también para
Super Admin en originación/cartera; Clientes conserva su excepción administrativa.
Una preferencia histórica inactiva no habilita nuevas capturas ni escritura normal.
Auditoría conserva sucursal del recurso y colas SIC/PDF su contexto persistido,
incluido null; cambiar activa se audita una vez, atómicamente.

Sin migraciones, seeders, variables o permisos nuevos. No se cambian condiciones
financieras, Bullet, SIC productivo ni habilitaciones para dinero real.

## Validación local

- Backend completo (hook pre-push del commit `bd860d8`): 624 aprobadas, una
  omitida, 42 417 aserciones, incluidas guarda de sucursal inactiva y capacidades
  de paquete.
- Frontend completo: 124 aprobadas en 47 archivos; incluye respuestas tardías,
  cancelación de selectores y restauración de filtros.
- Vite build y Pint: aprobados. Build conserva avisos de fuentes Liberation
  y tamaño de chunks ya existentes.
- Playwright aislado SQLite: 25 escenarios distintos aprobados por tandas.
  Última tanda ampliada: 24/25; el restante (Equipos) aprobó aislado, 1/1.
  También aprobaron 4/4 de Clientes/SIC y 4/4 de contexto/roles. Incluye
  administración, clientes/expediente, perfiles restringidos, solicitudes, SIC,
  productos/simulación, plantillas, folios, bitácora, estatus, equipos/integrantes,
  cambio de activa, pestañas, historial y formulario anterior; 1280/390 px.
- Enlaces locales de documentos cambiados verificados; sin destinos inexistentes.

Los resultados corresponden al commit de código `bd860d8`.
[CI MariaDB](https://github.com/jata-imk/kronik/actions/runs/41331654034) aprobado:
build, frontend, backend con 42 417 aserciones y concurrencia de folios (40
creaciones paralelas + dos cruces de bloqueos, 42 folios únicos consecutivos).
Pest en el runner reporta 592 avisos de `file_get_contents` y 33 casos sin avisos;
no se presenta ese desglose como el conteo local SQLite. CI finalizó sin fallos.
No acredita despliegue ni QA del usuario.

Logs sintéticos: `output/contexto-sucursal/` (excluidos localmente); diagnósticos
volátiles en `test-results/`. Capturas conservadas: [escritorio](../assets/contexto-sucursal/escritorio.png),
[móvil](../assets/contexto-sucursal/movil.png) y [pestañas](../assets/contexto-sucursal/pestanas.png).

## Revisión acumulada

Revisión medium local del diff completo contra `ee94025`, sin delegación externa.
Hallazgos corregidos: metadatos/totales ausentes en recargas parciales, selección
de rol eliminado, filtros restaurados que no actualizaban los controles, selección
de roles por objeto sin clave estable, aviso entre pestañas perdido al desmontar
el layout y ausencia de revalidación al recuperar visibilidad. Se revisaron
permisos Spatie/restauración, alcance institucional, acceso directo y auditoría.
Los casos de versiones históricas y propietario duplicado en `team_user` tienen
regresión. `/review medium` local: base `ee94025`, commit revisado `bd860d8`,
resultado sin hallazgos abiertos. Barrido acumulado de la única PR #66 contra
esa misma base: contratos, permisos, auditoría e históricos revisados; sin
cambios ajenos. No equivale a una aprobación de otro revisor. CI aprobado.

Límite local de navegador: tandas largas en Windows produjeron
`ERR_NO_BUFFER_SPACE` al cargar chunks de Clientes y, posteriormente, Equipos;
capturas/vídeos mostraron página vacía y trazas/consola confirmaron importación
dinámica fallida. Ambos aprobaron aislados; Clientes además aprobó en la segunda
tanda ampliada. No se afirma una tanda de 25 sin incidencias. Los 25 escenarios
están verificados por tandas. La prueba SIC usaba textos de una UI anterior ya
presentes en main; se actualizó al contrato QA actual y pasó en ambas tandas.
Los defectos reales de pestañas y rol seleccionado se corrigieron y sus
recorridos aprobaron nuevamente en la tanda ampliada.

## Siguiente paso

Desplegar `main` con el procedimiento habitual y validar en el entorno de QA:
cambio entre dos sucursales, consulta institucional y operaciones de la sucursal
responsable. La integración ya está confirmada; no quedan incrementos técnicos
del plan sin implementar ni decisiones de negocio pendientes. Notion conserva
Doing por despliegue y QA del usuario pendientes. La cobertura general del backlog de auditoría
permanece abierta; esta entrega mantiene sus emisores pertinentes sin atribuirse
el cierre de aquel backlog. PR #65, Bullet y políticas financieras pendientes
continúan separados.
