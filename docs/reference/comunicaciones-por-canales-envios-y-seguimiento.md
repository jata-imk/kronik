## Prioridad y ampliación vigente — 9 de noviembre de 2026

2026-11-08 — Reunión de producto y revisión de Kronik. Notificaciones y tablero preceden al siguiente desarrollo Revolvente/Nómina (00:33:30). Brian trabaja canales según la reunión; se actualiza a Doing por avance reportado, sin afirmar integración en main fad6b29. En el repositorio revisado no hay centro de notificaciones operativo conectado a AppTopbar; correos de acceso/invitación no acreditan esta entrega. No se revisaron cambios locales de Brian.

### Notificaciones internas en el panel superior

Ampliación aceptada en 00:06:37, dentro de esta tarea:

- [ ]  Campana/indicador de no leídas, bandeja breve y acceso a historial; marcar leída sin alterar el evento de negocio.
- [ ]  Definir catálogo de eventos, destinatarios, prioridades, deduplicación y enlaces al recurso autorizado; persistencia y permisos en backend, no solo componente visual.
- [ ]  Aislar por usuario/equipo/sucursal; no mostrar datos de clientes al cambiar contexto o a usuarios sin permiso.
- [ ]  Estados vacío/carga/error, fechas, teclado, móvil/oscuro y estrategia de actualización; no imponer WebSockets si no son necesarios.
- [ ]  Reutilizar orquestación existente/en desarrollo; distinguir notificación interna al operador de envío externo al acreditado y de bitácora de auditoría.
- [ ]  Pruebas de permisos, lectura, evento duplicado/reintento y contexto de origen. Un correo aceptado por transporte no equivale a entrega efectiva.

Se mantienen los límites ya definidos: avisos operativos/preventivos, correo/SMS/WhatsApp y Telegram opcional; no incorporar cobranza externa por la petición de atrasos del tablero. No se crean mensajes ni plantillas en proveedores durante esta actualización. La fecha previa de revisión se conserva, sin inventar nueva entrega.

## Alcance vigente — controlador de canales y módulo de comunicaciones

Actualización solicitada el 3 de noviembre de 2026. Esta sección complementa la tarea y prevalece sobre las alternativas abiertas de las revisiones anteriores. Se conserva su historial como contexto. Es planificación de trabajo pendiente.

### Objetivo y límites de la primera entrega

Crear un controlador/orquestador de canales para las notificaciones de Kronik, con configuración, conexión, plantillas, envíos y seguimiento desde el backend y el frontend. Considerar correo, SMS y WhatsApp; Telegram queda como canal opcional, desactivado por defecto y sin bloquear la entrega.

El alcance funcional inicial comprende notificaciones de operación y recordatorios preventivos de vencimiento. La cobranza de adeudos vencidos, negociaciones, campañas comerciales y bandeja de conversaciones quedan fuera por ahora. Las menciones anteriores a eventos de atraso se conservan como antecedentes, no como compromiso vigente.

Los acreditados actualmente no tienen portal. Por ello, no depender de acceso a un portal, instalación de PWA o Web Push para recibir los avisos; no incluir enlaces a pantallas que el destinatario no pueda abrir. La pantalla de comunicaciones es para operadores autorizados de Kronik.

Eventos candidatos: pago recibido/comprobante disponible, actualización de solicitud o expediente y próximo vencimiento. Confirmar los primeros eventos y sus disparadores al inspeccionar el flujo real; no activar automáticamente todos los candidatos. Cotizaciones y facturas conservan las dependencias ya documentadas.

### Comparación de canales y decisión de uso

