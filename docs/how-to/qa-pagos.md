# Guion integral QA: vida de un crédito simple

Este documento guía una primera prueba manual de punta a punta: configurar un
producto, preparar cliente y expediente, originar/evaluar/aprobar una solicitud,
formalizarla en modo QA, registrar un desembolso ficticio y probar pagos. Está
organizado por etapa para que operación, analistas, cumplimiento y contador puedan
seguir el mismo caso.

> **Solo QA.** El paquete y la revisión de firma son documentos/evidencias de
> prueba sin validación contractual; el desembolso y los pagos son registros
> internos y no mueven dinero. `ORIGINACION_*_QA_HABILITADOS=true` abre esas
> funciones de QA, no las convierte en operaciones productivas. No uses un cliente
> real, una cuenta bancaria, una transferencia ni referencias reales. Si el sitio
> se llama “producción” pero actualmente solo aloja pruebas, comprueba que base,
> archivos, usuarios, correos e integraciones no atiendan operaciones reales.

## 0. Preparar el entorno y repartir el trabajo

### Comprobar que realmente es QA

Antes de habilitar acciones o capturar información:

- Confirma con quien administra la instalación qué base de datos, almacenamiento
  privado, colas y correo utiliza. Deben pertenecer al entorno de prueba.
- Usa datos sintéticos y una sucursal de pruebas. No reutilices personas reales ni
  archivos de identificación de clientes reales.
- Usa una cuenta ficticia y referencias claramente identificadas, por ejemplo
  `QA-2026-CR-001`; nunca escribas una referencia bancaria real.
- Los comprobantes de firma/desembolso/pago deben ser archivos o referencias
  sintéticos. No ejecutes transferencias.
- No consultes proveedores SIC reales ni habilites credenciales de producción para
  este ejercicio. Para la evaluación utiliza la revisión manual permitida por la
  política del producto.
- Asegura que `APP_DEBUG=false`, que no se envían notificaciones reales a clientes
  y que existe respaldo recuperable antes de probar. No uses `migrate:fresh`,
  seeders de desarrollo/E2E ni limpiezas masivas en una instalación persistente.

### Banderas de QA

Para recorrer todas las etapas en una instalación estrictamente QA, las puertas
correspondientes deben estar habilitadas y la caché de configuración reconstruida:

| Variable | Habilita | Límite |
| --- | --- | --- |
| `ORIGINACION_APROBACIONES_HABILITADAS=true` | Confirmar aprobación de solicitudes | No sustituye requisitos, permisos ni separación dual. No es una bandera de QA. |
| `ORIGINACION_PAQUETES_QA_HABILITADOS=true` | Preparar el paquete contractual marcado QA | El PDF no tiene validez contractual. |
| `ORIGINACION_FIRMAS_QA_HABILITADAS=true` | Recibir y revisar copia de firma en QA | No valida firma criptográfica ni identidad legal. |
| `ORIGINACION_DESEMBOLSOS_QA_HABILITADOS=true` | Registrar desembolso ficticio | No ordena ni confirma transferencia bancaria. |
| `ORIGINACION_PAGOS_QA_HABILITADOS=true` | Registrar/revertir pagos de créditos QA | No habilita pagos productivos ni movimiento de dinero real. |

No pongas estas banderas en una instalación que atienda operaciones reales para
completar la guía. Si la VPS hoy contiene solamente datos y operaciones sintéticos,
puedes seguir tras comprobar también correo, colas e integraciones. Si comparte
alguno de esos recursos con operaciones reales, aísla la prueba antes de continuar.

Las banderas no otorgan permisos. Un administrador debe asignar permisos mínimos y
contextos de sucursal a cada cuenta. El seeder crea permisos, pero no los asigna a
roles personalizados. No compartas contraseñas entre participantes.

### Separación sugerida de roles

Usa cuentas distintas para probar tanto el flujo positivo como las denegaciones:

