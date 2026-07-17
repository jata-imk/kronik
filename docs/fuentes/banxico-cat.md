# Banxico CAT

## Fuente

- Nombre: Costo Anual Total.
- Institución: Banco de México.
- Calculadora CAT créditos diversos: https://www.banxico.org.mx/CAT/
- Información de CAT y Circular 21/2009: https://anterior.banxico.org.mx/dyn/ley-de-transparencia/consultas-frecuentes/costo-anual-total.html
- Fecha de consulta: 2026-07-17.

## Resumen

El CAT es un indicador estandarizado del costo de financiamiento expresado en términos porcentuales anuales. Incorpora costos y gastos inherentes al crédito para facilitar comparación entre ofertas. Banxico refiere la metodología, fórmula, componentes y supuestos de la Circular 21/2009.

## Requisitos Funcionales Derivados

- Guardar parámetros necesarios para calcular CAT informativo cuando aplique.
- Registrar tasa, comisiones, periodicidad, monto, plazo y cargos adicionales.
- Mostrar CAT en productos, simulaciones, carátulas o documentos donde aplique.
- Versionar los parámetros usados para calcular CAT.
- Agregar pruebas para simulador/CAT antes de marcar productos como terminado.

## Decisiones de Producto

- El primer simulador de crédito simple debe diseñarse con datos suficientes para cálculo futuro de CAT.
- Si se implementa CAT, debe validarse contra metodología oficial y casos de prueba conocidos.

## Pendiente de Validación

- Confirmar alcance obligatorio para la entidad objetivo y producto específico.
- Validar fórmula/casos con asesor especializado antes de usarlo como valor contractual.