| Canal | Ventajas | Contras y consideraciones | Conexión y habilitación |
| --- | --- | --- | --- |
| Correo | Ya se dispone del contacto de muchos acreditados; admite mensajes completos y documentos; permite iniciar contacto sin mensaje previo. | Spam, rebotes y reputación del remitente; aceptación por el servidor no acredita lectura. Validar que el proveedor acepte el caso financiero. | Elegir SMTP o API transaccional; configurar remitente/dominio y SPF, DKIM y DMARC; probar envío y gestionar rebotes/quejas según capacidades. Registrar autorización para la finalidad. |
| SMS | Usa el teléfono registrado y no exige instalar una app ni iniciar una conversación previa. | Costo por segmento, longitud/codificación, filtros de operadores y capacidad limitada para documentos. La elegibilidad cambia por proveedor, ruta y país. | Seleccionar proveedor que confirme avisos de créditos en México; configurar credenciales/remitente y estados disponibles. No asumir que Twilio es elegible: sus directrices de México consultadas restringen contenido relacionado con dinero/préstamos. |
| WhatsApp — API oficial | Mensajes con botones/documentos y seguimiento disponible según API; permite iniciar contacto autorizado mediante plantillas. | Consentimiento, plantillas, costos, revisión del remitente y políticas por actividad/producto. Aviso preventivo no implica elegibilidad automática del prestamista. | Preparar presencia pública, cuenta de desarrollador, app, portfolio empresarial, WABA y prueba técnica; número real y producción dependen de las validaciones detalladas abajo. |
| Telegram — opcional | Bot sencillo de integrar y útil para quien elija recibir avisos por ese medio. | No basta el teléfono: el acreditado debe usar Telegram, iniciar el bot y vincularse; añade fricción y otro canal que mantener. | Crear bot con BotFather; guardar token seguro, configurar webhook y vincular mediante enlace de inicio con token temporal de un solo uso. Permitir desvinculación; registrar chat_id y autorización. |

Tener correo o teléfono en la ficha no equivale a consentimiento para todos los canales y finalidades. Guardar autorización por acreditado, prestamista, canal y propósito, con fecha, origen y evidencia; respetar bajas y cambios de contacto. No enviar detalles de deuda a referencias o terceros por disponer de sus números.

### Backend — orquestación e integración con el proyecto

- [ ]  Inspeccionar las notificaciones, correo de acceso, colas, scheduler, módulos y convenciones actuales antes de diseñar; reutilizar lo existente y documentar diferencias respecto de la situación descrita en esta tarea.
- [ ]  Crear un servicio/orquestador con contrato de adaptador por canal y endpoints de configuración, prueba y consulta. Los controladores HTTP delegan la lógica; evitar concentrarla en un controlador monolítico.
- [ ]  Separar evento de negocio, notificación, destinatario, canal y proveedor. Usar capacidades explícitas: plantillas, adjuntos, respuestas y estados soportados. Un proveedor por canal basta inicialmente; no construir un marketplace ni fallback automático multicanal.
- [ ]  Aislar conexiones, remitentes, credenciales, preferencias, plantillas y envíos por equipo/prestamista; comprobar permisos y contexto tanto al programar como al ejecutar y procesar webhooks.
- [ ]  Definir matriz evento/propósito/destinatario/canal. Configurar activación, anticipación del recordatorio, zona horaria, ventanas de envío y límite de frecuencia conjunto entre canales.
- [ ]  Reutilizar Laravel Notifications y colas cuando encajen con la arquitectura real. Despachar después del commit y aplicar idempotencia por evento/destinatario/canal; conservar intentos y correlación con proveedor.
- [ ]  Antes de ejecutar, volver a comprobar consentimiento, conexión habilitada, vigencia del evento, saldo y fecha de vencimiento. Cancelar avisos obsoletos tras pago, cambio de calendario, baja o cancelación.
- [ ]  Definir plantillas por canal con variables permitidas y validadas, vista previa y contenido mínimo. En WhatsApp, asociar la versión al identificador, idioma y estado de aprobación; editar contenido local no modifica una plantilla aprobada en Meta.
- [ ]  Guardar secretos cifrados en backend y enmascarados en UI; rotación y revocación. Verificar autenticidad de webhooks, deduplicar eventos y tolerar desorden.
- [ ]  Distinguir programado, en cola, aceptado por proveedor, entregado, leído cuando exista evidencia, fallido, cancelado y resultado incierto. No inferir entrega/lectura por HTTP 200; no reintentar ciegamente un timeout que pudo generar envío.
- [ ]  Auditar cambios de conexión, plantillas, permisos, activación y envíos manuales; integrar eventos en la matriz de auditoría y documentación viva del proyecto. No registrar tokens ni datos sensibles innecesarios.

### Frontend — módulo integrado con el diseño de Kronik

Implementar dentro de la navegación y permisos actuales, reutilizando Vue/Inertia, PrimeVue/Sakai, Tailwind y componentes compartidos según confirme el repositorio. Mantener tipografía, espaciado, estados, formularios, tablas, diálogos, Toast y confirmaciones del sistema. No crear un panel visual ajeno a Kronik.

