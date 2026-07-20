# CONDUSEF RECA

## Fuente

- Nombre: Registro de Contratos de Adhesion.
- Institucion: CONDUSEF.
- URL principal: https://registros.condusef.gob.mx/reca/
- URL informativa: https://registros.condusef.gob.mx/reca/reca.php
- Fecha de consulta: 2026-07-17.

## Resumen

RECA concentra modelos de contratos de adhesion de productos y servicios financieros usados por entidades financieras en Mexico. Para Kronik es relevante porque productos crediticios, contratos, caratulas y flujos de originacion deben poder referenciar informacion contractual y condiciones visibles al usuario.

## Requisitos Funcionales Derivados

- Registrar contratos o plantillas asociadas a productos crediticios.
- Guardar numero de registro RECA cuando aplique.
- Versionar contratos para no alterar condiciones ya utilizadas en solicitudes o creditos.
- Relacionar producto, contrato, caratula, comisiones y condiciones comerciales.
- Mostrar condiciones clave antes de aprobacion/firma.

## Decisiones de Producto

- No implementar validaciones legales rigidas sin revision especializada.
- Disenar soporte para contratos versionados aunque la primera version no automatice registro ante CONDUSEF.

## Pendiente de Validacion

- Confirmar con asesor legal que campos minimos necesita capturar Kronik para productos de credito simple y, en el futuro, credito revolvente.
