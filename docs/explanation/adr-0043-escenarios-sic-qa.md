# ADR 0043: escenarios SIC por intento en QA

Estado: implementada, pendiente de validación de entrega. Fecha: 09/11/2026.

## Contexto

Los simuladores CDC requieren casos publicados. Recapturar cada identidad en el
expediente impide probar varios resultados con clientes autorizados. La tarea
[SIC QA](https://app.notion.com/p/41461db7db7f811d9c21ef4ae542d6d1) autoriza separar
el escenario de la identidad original exclusivamente en QA.

## Decisión

Ampliar [ADR 0022](adr-0022-sic-qa-evidencia.md): sustituir la petición únicamente
con modalidad explícita, conservar identidad, consentimiento y revisión originales,
y congelar escenario versionado, procedencia y petición cifrada por intento.
El positivo se preselecciona por servicio. La aceptación sintética para aprobación
requiere otra guarda y conserva uso QA en la evidencia aprobada.

Los casos CDC y simulaciones internas tienen procedencia distinta. Los internos
no envían red y congelan su respuesta. No existe sustitución automática tras errores.
Las operaciones /folio quedan excluidas del flujo y de la recuperación automática.

## Consecuencias

Los testers seleccionan casos independientemente. La idempotencia y el bloqueo de
intentos abiertos mantienen su contrato. Los escenarios no se presentan como
evolución personal ni habilitan producción. Los históricos conservan su significado.
Homun y el mapeo de ciudad real permanecen pendientes separados.

Contrato y fuentes: [escenarios SIC QA](../reference/sic-escenarios-qa.md).