- [ ]  Vista de canales: tarjetas o filas con proveedor, remitente, estado y última comprobación. Estados comprensibles: sin configurar, configurando, pruebas, pendiente de revisión, conectado, con error o deshabilitado; no mostrar conectado solo porque hay credenciales guardadas.
- [ ]  Acción “Conectar/configurar” con pasos específicos por canal, requisitos, campos, ayudas y validación. Distinguir configuración manual de un onboarding oficial; no prometer OAuth/Embedded Signup si aún no existe.
- [ ]  Acciones según permisos: probar conexión, enviar prueba a un destinatario autorizado, editar, activar/desactivar y desconectar con confirmación. Diferenciar prueba de credenciales y prueba de entrega; mostrar errores útiles sin revelar secretos.
- [ ]  Pantalla de eventos/reglas y plantillas: activar usos permitidos, elegir canal, anticipación, horario, variables, vista previa y estado de plantilla externa. Mensajes claros cuando falta consentimiento, elegibilidad, remitente o aprobación.
- [ ]  Historial con filtros por canal, evento, destinatario, crédito/solicitud, fecha y estado. Detalle de intentos, identificador externo, causa de cancelación/error y evidencias; reintento manual solo cuando sea seguro y esté autorizado.
- [ ]  Incorporar en la ficha del acreditado contactos verificados, preferencias, consentimiento/evidencia y baja por canal. La vinculación opcional de Telegram debe ser realizable sin portal, mediante invitación segura entregada por un medio autorizado.
- [ ]  Incluir estados vacíos, carga, validación, errores y acceso denegado; diseño responsive y accesibilidad de formularios. No exponer conexiones de otros equipos.

### WhatsApp — preparación y checklist específico

Situación declarada: Kronik está en demo, sin sociedad constituida ni registro como PF con actividad empresarial y sin redes sociales. Separar pruebas técnicas, alta de número real, verificación empresarial y provisión del canal a terceros. Una demo funcional no acredita aprobación para producción.

Orden de preparación propuesto para Kronik; la landing es preparación de identidad pública, no un requisito universal para iniciar una prueba técnica.

1. Publicar primero una página sencilla de Kronik en dominio propio: descripción real del software, estado de demo, identidad del responsable, contacto, aviso de privacidad y condiciones aplicables. Mantener nombre y marca consistentes; no presentarse como entidad financiera si Kronik es el software. Las redes sociales no se convierten en un entregable obligatorio.
2. Crear/usar una cuenta personal auténtica de Meta, registrarse como desarrollador y habilitar las medidas de seguridad que solicite la plataforma. Crear una app con el caso de uso de WhatsApp y un portfolio empresarial con información veraz; esto no constituye legalmente una empresa.
3. Configurar WhatsApp Business Account (WABA), número de prueba, destinatarios autorizados y token de pruebas según el panel vigente. Probar mensaje de ejemplo y webhook HTTPS para recepción/estados con datos ficticios. Separar credenciales y tráfico de demo/producción.
4. Evaluar API Cloud directa frente a proveedor oficial: costos, soporte, elegibilidad financiera y facilidad de conexión. Para la prueba inicial favorecer la alternativa más simple; el proveedor no elimina las políticas de Meta.
5. Antes del número real, validar la actividad y modalidad de crédito del remitente y los textos concretos. La política restringe cobranza y ciertos préstamos; un aviso de operación o preventivo puede seguir condicionado por el negocio. Registrar el resultado y no habilitar un uso rechazado.
6. Preparar un número dedicado verificable por SMS/llamada, nombre visible consistente y configuración de cobro aplicable. Revisar reglas vigentes si el número ya usa WhatsApp; evitar migrar el número personal para la demo.
7. Comprobar qué exige Meta a esa cuenta: puede existir inicio limitado sin verificación empresarial completa, pero no garantizarlo. Si se requiere verificación, evaluar documentos legales/fiscales admitidos en México; una web o red social no sustituye documentación. No inventar datos ni asumir que constituir una sociedad es la única vía posible. Registrar límites reales desde el panel.
8. Recabar consentimiento identificando al prestamista y el tipo de avisos; crear plantillas operativas/preventivas con idioma y variables, enviarlas a revisión y reflejar su estado. Inicio por empresa y envíos fuera de la ventana de servicio requieren plantilla aprobada; dentro de 24 horas desde el último mensaje del usuario se permiten respuestas libres, siempre sujetas a políticas.
9. Usar credenciales adecuadas para producción, permisos mínimos y webhook validado; probar estados, bajas, duplicados y fallos antes de activar eventos reales. Aprobación de plantilla, verificación empresarial e insignia son procesos diferentes.
10. Para comercializar la conexión a otros prestamistas, diseñar que cada uno use su identidad, WABA y remitente. Evaluar Tech Provider, revisión de app y Embedded Signup según la modalidad elegida; dejar esta automatización de onboarding para una fase posterior. No concentrar acreditados de todos los clientes en un único remitente de Kronik.

