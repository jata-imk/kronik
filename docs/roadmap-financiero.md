# Roadmap financiero de Kronik

## Objetivo

Plataforma configurable para prestamistas personales y pequeñas instituciones financieras mexicanas, incluidas SOFIPOs mediante perfiles institucionales. Crédito simple individual PF/MXN primero; otros motores quedan como extensiones. Despliegue de una instancia por financiera; equipos son contextos de permisos.

## Estado Actual del Sistema

Ya existe una base funcional para usuarios, equipos, permisos, sucursales, clientes, datos fiscales, domicilios y consultas de historial crediticio. Los `teams` representan departamentos o contextos de permisos y las sucursales representan unidades operativas separadas. Un usuario puede operar en varias sucursales con una principal y una actual; cada cliente conserva una sucursal responsable. Los clientes ya tienen datos personales, fiscales, direcciones y relación con consultas SIC. Círculo de Crédito tiene un adaptador QA para tres APIs, lectura histórica y evidencia cifrada; los SDK heredados siguen cerrados.

Actualización del 10/10/2026: P0–P6 integrados, con solicitudes, expediente y consentimiento SIC, evaluación manual/PLD, aprobación, contrato y tabla congelados, revisión de firma digitalizada, desembolso y pagos QA con devengo/reversos. Productos, fiscalidad y documentos están versionados. Los SDK SIC anteriores permanecen cerrados; P7 recupera consultas por un adaptador seguro y el historial.

P7 recupera FICO v2, Fintech y Reporte de Crédito + FICO en simulación y su lectura
histórica; P8 incorpora anticipos, liquidación y cierre. Implementados y verificados
localmente en la rama de entrega, pendientes de revisión/integración. 2FA y revisión
manual aprobadas. Cumplimiento/cobranza ampliados, reportes, contabilidad y crédito
revolvente siguen pendientes; Backlog 02.5 diferido.
[Notion](https://app.notion.com/p/3e161db7db7f817a8c89ec30767d4666) define prioridades.
Las secciones siguientes describen el objetivo funcional sin acreditar cumplimiento universal.

## Procesos Requeridos

### Configuración de Empresa

Cada instancia debe configurar identidad legal, datos fiscales, domicilio fiscal, usuarios, roles, permisos, moneda, zona horaria, logotipos, formatos, políticas generales, cuentas bancarias, plantillas contractuales y llaves de integraciones. Las sucursales guardan domicilio operativo, horarios y folios propios.

### Productos Crediticios

Los productos deben ser parametrizables por financiera. Para crédito simple: monto mínimo/máximo, plazo, periodicidad, tasa ordinaria, tasa moratoria, comisiones, días de gracia, método de amortización, reglas de prepago, desembolso, garantías y avales. Para crédito revolvente: línea autorizada, límites, disposiciones, cortes, pagos mínimos, intereses por saldo, estados de cuenta y cargos.

### Solicitantes y Expediente

El alta de cliente debe evolucionar hacia expediente de solicitante: documentos, consentimientos, ingresos, egresos, actividad económica, ocupación, referencias, avales, beneficiario/control cuando aplique, historial de cambios y validación documental. Debe registrar qué documentos faltan antes de permitir originación.

### Originación

El flujo objetivo es: prospecto/cliente, solicitud, captura de producto, prevalidación, consentimiento SIC, consulta de score, análisis de capacidad de pago, reglas de decisión, dictamen, comité o autorización, contrato, firma, desembolso y activación del crédito.

### PLD

PLD debe cubrir identificación, conocimiento del cliente, perfil transaccional, nivel de riesgo, listas, alertas, revisión periódica, bitácora, documentación de soporte y reportes internos. Las reglas deben ser configurables para adaptarse a cada financiera y documentar evidencia de revisión.

### Operación y Cobranza

Una vez autorizado el crédito, el sistema debe generar tabla de amortización, registrar desembolso, pagos, aplicación de pagos, saldo, intereses, mora, comisiones, cobranza preventiva, promesas de pago, convenios, reestructuras y cartera vencida.

### Reportes

Los reportes mínimos son colocación, cartera vigente/vencida, morosidad, pagos recibidos, próximos vencimientos, productividad por sucursal/asesor, consultas SIC, alertas PLD, actividad de usuarios y conciliación básica.

## Fuentes y Criterios Externos

- CONDUSEF RECA: contratos de adhesión, términos, tasas, comisiones y registro aplicable a SOFOM ENR y fintech.
- CONDUSEF RECO: registro y consulta de comisiones vigentes.
- Banxico CAT: cálculo informativo del costo anual total con intereses, comisiones y gastos inherentes.
- CNBV PLD/FT para SOFOM ENR: obligaciones de cumplimiento, dictamen técnico y supervisión en materia de PLD/FT.
- SAT Actividades Vulnerables: referencia si el modelo cambia a prestamista no financiero fuera del sistema financiero.

## Roadmap por Fases

1. Documentar alcance, fuentes y TODOs.
2. Implementar configuración global de empresa y sucursales.
3. Completar expediente KYC y documentación del solicitante.
4. Diseñar productos de crédito simple y simulador.
5. Crear solicitudes, evaluación y dictamen.
6. Formalizar integración SIC/CDC con consentimiento y datos reales.
7. Implementar motor de amortización, pagos y cobranza.
8. Implementar PLD configurable y alertas.
9. Agregar crédito revolvente.
10. Consolidar reportes, exportables y contabilidad.

## Fuera de Alcance Inicial

- Microcrédito grupal.
- Captación de ahorro o inversión.
- Financiamiento colectivo como ITF autorizada.
- App móvil nativa.
- Blockchain o contratos inteligentes.
