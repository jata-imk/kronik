# ADR 0050: LabsMobile como proveedor del canal SMS

Estado: aceptado el 2026-11-24 por decisión de José. Transporte/configuración I1
implementados en su rama el 2026-11-25; integración, despliegue, contratación y
activación no acreditados. Seguimiento LabsMobile pendiente de I2.
El [contrato de canales](../reference/canales-comunicacion.md) describe el código vigente.

## Contexto

La [comparación de proveedores](evaluacion-proveedores-sms-mexico.md) motivó la
elección explícita de LabsMobile por costo. La rama `feat/comunicaciones-notificaciones`
en `ecb9d4a` conserva un adaptador Infobip, elegido por
[ADR 0049](adr-0049-orquestacion-comunicaciones-externas.md). Se requiere cambiar
esa elección sin presentar el adaptador actual como una integración LabsMobile.

## Decisión

Seleccionar LabsMobile para la siguiente integración SMS, sustituyendo únicamente
la elección de Infobip de ADR 0049. Conservar evento, reglas, consentimiento,
salida, cola, contexto original y auditoría existentes; un proveedor por canal.
La [investigación técnica](integracion-sms-labsmobile.md) registra capacidades
documentadas y condiciones pendientes de cuenta/ruta/piloto.

No equiparar admisión del operador con entrega al dispositivo ni atribuir lectura
al SMS. La aceptación del transporte no acredita recepción. Antes de aceptar
estados externos, resolver autenticidad y correlación; la documentación examinada
no establece un esquema de firma para el callback SMS. Mantener inciertos los
resultados sin evidencia suficiente y evitar reenvíos ciegos.

La selección de proveedor no habilita tráfico ni autoriza compras, números,
credenciales o acceso a VPS. Confirmar elegibilidad mexicana, remitente,
condiciones y piloto. No se añaden campañas, cobranza vencida ni conversaciones.

## Consecuencias

I1 adapta transporte, configuración/UI y orquestación, y retira recepción y
conciliación Infobip, con pruebas y auditoría. El contrato vigente describe
LabsMobile; `ecb9d4a` conserva el antecedente funcional anterior. La divergencia
frente a `main` se revisó sin fusionar, conservando la elegibilidad financiera.
El seguimiento LabsMobile se implementará en I2 tras confirmar autenticidad.

La revisión pública deja pendientes cobertura concreta de OMV de Altán, vigencia
de entrega por ruta, autenticación/conciliación de callbacks y tarifa de cuenta.
Estas preguntas delimitan la prueba de la integración, sin reabrir la elección
de proveedor ya aceptada. No hay migraciones ni cambios funcionales en este ADR.

El [plan de sustitución](integracion-sms-labsmobile.md#sustitución-del-código-infobip)
debe incluir las referencias activas, configuración y jobs del proveedor anterior
antes de retirar código. José confirmó el 25/11/2026 que solo existen desarrollo
y QA, sin producción, clientes ni históricos que conservar; los datos de prueba
son desechables. Se sustituye Infobip directamente, con limpieza explícita cuando
corresponda, sin migración histórica ni convivencia de proveedores. La reutilización del
[editor documental](editor-plantillas-notificaciones.md) corresponde a I3;
no forma parte de la sustitución de transporte de I1.