### Entrega incremental y criterios adicionales de aceptación

Orden propuesto, sin compromiso nuevo de fechas: inspección y definición de eventos; base común y correo; prueba técnica de WhatsApp en paralelo a la preparación pública; SMS tras validar proveedor; habilitación productiva de WhatsApp tras sus dependencias. Telegram solo si hay usuarios interesados.

- [ ]  Un operador autorizado configura un canal y realiza una prueba; un usuario sin permisos no puede leer secretos ni modificar conexiones.
- [ ]  Un evento seleccionado genera una sola notificación por canal previsto y registra su resultado real, sin mensajes de cobranza ni campañas activadas por defecto.
- [ ]  Baja, pago previo a ejecución, cambio de calendario y conexión desactivada cancelan o bloquean los envíos correspondientes.
- [ ]  Se prueban aislamiento entre equipos, duplicación de eventos/webhooks, orden inverso, límites/horarios y resultados inciertos mediante adaptadores fake; pruebas reales limitadas a destinatarios autorizados.
- [ ]  La UI conserva los componentes y patrones del proyecto y permite conectar/configurar, probar y consultar intentos con estados comprensibles.
- [ ]  WhatsApp muestra por separado prueba técnica, elegibilidad, número, plantillas y habilitación productiva. Si falta aprobación externa, queda pendiente con su causa y correo puede entregarse independientemente.
- [ ]  Quedan documentados proveedor, costos/volumen estimados antes de contratar, políticas aplicables, configuración, recuperación ante fallos y decisiones pendientes. No se marca esta tarea como terminada por redactar el plan.

### Fuentes para validar antes de habilitar proveedores

Referencias consultadas en la conversación; las políticas y requisitos pueden cambiar y deben comprobarse al implementar.

- Política de WhatsApp Business
- Inicio de WhatsApp Cloud API — Meta
- Registro del remitente — Infobip
- Verificación empresarial — Infobip
- Directrices SMS para México — Twilio
- Revisión de envíos — Amazon SES
- Bots de Telegram: inicio de conversación

## Antecedentes y revisiones previas

## Revisión personal — reunión del 24 de octubre

Fuente: ‣. Actualización autorizada el 26 de octubre de 2026.

Fecha de revisión: 31 de octubre de 2026. José decidirá al revisar cada tarea si se implementa o se pospone; esta fecha no compromete la entrega completa. Los insumos de otros participantes se mantienen como dependencias, sin asignarles tareas en este tablero.

- Canales iniciales expresados: WhatsApp y correo; el impreso se resuelve mediante PDF. Telegram no es requisito de esta primera entrega por esta reunión.
- [ ]  Definir plantillas editables para WhatsApp, con variables permitidas, vista previa, validación y permisos; reutilizar documentos/plantillas donde corresponda sin mezclar contenido PDF y mensajes.
- [ ]  Seleccionar eventos iniciales entre cotización, seguimiento, recordatorio de vencimiento, atraso y comprobantes; no activar todos por inferencia.
- Facturas quedan vinculadas a la entrega contable posterior. Mantener elegibilidad del proveedor, consentimiento, idempotencia, resultados reales y trazabilidad antes de envíos reales.

Como parte de la futura entrega: revisar/emitar los eventos auditables correspondientes, actualizar la matriz, pruebas y documentación viva; justificar cuando un cambio no requiera nuevos eventos. La planeación no acredita código implementado, integración ni validación.

Las secciones anteriores a esta actualización conservan su contexto y evidencia históricos; ante una alternativa abierta, prevalecen las precisiones fechadas de esta revisión.

## Objetivo

Configurar para qué usa Kronik cada canal y seguir el resultado de envíos autorizados.

