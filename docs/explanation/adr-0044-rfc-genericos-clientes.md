---
type: explanation
area: fiscal
status: accepted
---

# ADR 0044: RFC genéricos en la captura de Clientes

## Contexto

La reunión del 24/10/2026 admitió dos RFC genéricos repetibles. La regla compartida
con Empresa y la UI los rechazaban universalmente (ADR 0004). El usuario confirmó
el 10/11/2026 permitirlos para física y moral en alta/edición web/API, mantener los
flujos actuales y separar la unicidad de RFC propios.

## Decisión

Sustituir parcialmente [ADR 0004](adr-0004-validacion-rfc-local.md): una única regla
mantiene validación local estricta y una excepción explícita, desactivada por
defecto y habilitada solo por Clientes. La lista canónica contiene
`XAXX010101000` y `XEXX010101000`; exige tipo válido y no deduce nacionalidad,
régimen, CURP ni acreditación fiscal a partir del genérico.

El aviso se deriva del valor actual, sin columna ni estado persistido. Empresa
conserva su RFC propio. No se agregan requisitos de aprobación ni cambian
payloads SIC. SIC productivo y facturación requieren su propia entrega con
contrato validado; los dobles QA no certifican aceptación del proveedor.

## Consecuencias y alternativas

- Se conserva normalización, validación de RFC propios y edición parcial.
- La corrección posterior cambia huellas vigentes y exige reevaluar según los
  mecanismos existentes, sin modificar snapshots ni documentos anteriores.
- Se reutilizan eventos de alta/edición, sin valores fiscales en auditoría.
- La unicidad de RFC propios no existe hoy y queda en tarea separada con análisis
  de duplicados y alcance; no se introduce un índice único en esta entrega.
- Se descarta habilitar genéricos globalmente o duplicar el validador por módulo.

Contrato y operación: [validador](../reference/validacion-rfc.md),
[guía](../how-to/validar-rfc.md) y [entrega](../agent-notes/clientes-rfc-genericos.md).
