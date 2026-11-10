# SIC QA por escenarios

## Referencias

- [Tarea Notion](https://app.notion.com/p/41461db7db7f811d9c21ef4ae542d6d1).
- Rama: feat/sic-escenarios-qa, base origin/main fad6b29.
- PR: [#75](https://github.com/jata-imk/kronik/pull/75), implementación
  `374689b339714bb6e82b5accf07a138ba379faa5`.
- [Contrato](../reference/sic-escenarios-qa.md), [ADR](../explanation/adr-0043-escenarios-sic-qa.md).

## Estado actual

09/11/2026: integrada en main mediante PR #75, merge
`67c2f8462fcfaa51df39f097e874ce68a4dd5d91`, con CI aprobada en el intento 2.
Checkout principal sincronizado; cambios documentales locales restaurados.
El usuario confirma funcionamiento en su instalación QA el 09/11/2026:
la captura de consulta #17 muestra Reporte + FICO mediante escenario CDC sandbox
registrado como exitoso. Despliegue confirmado por ese uso; sin inferir validación
de todos los escenarios o servicios.
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

CI: [ejecución 42755120709, intento 2](https://github.com/jata-imk/kronik/actions/runs/42755120709/attempts/2)
aprobada, incluidas concurrencia MariaDB, renderizador PDF y E2E Bullet/originación.
El primer intento excedió los 10 segundos del chequeo PDF; la repetición pasó
sin cambios de código. Integración completada. Despliegue y funcionamiento del
escenario de Reporte + FICO confirmados por el usuario; resta aceptación QA
integral. No se acredita operación productiva. El hook de
publicación repitió 878 pruebas aprobadas, 1 omitida y build sobre 374689b.

## Ajuste de presentación posterior al QA

Rama `fix/sic-presentacion-actividad`, base main `67c2f84`.
El enlace de cada fila muestra «Ver» sin saltos, con nombre accesible que conserva
el número de consulta. Actividad muestra el nombre del usuario; los pasos
automáticos dicen «Sistema · solicitado por …», sin confundir iniciador y ejecutor.
Los eventos persistidos conservan sus IDs y estados. Solo se expone el nombre,
sin datos de cuenta. Sin nuevas acciones auditables: se mantiene la lectura
auditada existente y se actualiza su presentación.

Validación: 61 pruebas SIC aprobadas (389 aserciones), prueba del componente
aprobada, build y Pint aprobados. E2E SIC aprobado con datos/BD aislados:
enlace «Ver» sin saltos, nombres de actividad, tres servicios y reporte móvil.
Review medium de 33632f6 contra main 67c2f84 sin hallazgos accionables;
sesión `01a1c3c4-0a6c-7131-80ee-6207818c13d9`. Barrido conjunto con PR #75
sobre la base funcional fad6b29: se conserva el contrato de consulta y auditoría.
[Listado compacto](../assets/sic-listado-compacto.png) y
[actividad móvil](../assets/sic-actividad-nombres-mobile.png) con datos E2E sintéticos.