| Participante | Trabajo en esta prueba | Permisos típicos a revisar |
| --- | --- | --- |
| Administrador de producto | Crear/configurar versión, política y fiscalidad de prueba; preparar plantilla QA | Administración de productos y documentos; no necesita registrar pagos. |
| Capturista/asesor | Crear cliente si corresponde, completar expediente, crear y enviar solicitud | Clientes/expediente, documentos, crear/editar/asignar solicitudes. |
| Analista | Registrar dictamen de evaluación manual y capacidad de pago | `read evaluacion-solicitudes` y `create evaluacion-solicitudes`, además de lectura de cliente/solicitud. |
| Cumplimiento | Documentar revisión humana y nivel de riesgo | `read cumplimiento` y `create cumplimiento`, además de lectura de cliente/solicitud; notas PLD reservadas. |
| Aprobador | Revisar requisitos y confirmar resolución | `approve solicitudes`; en modalidad dual, distinto de quien capturó o envió. |
| Formalización | Preparar paquete y recibir copia de firma | `prepare package solicitudes`, `receive signature solicitudes`, `read documentos`, `generate documentos` y lectura de solicitud/cliente. |
| Revisor de firma | Comparar original/copia y aceptar o devolver | `review signature solicitudes`; usa otra cuenta para probar permisos. La identidad distinta del receptor no es una restricción impuesta por el código. |
| Operador de desembolso | Registrar el desembolso ficticio | `disburse creditos`, además de los accesos requeridos a solicitud/documentos. |
| Cajero/operador de pagos | Previsualizar y registrar pago QA | `pay creditos`; acceso al crédito y sucursal correcta. |
| Revisor de correcciones | Revertir el último pago QA con motivo | `reverse payments creditos`, separado del permiso de pago. |
| Cliente de prueba | Persona sintética del expediente; no es una cuenta de usuario | No presupongas que existe un portal de autoservicio para el cliente. |

Las cuentas `consulta.clientes@example.test`, `editor.expedientes@example.test` y
`sin.acceso.clientes@example.test` están descritas en la [referencia de seeders](../reference/seeders.md)
y en el [guion documental](qa-backlog-03-5-documentos-y-plantillas.md). Son cuentas
de datos demo, no garantizadas en la VPS. No ejecutes seeders de desarrollo en la
instalación persistente para crearlas; pide al administrador cuentas QA dedicadas.

Comprueba que cada usuario selecciona la sucursal de prueba antes de escribir. Un
permiso global no debe permitir mutar una solicitud/crédito desde otra sucursal.
La sucursal activa y el permiso se validan también en el servidor.
Para consultar un crédito se necesitan además `read creditos`, `read solicitudes`
y `read clientes`. Quien descargue el PDF contractual necesita `download documentos`;
quien registre desembolso necesita lectura documental. Los permisos de pago y
reverso se conceden por separado, pero el código no exige operadores diferentes.

## 1. Configurar producto y versión de prueba

Con el administrador de producto, crea un producto identificable como QA o duplica
uno de prueba y crea una nueva versión borrador. No alteres una versión ya usada por
una solicitud o crédito: las versiones y evidencias formalizadas quedan congeladas.

Configura y registra en la hoja de prueba:

- Monto mínimo/máximo, tasa ordinaria anual, mora anual, periodicidad y número de
  pagos permitidos.
- Método de amortización, días de gracia y alternativa A/B. Para una primera
  prueba sencilla se recomienda gracia A; anota la selección.
- Regla de mora: **ambos intereses** o **moratorio sustituye al ordinario**.
  Si pruebas B + sustitución, la aplicación bloquea ciertos pagos parciales durante
  la gracia; no fuerces el caso ni cambies fechas para evadir el bloqueo.
- Política de originación: documentos requeridos, modalidad individual/dual,
  límite y vigencia de aprobación, regla de capacidad de pago y SIC requerido o
  revisión manual permitida. Para probar la separación de funciones, usa modalidad
  dual y prepara un aprobador distinto de quien capture o envíe. Para este recorrido
  permite revisión manual; una política que exija SIC integrado bloqueará la
  aprobación mientras no exista esa consulta válida. No intentes una consulta SIC
  real.
- Para cada concepto fiscal por separado —interés ordinario, moratorio y cada
  comisión— define en modo de prueba si está gravado, exento, no causa o sigue sin
  definir; si está gravado, define tasa y base configuradas para QA. No asumas 16 %,
  exención ni tasa cero como regla universal. El capital no se trata como base de
  impuesto automáticamente.
- Comisiones: nombre, importe, modalidad (financiada, descontada o pago separado),
  obligatoriedad y tratamiento fiscal de cada una. Omite comisiones opcionales en
  la primera corrida para reducir variables; prueba una segunda corrida si quieres
  cubrirlas.

