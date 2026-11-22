# ADR 0048 · Calendario institucional y evidencia contractual

## Estado

Aceptado el 18/11/2026.

## Contexto

Los productos comparten una financiera por instalación. Las versiones de
producto congelan reglas y la revisión de solicitud congela el escenario.
El Anexo de cálculo histórico usa fechas civiles sin ajuste.

## Decisión

Versionar el calendario de la financiera separadamente de la regla del
producto. Resolver la última publicación al enviar la solicitud y fijar
en la revisión su identidad, huella y trayectoria de fechas. La tabla fiscal
reutiliza esa trayectoria con las condiciones aprobadas. El cronograma firmado
sigue siendo la evidencia de obligaciones para el core.

La nueva política desplaza vencimiento y cierre de periodo al siguiente día
hábil sobre cobertura revisada. El calendario mensual conserva su ancla.
Publicar tiene alcance institucional y exige Super Admin global.

## Consecuencias

Es posible publicar correcciones al calendario sin alterar contratos
congelados. La financiera mantiene la cobertura revisada para los plazos que
ofrece. Periodicidad diaria puede compartir clasificación de días sin habilitar
frecuencias adicionales.

[Contrato del calendario](../reference/calendario-crediticio.md)
