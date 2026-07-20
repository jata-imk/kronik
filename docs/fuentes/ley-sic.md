# Ley para Regular las Sociedades de Informacion Crediticia

## Fuente

- Nombre: Ley para Regular las Sociedades de Informacion Crediticia.
- Fuente legislativa consultada: Camara de Diputados / Gaceta Parlamentaria.
- URL inicial de decreto: https://gaceta.diputados.gob.mx/Gaceta/58/2001/dic/20011226.html
- URL reforma 2025 localizada: https://gaceta.diputados.gob.mx/Gaceta/66/2025/abr/20250409-II-3.html
- Fecha de consulta: 2026-07-17.

## Resumen

La ley regula constitucion y operacion de sociedades de informacion crediticia. Para Kronik es relevante por consultas SIC, consentimiento, manejo de reportes, trazabilidad y proteccion de informacion crediticia.

## Requisitos Funcionales Derivados

- Exigir consentimiento vigente antes de consultar historial o score.
- Guardar folio, proveedor, usuario, fecha, request auditable y respuesta cruda.
- Separar proveedores SIC, APIs y resultados normalizados.
- Auditar consultas y accesos a informacion sensible.
- Proteger credenciales y datos personales/crediticios.

## Decisiones de Producto

- Ninguna consulta SIC debe dispararse desde UI o job sin consentimiento y registro auditable.
- Los resultados normalizados no sustituyen la respuesta cruda del proveedor.

## Pendiente de Validacion

- Consultar version consolidada vigente en fuente oficial antes de implementar reglas legales especificas.
- Validar plazos, textos de consentimiento y conservacion de evidencia con asesor legal.