Guarda una captura o transcribe la versión, sus valores y la marca **Prueba/QA**.
Prueba en un borrador separado que un concepto aplicado y fiscalmente “sin definir”
no se trate como exento ni produzca una proyección completa. El moratorio puede no
intervenir en una tabla sin atraso, pero el motor de pagos exige su fiscalidad
explícita. Para la solicitud que sí avanzarás, define ordinario, moratorio y cada
comisión aplicada. Si cambias el producto luego, verifica que no se alteren las
condiciones congeladas del caso ya creado.

Para revisar pantalla y cálculos del producto antes de seguir, usa
[configuración fiscal de producto](configurar-fiscalidad-productos.md) y
[simulación fiscal QA y revisión del contador](qa-simulacion-fiscal.md).

## 2. Preparar plantilla documental y cliente sintético

### Plantilla

En **Documentos y plantillas**, crea/activa una plantilla de Contrato marcada como
prueba, con identificadores visibles como `QA — SIN VALIDEZ CONTRACTUAL`. No copies
un contrato de una institución para aparentar aprobación. Revisa que el PDF generado
muestre la marca QA en todas sus páginas y que una modificación posterior de la
plantilla no reescriba documentos históricos.

La activación de una plantilla solo la hace seleccionable: **no aprueba su contenido
jurídicamente**. Ve el [guion del paquete contractual QA](qa-paquete-contractual.md)
para editor, variables y visor.

### Cliente y expediente

Usa un cliente persona física sintético dentro de la sucursal QA. Captura solo lo
necesario para probar el flujo. Completa secciones de identidad/datos fiscales,
domicilio, perfil económico, referencias y documentos que la política requiera.
Carga archivos sintéticos claramente marcados y valida cada uno como el rol autorizado.

Qué comprobar:

- Los campos obligatorios y errores aparecen en español, conservan lo capturado y
  explican cómo corregirlo.
- Los documentos muestran tipo, vigencia/estado, actor y acciones disponibles.
- Reemplazar o validar evidencia deja claro qué dictámenes o requisitos previos
  pueden quedar desactualizados. Haz las validaciones documentales antes de emitir
  los dictámenes para evitar repetir trabajo.
- La cuenta de solo lectura puede consultar lo autorizado pero no editar ni validar;
  la cuenta sin acceso no puede entrar tampoco usando una URL directa.
- El vínculo de **Nueva solicitud** desde el cliente precarga a la persona correcta.
- Los archivos permanecen privados: una URL directa sin permiso/sesión no los abre.

Para pruebas exhaustivas de carga, privacidad y documentos consulta el [guion de
Documentos y plantillas](qa-backlog-03-5-documentos-y-plantillas.md); este recorrido
solo necesita los documentos mínimos requeridos por la política.

## 3. Crear y completar la solicitud

Como capturista, selecciona la sucursal QA y crea una solicitud para el cliente
sintético. Puedes abrirla desde el detalle del cliente o buscarlo al crearla.
Selecciona la versión de producto preparada y captura importe, periodicidad,
número de pagos, método, fecha estimada de disposición y destino. Si planeas llegar
al desembolso en esta misma sesión, selecciona **la fecha de hoy en la zona horaria
de la institución** y termina aprobación, contrato y firma antes de que cambie el
día. El desembolso exige coincidencia exacta con esa fecha firmada; no admite fecha
retroactiva. Si el día cambió, devuelve y rehace la revisión, aprobación, contrato
y firma con la nueva fecha.

Al guardar borrador, verifica que aún no aparezca como tarea de evaluación ni permita
aprobar. Revisa el resumen de requisitos pendientes, asigna responsable activo y
envía a revisión cuando la captura y el expediente estén listos.

Comprueba:

- El cliente/producto/sucursal están correctos y se muestran sin tener que volver
  a capturarlos en los siguientes módulos.
- Los importes muestran formato monetario, y los mensajes indican límite/rango y
  qué debe corregir el capturista.
- `Mi trabajo` y la bandeja de Solicitudes muestran responsable, etapa y acción
  siguiente; un filtro o búsqueda permite reencontrar el caso.
- Existe bitácora de creación, asignación, envío y cambios relevantes.
- Si se devuelve, el motivo operativo es claro, el responsable puede corregir y
  reenviar, y se crea una nueva revisión sin borrar la anterior.
- Si se cancela o rechaza, la solicitud sale de pendientes normales pero puede
  encontrarse con filtros; no se edita/reabre como si siguiera activa.
