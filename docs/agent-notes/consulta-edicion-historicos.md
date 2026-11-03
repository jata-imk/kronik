# Consulta, edición e históricos

## Referencias

- Notion: https://app.notion.com/p/3fe61db7db7f81138be2df5cfa15564c
- Base funcional: 33c79c421451effac723da9d268da0630db3fb0f; PR #66 integrada.
- Rama actual: feat/consulta-plantillas. PR #65 de Bullet ajena.
- [Contrato y matriz de 19 módulos](../reference/consulta-edicion-historicos.md).

## Estado — 2026-11-02

Implementación autorizada del plan ampliado. Cinco incrementos: productos,
plantillas, originación, Usuarios/Sucursales, Empresa/Menú. PR encadenadas para
revisión; no se integra main sin una entrega concreta revisada. Sin despliegue.
Incremento 1 implementado y validado; incremento 2 implementado en validación. Sin migraciones ni permisos nuevos.

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

## Incremento 2

Rama feat/consulta-plantillas, dependiente de bc6e533. Editor documental en consulta
y revalidación de estado dentro de bloqueos transaccionales. Incremento 1:
E2E productos 4/4 aprobado, incluidos consulta móvil y simulación fiscal.
Validación de plantillas en curso.

Plantillas: 22 backend/193 aserciones y prueba real de Quill readonly aprobadas.
Build aprobado. Revisión medium sin hallazgos; su intento de Vitest quedó limitado
por sandbox, pero la ejecución principal sí pasó. E2E detectó espera de guardado
ausente en el caso nuevo y overflow móvil; corregidos, repetición pendiente.

## Incremento 3

Rama feat/originacion-guiada, base bab3553. Política actual con número propio,
historia exacta y formulario compartido de consulta/nueva revisión. Lectores pueden
consultar; retirada impide crear incluso con instancia antigua y Super Admin.
Avisos en producto, captura y requisitos sin nuevos bloqueos de captura/envío.
Frontend: 41 pruebas aprobadas y build aprobado. Backend/E2E/review en validación.
Plantillas: E2E nuevo y editor existente aprobados (2/2); corregidos espera de
creación y desbordamiento móvil. Sin integración ni despliegue.

Incremento 3 validado: 73 backend/1148 aserciones, 41 frontend y E2E de historia
aprobados. Revisión medium contra bab3553 sin hallazgos accionables. El E2E
usa pulsaciones reales para el campo monetario PrimeVue. Sin cambios financieros.
