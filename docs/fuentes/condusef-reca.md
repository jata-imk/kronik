# CONDUSEF RECA

## Fuente

- Nombre: Registro de Contratos de Adhesión.
- Institución: CONDUSEF.
- URL principal: https://registros.condusef.gob.mx/reca/
- URL informativa: https://registros.condusef.gob.mx/reca/reca.php
- Fecha de consulta: 2026-07-17.

## Resumen

RECA concentra modelos de contratos de adhesión de productos y servicios financieros usados por entidades financieras en México. Para Kronik es relevante porque los productos crediticios, contratos, carátulas y flujos de originación deben poder referenciar información contractual y condiciones visibles al usuario.

## Requisitos Funcionales Derivados

- Registrar contratos o plantillas asociadas a productos crediticios.
- Guardar número de registro RECA cuando aplique.
- Versionar contratos para no alterar condiciones ya utilizadas en solicitudes o créditos.
- Relacionar producto, contrato, carátula, comisiones y condiciones comerciales.
- Mostrar condiciones clave antes de aprobación/firma.

## Decisiones de Producto

- No implementar validaciones legales rígidas sin revisión especializada.
- Diseñar soporte para contratos versionados aunque la primera versión no automatice registro ante CONDUSEF.

## Pendiente de Validación

- Confirmar con asesor legal qué campos mínimos necesita capturar Kronik para productos de crédito simple y, en el futuro, crédito revolvente.