- No se permite enviar condiciones fuera de los límites o una versión no vigente.

Sigue el apartado de operación de [Solicitudes, evaluación y aprobación](../reference/originacion-solicitudes.md)
si una acción o bloqueo requiere contexto.

## 4. Evaluar y revisar cumplimiento

Estas decisiones las realizan personas. La pantalla puede organizar información y
marcar requisitos, pero no determina automáticamente solvencia, fraude o cumplimiento.
Mantén la consulta SIC real fuera de esta primera prueba.

### Evaluación

Con el analista asignado, revisa solicitud, expediente, ingreso/deuda declarados,
capacidad y condiciones del producto. Registra el dictamen manual con resultado,
fundamento, fuente/metodología y datos mínimos sintéticos. Confirma que el resumen
muestre el último dictamen y que el formulario anterior no siga abierto como si
pudiera editarse: un dictamen nuevo crea evidencia nueva.

Prueba un escenario separado que se devuelva para corregir datos y reenviar. El
dictamen de la revisión previa debe quedar histórico y no satisfacer automáticamente
la revisión nueva. No cargues reportes de crédito reales ni notas innecesarias.

### Cumplimiento/PLD

Con el responsable de cumplimiento, revisa las fuentes y evidencias que la instalación
realmente tenga configuradas. Registra nivel de riesgo, resultado, fundamento y
seguimiento manual. Si no existe proveedor/lista conectado, no interpretes la
ausencia de coincidencias como una búsqueda realizada: documenta que la comprobación
fue manual y limitada a las fuentes revisadas.

Comprueba que:

- Los campos requeridos impiden cerrar un dictamen incompleto y explican cómo
  completarlo.
- El usuario operativo sin permiso de cumplimiento no ve notas reservadas ni las
  obtiene alterando una URL o inspeccionando el detalle que recibe.
- Un resultado pendiente/desfavorable o riesgo sin determinar no se presenta como
  autorización favorable ni permite saltarse requisitos.
- Devolver la solicitud indica responsable y siguiente paso, sin filtrar detalles
  reservados de PLD en el motivo operativo.
- Los cambios de evidencia indican qué dictamen debe renovarse y por quién.

## 5. Revisar requisitos y aprobar

Antes de aprobar, el aprobador revisa de nuevo el expediente y la lista de
**Requisitos para aprobación**. No basta con que un contador o analista haya llenado
campos: valida vigencia, evidencia, dictámenes aplicables a la revisión actual,
límites de producto, sucursal, política y separación dual.

Prueba ambos resultados en solicitudes separadas:

1. **Bloqueada:** deja un requisito incompleto o usa una solicitud/dictamen obsoleto.
   La interfaz debe nombrar lo pendiente, explicar por qué y orientar a la persona
   responsable. Corregir la causa debe refrescar el estado; no debe “arreglarse” con
   un dictamen no relacionado.
2. **Aprobada en QA:** completa requisitos actuales y aprueba con el rol autorizado.
   En modalidad dual, el aprobador no debe ser quien capturó/envió. Comprueba fecha,
   actor, evidencia y vigencia de aprobación en el historial.

Si aparece habilitación operativa pendiente, verifica con el administrador que la
bandera `ORIGINACION_APROBACIONES_HABILITADAS` esté intencionalmente activa en QA;
no intentes resolverla cambiando PLD o documentos. Apagarla debe bloquear nuevas
aprobaciones, no borrar resoluciones anteriores.

## 6. Preparar contrato y recibir/revisar firma QA

En la solicitud aprobada, abre **Contrato y tabla de pagos** y genera el paquete con
la plantilla QA. Comprueba cliente, producto, versión, monto, tasa, comisiones,
impuestos configurados, periodicidad, fechas, número de cuotas y total. La tabla es
una proyección y el paquete debe indicar **Documento de prueba QA — sin validez
contractual**.

Para probar los permisos, usa dos cuentas diferentes. Esta separación es una
disciplina de QA; el sistema no bloquea por identidad a quien tenga ambos permisos.
Comprueba la fecha de recepción: debe estar entre la generación del original y hoy.

Luego:

1. El receptor carga una copia sintética digitalizada distinta del original y fecha
   válida; intenta adjuntar el mismo original y verifica que se rechace.
2. El revisor compara todas las páginas y condiciones. Primero prueba un rechazo
   con motivo de corrección; recibe otra copia sintética y aprueba la revisión manual.
