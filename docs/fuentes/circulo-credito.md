# Circulo de Credito

## Fuente

- Nombre: Documentacion tecnica y contratos de Circulo de Credito.
- Institucion: Circulo de Credito.
- URL publica general: https://www.circulodecredito.com.mx/
- Fecha de consulta: 2026-10-10.
- Portal de APIs: https://developer.circulodecredito.com.mx/apis
- FICO v2, caso sandbox oficial: https://github.com/APIHub-CdC/ficoscore-v2-simulacion-client-java/blob/master/src/test/java/com/cdc/apihub/mx/FS/simulacion/test/ApiTest.java
- Fintech, caso sandbox oficial: https://github.com/APIHub-CdC/fintechscore-simulacion-client-php/blob/master/test/Api/FintechScoreSimulacionApiTest.php
- RC + FICO, caso sandbox oficial: https://github.com/APIHub-CdC/rc-ficoscore-simulacion-client-php/blob/master/test/Api/ReporteDeCreditoConFicoScoreApiTest.php
- Firma oficial: https://github.com/APIHub-CdC/signature-manager-php/blob/master/lib/Interceptor/KeyHandler.php
- Modelo de cuenta: https://github.com/APIHub-CdC/rc-ficoscore-simulacion-client-php/blob/master/lib/Model/Credito.php

## Resumen

Kronik ya contiene servicios, modelos y repositories para integraciones de Circulo de Credito. La documentacion contractual y tecnica especifica suele depender de credenciales, contratos o material privado del proveedor, por lo que este documento solo registra la trazabilidad interna.

## Requisitos Funcionales Derivados

- Separar ambiente sandbox y produccion por configuracion de financiera.
- Proteger credenciales.
- Construir requests desde datos reales del cliente, datos fiscales y domicilio.
- Guardar request minimo auditable y response cruda.
- Normalizar score, razones, cuentas, consultas, mora y alertas.
- Agregar pruebas con respuestas fake, sin llamadas externas.

## Decisiones de Producto

- Los tests de SIC deben usar fixtures/respuestas fake.
- Ningun test automatizado debe depender de llamadas reales a Circulo de Credito.

## Pendiente de Validacion

- Incorporar resumen interno de documentacion privada cuando el contrato/proveedor lo permita.
- QA verificado: las tres rutas sandbox respondieron HTTP 200 con casos sintéticos
  oficiales el 10/10/2026; no se insertó evidencia acreditable de aprobación.
- Producción sigue pendiente: contratos, habilitación, credenciales, firma/verificación
  del transporte y políticas validadas. El host coincide entre ambientes; no basta
  comprobarlo para identificar sandbox. Las rutas y flags deben comprobarse también.
