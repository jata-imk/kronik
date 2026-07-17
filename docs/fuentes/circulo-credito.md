# Círculo de Crédito

## Fuente

- Nombre: Documentación técnica y contratos de Círculo de Crédito.
- Institución: Círculo de Crédito.
- URL pública general: https://www.circulodecredito.com.mx/
- Fecha de consulta: 2026-07-17.

## Resumen

Kronik ya contiene servicios, modelos y repositories para integraciones de Círculo de Crédito. La documentación contractual y técnica específica suele depender de credenciales, contratos o material privado del proveedor, por lo que este documento solo registra la trazabilidad interna.

## Requisitos Funcionales Derivados

- Separar ambiente sandbox y producción por configuración de financiera.
- Proteger credenciales.
- Construir requests desde datos reales del cliente, datos fiscales y domicilio.
- Guardar request mínimo auditable y response cruda.
- Normalizar score, razones, cuentas, consultas, mora y alertas.
- Agregar pruebas con respuestas fake, sin llamadas externas.

## Decisiones de Producto

- Los tests de SIC deben usar fixtures/respuestas fake.
- Ningún test automatizado debe depender de llamadas reales a Círculo de Crédito.

## Pendiente de Validación

- Incorporar resumen interno de documentación privada cuando el contrato/proveedor lo permita.
- Confirmar campos obligatorios por API usada antes de cerrar el módulo SIC.