3. Revisa huellas, actor/fechas e historial. No se debe afirmar que hubo verificación
   criptográfica o biométrica. La formalización resultante es **QA**, no una firma
   jurídicamente validada.

No edites PDF ni cambies fechas para superar un bloqueo. Para pasos/validaciones
detallados, consulta [firma digitalizada QA](qa-firma-digitalizada.md) y
[paquete contractual QA](qa-paquete-contractual.md).

## 7. Registrar desembolso ficticio y abrir el crédito

Como operador con permiso de desembolso, abre el desembolso QA desde la solicitud o
el contrato **el mismo día indicado en la tabla firmada**. Confirma cliente,
sucursal, fecha, efectivo entregado, capital financiado y retenciones contra el
paquete. Usa una referencia sintética. La pantalla
de confirmación debe dejar claro que es una captura QA: **no se ejecutó transferencia**.

Después verifica que:

- Se creó un solo crédito QA con número, cronograma firmado/proyectado, versión y
  movimiento inicial coherentes con el paquete.
- El crédito conserva las condiciones y documentos usados al formalizar. Cambiar
  producto o plantilla no reescribe el crédito histórico.
- La solicitud muestra desembolso QA y acceso al crédito; no permite repetir la
  operación con la misma solicitud/referencia.
- Lista, detalle y expediente navegan entre sí con cliente y sucursal correctos.
- Un lector puede consultar lo que le corresponde pero no desembolsar; otra
  sucursal y URL directa no autorizada quedan bloqueadas.

No confundas **capital inicial**, **saldo calculado al día**, **importe exigible**
y **tabla contractual**: son cifras distintas. Consulta el [guion de desembolso QA](qa-desembolso.md)
para estados, duplicados y evidencia.

## 8. Registrar pago QA, recibo y reverso

Los pagos solo pueden probarse en un crédito QA recién desembolsado con política y
fiscalidad explícitas. Una cuota de capital con vencimiento futuro **no es exigible
hoy**: aunque registres el desembolso hoy, el sistema no permite pagarla por
anticipado. Para comprobar el pago el mismo día, crea desde el principio una
comisión inicial con modalidad **pago separado**, fiscalidad definida y monto de
prueba; ese pago cubre la comisión, no amortiza capital. Para comprobar una primera
cuota con interés y capital, vuelve cuando llegue su vencimiento real. No adelantes
vencimientos, alteres el reloj ni inventes fechas pasadas para fabricar saldo.

El operador con `pay creditos` debe:

1. Abrir **Registrar pago QA**, capturar importe, fecha efectiva y referencia
   sintéticos y revisar la vista previa antes de confirmar.
2. Verificar distribución cuota antigua primero: comisión → moratorio → ordinario
   → capital. Los impuestos proporcionales siguen al concepto gravado y su propia
   configuración. Ejemplo de QA, no regla fiscal: comisión de $500 más impuesto
   configurado de $80; un abono de $290 se distribuye $250 a comisión y $40 a su
   impuesto.
3. Cambiar importe/fecha/referencia después de previsualizar: la vista previa debe
   invalidarse y exigir revisión nueva. Confirmar una vez debe crear recibo e
   historial; reintentar no duplica.
4. Probar fecha futura, anterior al desembolso, exceso y referencia repetida: deben
   rechazarse sin cambio parcial de saldo. La prueba de fecha pasada con movimientos
   posteriores requiere otro caso que ya tenga movimientos en un día posterior;
   anótala como pendiente si solo hiciste la prueba del mismo día.
5. Si el escenario lo permite, probar parcialidad y fiscalidad. Para gracia B +
   sustitución al ordinario, la combinación afectada puede bloquear pagos parciales
   durante la gracia; registrar el bloqueo como hallazgo, no eludirlo.
6. Como lector/sin permiso, intentar registrar y abrir recibo por URL. La operación
   debe denegarse con mensaje en español. Cambiar a otra sucursal debe bloquearla.
7. Con otra cuenta y `reverse payments creditos`, revertir únicamente el último
   pago permitido, indicando motivo. El registro original debe permanecer y quedar
   enlazado a movimientos compensatorios. Esto no devuelve dinero ni borra historia.

Este flujo aplica solo a datos ficticios; la [decisión técnica de P6](../explanation/adr-0020-pagos-manuales-qa.md)
documenta límites como anticipos, liquidación anticipada, conciliación bancaria,
CFDI y operaciones monetarias reales aún no cubiertas.

