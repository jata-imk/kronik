# Evaluación de proveedores SMS para México

Consulta: 2026-11-24. La comparación inicial se conserva como antecedente;
José eligió después LabsMobile por costo. La decisión y la investigación vigente
están en [ADR 0050](adr-0050-proveedor-sms-labsmobile.md) y en la
[investigación de LabsMobile](integracion-sms-labsmobile.md). Alcance de
[Comunicaciones en Notion](https://app.notion.com/p/3fe61db7db7f81e4af50c4f0f3ab7e41):
avisos operativos y preventivos al titular; pago recibido y próximo vencimiento.
No amplía a campañas, cobranza vencida, OTP ni conversaciones.

## Comparación

Las cifras son referencias públicas en su moneda original. No son cotizaciones
de cuenta ni prueban elegibilidad de un prestamista. El tamaño y la codificación
del texto pueden consumir varios segmentos por aviso.

| Opción | Referencia económica consultada | Integración en esta rama | Qué falta comprobar |
| --- | --- | --- | --- |
| Infobip | USD 0.17043 por mensaje para México, promedio publicado entre redes; precio final según cuenta, contrato y volumen. | Ya existe `InfobipSmsTransport`, comprobación de conexión y recepción de estados. Es la opción con más código reutilizable. | Aprobación del caso financiero mexicano, remitente/ruta, precio real, estados disponibles y perfil de seguridad del webhook. |
| LabsMobile | La página mexicana anuncia desde MXN 0.224; es un piso sujeto a volumen/promoción. Su lista fechada 24/11/2026 muestra EUR 0.0126 en el primer tramo mexicano y EUR 0.0112 en el siguiente. No equivalen a una cotización para un piloto pequeño. | API REST JSON con token mediante Basic Auth, modo simulado sin envío ni consumo y callbacks. Requiere adaptación nueva. | Precio en MXN y compra mínima para el volumen real, autorización mexicana, remitente y autenticidad del callback. |
| SMS Masivos | MXN 0.90 por SMS desde 500; MXN 0.77 desde 1,000; MXN 0.68 desde 10,000, más IVA. Mínimo publicado de 500. | API con `apikey`, sandbox y confirmaciones por webhook. Requiere adaptación nueva. | Resolver discrepancias sobre cobro de fallidos, mecanismo de autenticidad del webhook y requisitos de cuenta para el piloto. |
| Twilio | No se utiliza el precio para decidir esta comparación. | No hay adaptador en la rama. | Se descarta para este caso: sus directrices mexicanas prohíben contenido relacionado con dinero/préstamos. No intentar eludirlo mediante otra redacción. |

Fuentes económicas: [Infobip](https://www.infobip.com/sms/pricing),
[LabsMobile México](https://www.labsmobile.com/es/plataforma-envio-sms-masivo/mexico),
[lista oficial LabsMobile](https://www.labsmobile.com/es/prices/pricelist) y
[SMS Masivos](https://www.smsmasivos.com.mx/precios-sms-masivos).
Restricción: [directrices Twilio México](https://www.twilio.com/en-us/guidelines/mx/sms).

## Condiciones que cambian la elección

Infobip publica una [oferta para servicios financieros](https://www.infobip.com/sms/financial-services)
y [seguimiento por webhook o consulta](https://www.infobip.com/docs/sms/message-delivery).
Es evidencia de capacidades generales; no confirma que la cuenta o ruta mexicana
apruebe a cada prestamista. Se debe contrastar con la API real antes de dar por
válidos el payload, los estados o el perfil HMAC implementados en la rama.

LabsMobile incluye pagos recibidos y recordatorios en sus
[casos del sector financiero](https://www.labsmobile.com/es/soluciones-sms/financiero-y-fintech).
Su [API vigente](https://www.labsmobile.com/es/api-sms/versiones-api/http-rest-post-json)
distingue acuse del operador y confirmación al dispositivo; la recepción de
estados documentada usa GET. No se encontró en esa página un esquema de firma
HMAC para esos callbacks. Esto es una incertidumbre de la revisión, no prueba de
que el proveedor carezca de una opción autenticada. El remitente personalizable
depende de país/operador. Confirmar ambos puntos antes de aceptar estados externos.
El precio mínimo anunciado no determina el costo del volumen de Kronik.

SMS Masivos publica [casos financieros mexicanos](https://www.smsmasivos.com.mx/blog/sms-servicios-financieros),
pero hay discrepancias materiales entre sus páginas:

- La [página de API](https://www.smsmasivos.com.mx/api-sms) anuncia cobro por
  entrega. Los [términos C.3/E.3](https://www.smsmasivos.com.mx/terminos-y-condiciones)
  indican consumo también por fallidos y aclaran que el DLR no garantiza
  recepción en el dispositivo. Para presupuestar, considerar cobro por intento
  hasta obtener una oferta que resuelva la diferencia.
- Esos términos limitan la cuenta a actividad empresarial/profesional, restringen
  reventa sin autorización y exigen autorización para representar a otra entidad.
  Aclarar quién contrata: Kronik o cada prestamista. La situación de demo descrita
  en Notion no acredita que se cumplan esos requisitos.
- La [guía API de estados](https://www.smsmasivos.com.mx/api-docs/webhooks)
  documenta GET/POST, correlación y reintentos, sin precisar firma; un
  [artículo del proveedor](https://www.smsmasivos.com.mx/blog/webhooks-sms-guia-integracion-tiempo-real)
  menciona HMAC y remite a la documentación de cuenta. Pedir el contrato exacto.

Son condiciones publicadas por proveedores, no una certificación jurídica ni
una conclusión sobre la elegibilidad particular de Kronik.

## Escenario de costo para pedir cotización

Ejemplo hipotético: 1,000 créditos con un aviso de pago y un recordatorio cada
uno = 2,000 avisos mensuales, antes de bajas, coincidencias y límites diarios.
Suponiendo un segmento por aviso:

| Opción | Cálculo orientativo, sin conversión de moneda |
| --- | --- |
| Infobip | 2,000 × USD 0.17043 = USD 340.86, usando el promedio público. |
| LabsMobile | 2,000 × EUR 0.0126 = EUR 25.20, usando el tramo de la lista consultada; confirmar que aplique a la compra y cuenta. |
| SMS Masivos | 2,000 × MXN 0.77 = MXN 1,540 más IVA, usando una recarga dentro del tramo de 1,000+. |

No se eligió tipo de cambio ni se igualaron impuestos, ruta o condiciones entre
ofertas. Dos segmentos por aviso duplicarían estas bases. Infobip documenta
[160 caracteres para texto estándar y 70 para Unicode](https://www.infobip.com/docs/sms/message-delivery);
el cálculo final debe considerar el texto y la segmentación reales del proveedor.
La cantidad de créditos y mensajes de este ejemplo no es un dato de cartera.

## Recomendación inicial, anterior a la elección de José

Solicitar condiciones comparables a LabsMobile e Infobip: el primero por su
referencia económica y el segundo por reutilizar el trabajo existente. Mantener
SMS Masivos como alternativa local con facturación CFDI, condicionada a resolver
las discrepancias. Este orden es una inferencia de costo publicado, código
disponible y dudas verificadas; no selecciona proveedor ni autoriza contratación.

Tras la elección, definir con José volumen mensual, presupuesto del piloto, identidad de quien
contrata, primer evento y destinatario autorizado. Preparar para cada candidato
los mismos textos sintéticos y preguntas: aceptación de avisos de crédito en
México, ruta/remitente, segmentación, cobro de fallidos, mínimos, estados y firma
de webhook. No se contactó a proveedores ni se abrieron cuentas.

## Implicaciones para la implementación posterior

El código `ecb9d4a` asigna Infobip en controlador, orquestador, conciliación y UI;
elegir otro proveedor requiere revisar esas superficies además del transporte.
Conservar un proveedor por canal inicialmente, secretos cifrados, consentimiento,
equipo de origen, autorización y auditoría existentes. No añadir fallback ni
otro motor de recordatorios. Ver [contrato de canales](../reference/canales-comunicacion.md).

Antes de programar cambios, revisar la diferencia de la rama frente a `main`,
especialmente la elegibilidad de captura histórica y las lecturas financieras.
Después, probar con HTTP fake/sandbox aceptación y rechazo, falta de saldo,
timeout incierto, límites de segmento, firmas, duplicados, desorden y revocación.
El piloto real requiere autorización de número y gasto; diferenciar aceptación,
acuse del operador, evidencia al dispositivo y confirmación manual de recepción.

La investigación no altera código, configuración, permisos ni emisores auditables.
No requiere un ADR nuevo hasta adoptar o cambiar una decisión duradera; cualquier
cambio de proveedor se registra en [ADR 0050](adr-0050-proveedor-sms-labsmobile.md),
que sustituye parcialmente [ADR 0049](adr-0049-orquestacion-comunicaciones-externas.md).
Evidencia operativa: [Agent Note](../agent-notes/comunicaciones-notificaciones.md).
