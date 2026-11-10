# SIC QA por escenarios

## Referencias

- [Tarea Notion](https://app.notion.com/p/41461db7db7f811d9c21ef4ae542d6d1).
- Rama: feat/sic-escenarios-qa, base origin/main fad6b29.
- [Contrato](../reference/sic-escenarios-qa.md), [ADR](../explanation/adr-0043-escenarios-sic-qa.md).

## Estado actual

09/11/2026: implementación local en worktree aislado; checkout principal preservado.
Catálogo de 84 cuerpos oficiales distintos y simulaciones internas explícitas.
Predeterminados positivos acordados; las tres colecciones aportadas por el usuario
se revisaron sin ejecutar sus scripts ni enviar consultas CDC.

## Evidencia

Regresión inicial en checkout principal c30c941: 38 pruebas SIC, 268 aserciones.
Validación inicial del cambio: suite completa 875 aprobadas, 1 omitida, 46235
aserciones; frontend afectado 6 aprobadas; build y Pint aprobados. Tras review:
60 pruebas SIC aprobadas, 365 aserciones. E2E positivo (tres servicios, gráficas,
móvil y error interno 404.1) aprobado con fixtures activados; E2E de cierre
aprobado por separado con fixtures desactivados. La ejecución conjunta inicial
falló por exigir configuraciones opuestas; no fue un defecto de la aplicación.
La revisión medium inicial detectó repetición idempotente tras activar escenarios
y atribución incorrecta de origen en auditoría histórica; corregidos con regresiones.
Segunda revisión medium sin hallazgos accionables (sesión
`01a1c3a7-37c5-7d52-acec-ca34aacd90c5`), con 77 pruebas relacionadas aprobadas por
el revisor. Su harness frontend encontró una restricción de archivos; la ejecución
principal autorizada sí pasó las 6 pruebas frontend.
Suite final: 878 aprobadas, 1 omitida, 46248 aserciones. Enlaces documentales:
154 revisados sin faltantes. [Captura móvil sintética](../assets/sic-escenarios-qa-mobile.png).
Barrido acumulado contra base funcional fad6b29: catálogo, flujo, aprobación,
históricos, migración y auditoría revisados en esta única entrega.
Migración aditiva nullable;
dos variables nuevas desactivadas por defecto. Sin seeders operativos.

## Siguiente paso

Publicar la PR y registrar su referencia. Integración, despliegue manual
y aceptación QA pendientes; no se acredita operación productiva.
