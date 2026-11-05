# Consulta, edición e históricos

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81138be2df5cfa15564c
- Base funcional: 33c79c421451effac723da9d268da0630db3fb0f; PR #66 integrada.
- Rama actual: feat/consulta-productos. PR #65 de Bullet ajena.
- [Contrato y matriz de 19 módulos](../reference/consulta-edicion-historicos.md).

## Estado — 2026-11-02

Implementación autorizada del plan ampliado. Cinco incrementos: productos,
plantillas, originación, Usuarios/Sucursales, Empresa/Menú. PR encadenadas para
revisión; no se integra main sin una entrega concreta revisada. Sin despliegue.
Incremento 1 en implementación; los demás pendientes. Sin migraciones ni permisos nuevos.

## Evidencia inicial

Investigación sobre main 33c79c4 y BD local (SELECT readonly, 19 módulos).
11 pruebas existentes/137 aserciones pasaron durante planeación; no corresponden
a una validación de estos cambios. Reproducción en memoria: política en retirada
permitida; instancia antigua de borrador puede modificar plantilla activa;
HTTP habitual protegido 422. Empresa GET crea singleton ausente; lectores de
administración GET 200 y PUT 403. Sin modificar BD operativa ni habilitar dinero.

## Siguiente paso

Validar incremento 1, revisión medium y PR. Continuar incrementos 2–5 y barrido
acumulado contra base funcional; registrar pruebas, CI, integración y QA reales.

## Incremento 1

Consulta completa de productos implementada. Backend focalizado: 59 pruebas/511
aserciones; frontend: tres pruebas. Pint y enlaces locales aprobados. Build aprobado,
con avisos preexistentes de fuentes/chunks. E2E y revisión final siguen en curso.
Revisión medium identificó alta de periodicidad visible en consulta y una referencia
de simulación incorrecta; ambas corregidas. Regresión de validación conservando
captura cubierta por componente. Continuar revisión tras las correcciones.