## 9. Revisión del contador o asesor

Invita al contador a revisar la **configuración de prueba y los cálculos**, no a
certificar que el producto ya está listo para venderse. Entrega una copia/exportación
de la versión QA, una tabla con escenario y resultados, la simulación, el paquete
marcado sin validez y los recibos sintéticos. Pídele documentar observaciones,
supuestos, alcance institucional y fecha; no cambies configuración productiva desde
una conversación informal.

### Lista de revisión

- **Concepto por concepto:** tratamiento declarado para interés ordinario,
  moratorio y cada comisión; distinguir gravado, exento, no causa y sin definir.
- **Base y tasa:** confirmar para cada gravamen cuál es la base configurada y tasa
  usada en el ejemplo. Verificar que el principal no se grave automáticamente y
  que el sistema no convierta “sin definir” en exento/tasa cero.
- **Mora:** revisar escenarios de días de gracia A y B, fecha desde la que inicia
  el moratorio y regla elegida entre ambos intereses o sustitución. Pedir revisión
  de un caso numérico sencillo y anotar cualquier regla especial que el motor aún
  no soporte.
- **Cálculo:** cotejar días, periodicidad, amortización, interés ordinario/moratorio,
  impuestos por concepto, suma de cuotas, saldo final y distribución de pagos
  parciales. Revisar redondeos y centavos entre proyección, recibo y movimientos.
- **Comisiones:** confirmar importe, obligatoriedad, momento de cobro y modalidad
  (financiada, descontada o separada), además de su tratamiento fiscal independiente.
- **Reversos y fechas:** comprobar ejemplos de pago parcial, fecha efectiva/captura,
  reverso compensatorio y límites de corrección. El sistema bloquea pago retroactivo
  si ya existen movimientos posteriores.
- **Documentos/contrato:** el contador puede revisar consistencia aritmética y
  presentación de importes. La aprobación jurídica del contenido es una revisión
  separada que corresponde a la institución; una plantilla activa no la acredita.
- **Alcance no implementado:** conciliación bancaria, CFDI, retenciones,
  acreditamientos/declaraciones, liquidación anticipada, anticipos y casos de
  compensación futura no deben darse por cubiertos por esta prueba.

Pide al contador que marque cada punto como “revisado para el escenario”,
“requiere ajuste” o “fuera de alcance”; no es necesario que dé visto bueno a una
regla legal que no haya validado. La guía de [simulación fiscal QA](qa-simulacion-fiscal.md)
incluye preguntas ampliadas para una revisión profesional. **No existe una tasa
universal que esta aplicación deba asumir**: la configuración final corresponde a
cada institución con su asesor.

## 10. Cierre de la sesión y registro de hallazgos

Por cada etapa anota identificador QA del caso (sin datos personales), rol probado,
sucursal, resultado esperado/observado, fecha, captura sin datos sensibles y
severidad. Separa fallas de interfaz, permiso, negocio, cálculo y explicación.

- No pegues contraseñas, tokens, RFC/CURP/domicilios reales, notas PLD, reportes SIC,
  contenido documental ni secretos en el reporte.
- No reviertas pagos solo para “limpiar” el crédito; la evidencia es inmutable.
- Conserva las referencias sintéticas y anota si una bandera se cambió. Al terminar,
  el administrador puede deshabilitar las puertas QA que ya no se necesiten y
  reconstruir la caché, sin borrar los registros.
- Si el entorno no está aislado de operaciones reales, detén las pruebas y solicita
  una instancia/base de QA separada antes de continuar.

Las guías enlazadas abajo describen incrementos anteriores y algunas conservan
frases como “pagos pendientes” referidas a su PR original. Para el flujo integrado
vigente, usa esta secuencia y consulta allí los pasos detallados de cada pantalla.

### Documentación complementaria

- [Configuración fiscal de producto](configurar-fiscalidad-productos.md)
- [Simulación fiscal y revisión profesional](qa-simulacion-fiscal.md)
- [Solicitudes, evaluación y aprobación](../reference/originacion-solicitudes.md)
- [Paquete contractual QA](qa-paquete-contractual.md)
- [Recepción y revisión de firma QA](qa-firma-digitalizada.md)
- [Desembolso QA](qa-desembolso.md)
- [Pagos manuales QA y ADR](../explanation/adr-0020-pagos-manuales-qa.md)