## Estado y factibilidad

Registrado el 18 de octubre de 2026. Alta factibilidad técnica / dificultad media-alta; elegibilidad por canal pendiente. Implementación pendiente; investigación documental realizada, sin prueba del nuevo comportamiento. Responsables y fechas por acordar. No se asigna prioridad global ni se modifica el cierre de entregas anteriores.

## Situación actual

Existen correo de acceso, colas y scheduler; no un módulo general de comunicaciones. Laravel permite canales personalizados y notificaciones en cola.

## Primera entrega propuesta

Configuración de canales, propósitos/eventos, destinatarios, plantillas, preferencias, horarios y seguimiento de intentos. El usuario confirmó envíos y seguimiento como primera etapa. Seleccionar eventos iniciales en la próxima reunión; candidatos: cotización solicitada, seguimiento al asesor, avisos de expediente/solicitud y alertas internas.

## Ampliaciones

Bandeja de conversaciones, recepción general, promociones/segmentación y asistente externo por entregas posteriores. Los webhooks de estado o vinculación pueden ser necesarios desde la etapa inicial.

## Dependencias e integración

Contacto/cotización conservada, permisos, eventos reales y política por uso/proveedor. WhatsApp no se promete antes de verificar elegibilidad: su política restringe ciertos préstamos, adelantos, P2P y cobranza. Telegram requiere vinculación por interacción con el bot.

## Preguntas y decisiones pendientes

Dos primeros eventos, usuarios internos/externos, contenido mínimo, baja, horarios/frecuencia, volumen/presupuesto y responsable de evaluar cada proveedor. No confundir nómina general con payday loans ni aprobación de plantilla con elegibilidad.

## Aceptación de la futura entrega

- [ ]  La matriz evento/propósito/destinatario/canal define usos habilitados y permisos.
- [ ]  Baja y cancelación de seguimiento obsoleto impiden nuevos envíos; envío posterior al commit y clave idempotente.
- [ ]  Webhooks duplicados/fuera de orden y timeouts inciertos no generan reenvíos ciegos; estados reflejan capacidades reales del canal.
- [ ]  Credenciales y documentos privados no se exponen; API aceptada no se muestra como leído/entregado sin evidencia.
- [ ]  La elegibilidad del producto/uso y plantillas exigibles está documentada antes de habilitar un canal real.

## Verificación prevista

Adaptadores fake, doble dispatch, baja antes de ejecución, horario/zona, obsolescencia, fallos/reintentos y webhooks autenticados/duplicados/fuera de orden. Sin mensajes reales en regresión.

## Procedencia y contexto

- Fuente: 2026-10-17 — Reunión de producto y revisión de Kronik.
- Factibilidad: ‣.
- Preguntas para el equipo: ‣.
- Backlogs relacionados: ‣, ‣.

Transcripción de la reunión del 17 de octubre de 2026 recibida y conservada con fuente y marcas de tiempo en 2026-10-17 — Reunión de producto y revisión de Kronik. Esta tarea registra trabajo futuro; no acredita integración, despliegue o validación de la capacidad.

## Evidencia del contraste de transcripción

00:21:32–00:22:29: Javier León propone envío de corrida a un número de WhatsApp; José Tejero registra la investigación. Telegram, módulo general y primera etapa de envíos/seguimiento se amplían/aclaran después en la conversación con el usuario. Elegibilidad por caso/proveedor todavía pendiente.

## Entregas relacionadas

- ‣
- ‣
- ‣

Los enlaces expresan coordinación; el orden obligatorio se limita a los tipos de crédito confirmados y a las dependencias que se acuerden.

## Actualización 2026-11-06 — Recorrido de WhatsApp verificado

Nota de alcance: esto es preparación y documentación, no construcción en V1. WhatsApp queda fuera de V1 por decisión de Jose del 2026-11-05, después del MVP y detrás de la app móvil. Antes de construir nada, necesita su propio ADR y revisión de la política de Meta.

### Recorrido verificado en la guía oficial de Meta

Fuente principal: guía oficial de Meta "Primeros pasos con la API de la nube de WhatsApp", revisada el 2026-11-06, actualizada el 2026-11-04. La landing de Kronik y el README del repositorio confirman el recorrido de solicitudes, créditos y pagos, pero no los nombres exactos de los campos.

