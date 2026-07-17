# Ley para Regular las Sociedades de Información Crediticia

## Fuente

- Nombre: Ley para Regular las Sociedades de Información Crediticia.
- Fuente legislativa consultada: Cámara de Diputados / Gaceta Parlamentaria.
- URL inicial de decreto: https://gaceta.diputados.gob.mx/Gaceta/58/2001/dic/20011226.html
- URL reforma 2025 localizada: https://gaceta.diputados.gob.mx/Gaceta/66/2025/abr/20250409-II-3.html
- Fecha de consulta: 2026-07-17.

## Resumen

La ley regula constitución y operación de sociedades de información crediticia. Para Kronik es relevante por consultas SIC, consentimiento, manejo de reportes, trazabilidad y protección de información crediticia.

## Requisitos Funcionales Derivados

- Exigir consentimiento vigente antes de consultar historial o score.
- Guardar folio, proveedor, usuario, fecha, request auditable y respuesta cruda.
- Separar proveedores SIC, APIs y resultados normalizados.
- Auditar consultas y accesos a información sensible.
- Proteger credenciales y datos personales/crediticios.

## Decisiones de Producto

- Ninguna consulta SIC debe dispararse desde UI o job sin consentimiento y registro auditable.
- Los resultados normalizados no sustituyen la respuesta cruda del proveedor.

## Pendiente de Validación

- Consultar versión consolidada vigente en fuente oficial antes de implementar reglas legales específicas.
- Validar plazos, textos de consentimiento y conservación de evidencia con asesor legal.
