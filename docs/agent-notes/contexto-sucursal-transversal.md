# Revisión transversal del contexto de sucursal

## Referencias

- Notion: https://app.notion.com/p/40561db7db7f81188563fb756bd07b75
- Rama: `fix/contexto-sucursal-transversal`, base `ee940259de4b1552945f05577edfb8d9855e41cc`.
- PR de esta entrega: en preparación; PR #65 de Bullet es ajena.
- [Contrato y matriz de los 19 módulos](../reference/contexto-sucursal-consultas.md).
- Decisiones: ADR 0005, 0031 y [0036](../explanation/adr-0036-alcance-consulta-sucursal.md).

## Estado — 2026-11-01

Implementación local completa; validación acumulada y publicación en curso.
Sin integración ni despliegue. La tarea de Notion permanece Doing hasta completar
la entrega y su seguimiento de integración/QA. No hay decisiones de negocio abiertas.

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

- Backend completo: 623 aprobadas, una omitida, 42 399 aserciones. Después se
  verificaron 28 pruebas de contexto/perfiles/expediente/sucursales, con 349
  aserciones, incluyendo guarda de sucursal inactiva y capacidades de paquete.
- Frontend completo: 124 aprobadas en 47 archivos; incluye respuestas tardías,
  cancelación de selectores y restauración de filtros.
- Vite build y Pint: aprobados. Build conserva avisos de fuentes Liberation
  y tamaño de chunks ya existentes.
- Playwright aislado SQLite: cuatro recorridos de contexto/roles aprobados,
  escritorio 1280 px y móvil 390 px; incluye pestañas, historial y salida de
  formulario anterior. Regresión ampliada de módulos todavía ejecutándose.
- Enlaces locales de documentos cambiados verificados; sin destinos inexistentes.

Los resultados corresponden al árbol de implementación; el commit de código y
CI se registrarán al publicar. No acreditan despliegue ni QA del usuario.
Logs sintéticos: `output/contexto-sucursal/` (excluidos localmente); capturas,
trazas y vídeos de Playwright: `test-results/`.

## Revisión acumulada

Revisión medium local del diff completo contra `ee94025`, sin delegación externa.
Hallazgos corregidos: metadatos/totales ausentes en recargas parciales, selección
de rol eliminado, filtros restaurados que no actualizaban los controles, selección
de roles por objeto sin clave estable, aviso entre pestañas perdido al desmontar
el layout y ausencia de revalidación al recuperar visibilidad. Se revisaron
permisos Spatie/restauración, alcance institucional, acceso directo y auditoría.
Los casos de versiones históricas y propietario duplicado en `team_user` tienen
regresión. Falta registrar el commit revisado y resultado final de CI/barrido.

Un recorrido de Clientes de la tanda ampliada falló por `ERR_NO_BUFFER_SPACE`
al cargar un chunk, seguido de error de importación dinámica. Captura, vídeo,
traza y consola revisados. Debe repetirse aislado; no se clasifica como defecto
del dominio sin reproducirlo. Los defectos de pestañas y rol seleccionado se
corrigieron y sus recorridos ya pasan.

## Siguiente paso

Terminar la regresión de navegador, publicar PR independiente, comprobar CI MariaDB
y registrar evidencia final aquí y en Notion. Mantener integración/despliegue/QA
separados del estado implementado; la cobertura general del backlog de auditoría
permanece abierta.