1. Cuenta de desarrollador de Meta: cuenta personal auténtica de Facebook o cuenta de Meta administrada, registro de desarrollador y un dispositivo con WhatsApp para mensajes de prueba.
2. Crear la app con el caso de uso "Conectarse con los clientes a través de WhatsApp". En la guía vigente no aparece una selección de "tipo de app Business" como tal. En ese mismo flujo se elige un portfolio comercial existente o se crea uno nuevo; no se puede avanzar sin elegir uno. Crear el portfolio no constituye legalmente una empresa.
3. Configurar la cuenta de WhatsApp Business (WABA). Meta genera y registra automáticamente un número de teléfono de prueba al completar los primeros pasos.
4. Generar un token temporal y enviar el mensaje de prueba con la plantilla `hello_world`. El token temporal caduca rápido y no sirve para desarrollo continuo; la guía no fija una duración exacta en horas.
5. Para desarrollo continuo, crear un usuario de sistema en Configuración del negocio, asignarle la app y la cuenta de WhatsApp, y generar un token permanente con los permisos `business_management`, `whatsapp_business_messaging` y `whatsapp_business_management`.
6. Configurar el webhook HTTPS para recepción y estados, con verificación de autenticidad, deduplicación y tolerancia a eventos duplicados o fuera de orden.
7. Antes del número real, validar la actividad y modalidad de crédito del remitente y los textos concretos. La política restringe cobranza y ciertos préstamos; un aviso operativo o preventivo no implica elegibilidad automática del prestamista.
8. Preparar un número dedicado verificable por SMS o llamada, nombre visible consistente y configuración de cobro. No migrar el número personal para la demo.
9. Crear plantillas operativas y preventivas con idioma y variables, enviarlas a revisión y reflejar su estado. El inicio por empresa y los envíos fuera de la ventana de servicio requieren plantilla aprobada.

### Qué no se exige para el modo de prueba

- No se exige verificación empresarial para probar.
- No se exige una empresa real constituida para probar.
- No se exige página de Facebook para probar. La página y la landing son preparación de identidad pública, no un requisito universal para iniciar una prueba técnica.
- No se exige método de pago para probar.

### Correcciones respecto a supuestos anteriores

- El límite de "hasta 5 destinatarios de prueba" no quedó verificado en la documentación pública de Meta. La guía solo indica agregar el número receptor. Ese límite, si aplica, solo se confirma dentro del panel de la app (API Setup), con sesión iniciada.
- El token temporal no tiene una duración fija de 24 horas en la guía. Caduca rápido, cada pocas horas, y hay que generar uno nuevo al visitar el panel.

### Límites y costos que aplican al escalar

- Portfolio nuevo: límite de 250 mensajes y 2 números comerciales registrados.
- Tras verificación empresarial o llegar al nivel de 2,000, el límite sube automáticamente a 20 números.
- El límite de mensajería se calcula por portfolio y se comparte entre todos sus números, no es por número. Un solo número puede agotar la capacidad del portfolio.
- Cada número tiene 1,000 mensajes de servicio gratis al mes. Después de ese nivel, los mensajes de servicio se cobran a tarifa de utilidad. Las plantillas de marketing, utilidad y autenticación tienen tarifas por país y por categoría.
- Para producción, cada número necesita su propio método de pago en su propia cuenta de mensajes.

### Modelo multi-financiera

Kronik es software que usarán varias financieras. El modelo correcto es uno por financiera, y coincide con el despliegue del README: una app o instancia por financiera, otra instancia o VPS por cada nueva financiera.

- Cada financiera usa su propia identidad, su propio portfolio, su propia WABA, su propio número, su propio nombre visible, sus propias plantillas y su propia facturación a Meta.
- No concentrar acreditados de todas las financieras en un único remitente de Kronik.
- Los 1,000 mensajes de servicio gratis son por número de negocio, por lo que cada financiera conserva su propio nivel gratuito en su propio número. No es un pozo común.
- Modelo directo por instancia: cada financiera crea su portfolio y su número, y lo conecta en su instancia de Kronik. Es el modelo recomendado para las primeras financieras.
- Modelo Tech Provider, para una fase posterior: Kronik se convertiría en Meta Tech Provider para que las financieras se conecten mediante Embedded Signup a través de la app de Kronik. Requiere negocio verificado y revisión de la app de Meta, con videos de envío y de creación de plantillas, y acceso avanzado a mensajería y gestión. Aun en ese modelo, el número, la WABA y la facturación seguirían siendo de cada financiera. Hoy no es alcanzable porque Kronik no tiene empresa constituida ni verificada.

