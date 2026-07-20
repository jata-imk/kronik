# Banxico CAT

## Fuente

- Nombre: Costo Anual Total.
- Institucion: Banco de Mexico.
- Calculadora CAT creditos diversos: https://www.banxico.org.mx/CAT/
- Informacion de CAT y Circular 21/2009: https://anterior.banxico.org.mx/dyn/ley-de-transparencia/consultas-frecuentes/costo-anual-total.html
- Fecha de consulta: 2026-07-17.

## Resumen

El CAT es un indicador estandarizado del costo de financiamiento expresado en terminos porcentuales anuales. Incorpora costos y gastos inherentes al credito para facilitar comparacion entre ofertas. Banxico refiere metodologia, formula, componentes y supuestos de la Circular 21/2009.

## Requisitos Funcionales Derivados

- Guardar parametros necesarios para calcular CAT informativo cuando aplique.
- Registrar tasa, comisiones, periodicidad, monto, plazo y cargos adicionales.
- Mostrar CAT en productos, simulaciones, caratulas o documentos donde aplique.
- Versionar los parametros usados para calcular CAT.
- Agregar pruebas para simulador/CAT antes de marcar productos como terminado.

## Decisiones de Producto

- El primer simulador de credito simple debe disenarse con datos suficientes para calculo futuro de CAT.
- Si se implementa CAT, debe validarse contra metodologia oficial y casos de prueba conocidos.

## Pendiente de Validacion

- Confirmar alcance obligatorio para la entidad objetivo y producto especifico.
- Validar formula/casos con asesor especializado antes de usarlo como valor contractual.