### Propuestas de plantillas — borrador 2026-11-06

Estado: borrador para revisión de Jose. Nada creado en Meta ni enviado. Categoría Utilidad, idioma es_MX, variables numeradas como pide Meta. Cada financiera aprueba sus propias plantillas con su propio nombre visible.

1. `pago_recibido` — Pago recibido / comprobante disponible
    - Texto: Hola {{1}}, recibimos tu pago de ${{2}} MXN para tu crédito {{3}} el {{4}}. Tu comprobante está disponible. Gracias por tu pago.
    - Ejemplo: Hola María, recibimos tu pago de $1,500 MXN para tu crédito KR-1042 el 05/11/2026. Tu comprobante está disponible.
    - Variables: {{1}} nombre del acreditado, {{2}} monto pagado, {{3}} identificador del crédito, {{4}} fecha del pago.
    - Pendiente: no prometer enlace de comprobante, porque los acreditados hoy no tienen portal.
2. `vencimiento_proximo` — Recordatorio preventivo de vencimiento
    - Texto: Hola {{1}}, te recordamos que tu pago de ${{2}} MXN de tu crédito {{3}} vence el {{4}}. Si ya lo realizaste, ignora este mensaje.
    - Ejemplo: Hola María, te recordamos que tu pago de $1,500 MXN de tu crédito KR-1042 vence el 10/11/2026. Si ya lo realizaste, ignora este mensaje.
    - Variables: {{1}} nombre, {{2}} monto del pago próximo, {{3}} identificador del crédito, {{4}} fecha de vencimiento.
    - Pendiente: anticipación propuesta, 3 días antes, y opcionalmente 1 día antes.
3. `solicitud_actualizacion` — Actualización de solicitud / expediente
    - Texto: Hola {{1}}, tu solicitud {{2}} cambió a: {{3}}. {{4}}
    - Ejemplo: Hola María, tu solicitud SOL-221 cambió a: En evaluación. Te avisaremos cuando haya un siguiente paso.
    - Variables: {{1}} nombre, {{2}} folio de solicitud, {{3}} nuevo estatus, {{4}} siguiente paso o indicación breve.
    - Pendiente: lista cerrada de estatus y texto de siguiente paso para cada uno.

Alcance respetado: solo notificaciones operativas y recordatorios preventivos. Ninguna plantilla menciona adeudo vencido ni pide pago urgente. Cobranza, negociaciones, campañas comerciales e inbox quedan fuera por ahora.

### Las mismas plantillas pueden funcionar para los demás canales

Jose confirmó el 2026-11-06 que estas propuestas podrían funcionar también para los demás canales. El mensaje base es el mismo en todos: nombre, monto o folio, fecha o estatus, y siguiente paso. Lo que cambia es el formato y las reglas de cada canal, no el contenido mínimo.

- Correo: puede llevar el texto completo, más contexto y documentos adjuntos, como el comprobante, con remitente y dominio configurados.
- SMS: mismo contenido mínimo, más corto, sin botones ni documentos, y con costo por segmento. Hay que validar proveedor y elegibilidad en México para avisos de créditos.
- WhatsApp: requiere plantilla aprobada para iniciar fuera de la ventana de 24 horas, con variables numeradas y sin enlace a portal.
- Telegram, opcional: requiere que el acreditado inicie el bot y se vincule; no basta con tener su teléfono.

Antes de reutilizarlas, hay que confirmar por canal: consentimiento por acreditado, prestamista, canal y propósito; longitud y variables permitidas; si se incluye enlace o documento; y qué estatus o textos se permiten en cada canal.

### Pendientes para cerrar antes de crearlas en Meta

- Jose corrige o aprueba los 3 textos.
- Jose elige la anticipación del recordatorio preventivo: 3 días antes, 1 día antes, o ambos.
- Jose cierra la lista de estatus de solicitud y el texto de siguiente paso de la plantilla 3.
- Confirmar los nombres exactos de los campos en Kronik: monto, fecha, folio y estatus. La landing y el README confirman el recorrido, pero no los nombres de campo.
- Registrar el resultado de la validación de actividad y textos antes de habilitar un uso en producción.