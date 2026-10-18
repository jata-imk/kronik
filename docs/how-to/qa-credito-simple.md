# Guía operativa QA: crédito simple para un prestamista o financiera pequeña

Revisión funcional: **17/10/2026**, contra `main` en `f3581cb`, con SIC y P8
(PR #36) y la portada ampliada (PR #37) integrados. Este documento describe el
código revisado; comprueba que tu instalación tenga esa entrega o una posterior.

Este documento guía una primera prueba manual de punta a punta: configurar un
producto, preparar cliente y expediente, originar/evaluar/aprobar una solicitud,
formalizarla en modo QA, registrar un desembolso ficticio, probar pagos y anticipos,
liquidar el crédito y comprobar su reapertura mediante reverso. Incluye la consulta
SIC sandbox cuando la política del producto la requiere. Está organizado por
etapa para que operación, analistas, cumplimiento y contador puedan
seguir el mismo caso.

Está dirigida tanto a quien presta y opera individualmente como al equipo de una
financiera pequeña que quiere ensayar su operación, incluida una SOFOM. El resultado
es una **aceptación operativa de QA**. El perfil institucional SOFOM, su validación
regulatoria y la habilitación de SIC/dinero reales continúan como entregas pendientes.

### Alcance del caso

| Concepto | Qué puedes recorrer hoy |
| --- | --- |
| Otorgante | Configuración de empresa persona física o moral; una instalación por institución. |
| Acreditado | Persona física con identidad fiscal registrada. Configurar al otorgante como persona moral no habilita créditos a personas morales. |
| Crédito | Crédito simple en MXN; capital fijo o cuota nivelada; periodicidad semanal, quincenal o mensual permitida por la versión. |
| Disposición | Un desembolso manual QA por solicitud formalizada. |
| Vida del crédito | Pago exigible, anticipo, liquidación, recibos, versiones y reversos compensatorios. |
| SIC | Tres servicios Círculo de Crédito en sandbox e histórico disponible; requisito según política. |
| Decisiones | Evaluación y PLD manuales, aprobación individual o dual explícita y revisión manual de firma digitalizada. |

Este recorrido no cubre crédito revolvente, disposiciones múltiples, nómina,
reestructuras ni productos especializados por el hecho de cambiarles el nombre.

## Cómo usar esta guía

**Para tu primera prueba, sigue únicamente este archivo, en orden del apartado 0
al 10.** En 0.1 elige operación individual o en equipo; en 1.1 encontrarás un
caso con importes para cotejar. Cada apartado dice qué hacer y qué resultado
esperar. Las demás guías
`qa-*.md` amplían una etapa para investigar un bloqueo, repetir casos difíciles o
revisar cálculos con el contador; no tienes que ejecutarlas todas. Los comandos de
pruebas automatizadas que aparecen al final de algunas son para desarrollo: no los
ejecutes en la VPS durante la prueba manual.

Si ya desplegaste el código y migraste la base, no repitas el despliegue por seguir
esta guía. Empieza verificando banderas, permisos y sucursal. Cuando una etapa pida
otro rol, cierra sesión y entra con la cuenta QA correspondiente; el cliente
sintético del expediente no necesita iniciar sesión.

Para empezar una nueva ronda eliminando los casos anteriores, consulta
[Reiniciar datos de desarrollo o QA](reset-datos-desarrollo.md). El modo mínimo
conserva un superadministrador y la configuración básica; requiere vista previa,
respaldo y detención de procesos antes del borrado. No lo ejecutes en una
instalación con datos reales.

| Si necesitas profundizar en... | Abre después... |
| --- | --- |
| Impuestos y ejemplos numéricos con el contador | [Simulación fiscal](qa-simulacion-fiscal.md) |
| Datos de la institución y domicilio | [Configurar empresa](configurar-empresa.md) |
| Equipos, usuarios, invitaciones y sucursales | [Usuarios, equipos y sucursales](../reference/usuarios-equipos-sucursales.md) |
| Acceso global y permisos por equipo | [Roles y permisos](../reference/roles-y-permisos.md) |
| Generación, PDF o reintento del contrato | [Paquete contractual](qa-paquete-contractual.md) |
| Carga, rechazo o aceptación de la copia firmada | [Firma digitalizada](qa-firma-digitalizada.md) |
| Registro único del desembolso y búsqueda del crédito | [Desembolso](qa-desembolso.md) |
| Recibos, errores y reversos de pagos | [Pagos](qa-pagos.md) |
| Tres APIs CDC, histórico, gráficos y conciliación de intentos inciertos | [SIC sandbox](qa-sic.md) |
| Anticipos, cotización de liquidación y versiones del calendario | [Anticipos y liquidación](qa-anticipos-liquidacion.md) |

### Qué significa cada etapa

| Etapa | Qué representa en esta prueba |
| --- | --- |
| Producto y versión | Las condiciones financieras y reglas que podrá elegir la solicitud. |
| Cliente y expediente | La persona de prueba y sus datos/documentos de respaldo. |
| Solicitud | La petición de crédito; un borrador todavía no se evalúa. |
| SIC sandbox | Respuesta de simulación vinculada a consentimiento e identidad; si se usa para aprobación, también a la revisión actual. |
| Evaluación y cumplimiento | Dos dictámenes humanos sobre el caso. Ninguno por sí solo aprueba el crédito. |
| Aprobación | La resolución autorizada, todavía sin contrato ni desembolso. |
| Contrato y tabla de pagos | PDF de prueba con condiciones y calendario congelados. |
| Firma digitalizada | Copia PDF recibida y revisada manualmente; no es firma electrónica verificada. |
| Desembolso QA | Registro interno que crea el crédito; no realiza una transferencia. |
| Pago QA | Movimiento interno sobre deuda exigible y recibo; no cobra dinero al cliente. |
| Anticipo QA | Aplicación a capital futuro después de cubrir devengos/cargos; recalcula el calendario según el contrato. |
| Liquidación QA | Cotización y registro exactos que cierran el crédito con saldo y cargos aplicables en cero. |
| Reverso de liquidación | Movimiento compensatorio que conserva el recibo, restaura el calendario y reabre el crédito; no devuelve dinero. |

> **Solo QA.** El paquete y la revisión de firma son documentos/evidencias de
> prueba sin validación contractual; el desembolso y los pagos son registros
> internos y no mueven dinero. `ORIGINACION_*_QA_HABILITADOS=true` abre esas
> funciones de QA, no las convierte en operaciones productivas. No uses un cliente
> real, una cuenta bancaria, una transferencia ni referencias reales. Si el sitio
> se llama “producción” pero actualmente solo aloja pruebas, comprueba que base,
> archivos, usuarios, correos e integraciones no atiendan operaciones reales.

### Dónde se prueba cada área

| Área disponible | Apartado de esta guía |
| --- | --- |
| Empresa, sucursales y equipos | 0.2–0.3 |
| Usuarios, roles, permisos, perfil, 2FA y sesiones | 0.4–0.5 y 0.7 |
| Portada, acceso, tablero y Mi trabajo | 0.5 y 9.1 |
| Productos, versiones, comisiones, fiscalidad, simulador y política | 1 |
| Plantillas, editor, vista previa, documentos generados y descargas | 2 y 6 |
| Clientes, expediente, referencias, vínculos, garantías y consentimiento | 2 |
| Solicitudes, responsables, devoluciones, rechazo y cancelación | 3 y 5 |
| SIC, histórico, evaluación y cumplimiento manual | 4 |
| Contrato y revisión de firma | 6 |
| Desembolso, cartera y calendario | 7 y 9.1 |
| Pagos, recibos, anticipos, liquidación y reversos | 8–8.3 |
| Actividad, filtros, CSV y navegación contextual | 9.2–9.3 |
| Observaciones, bloqueos y aceptación del ejercicio | 10 |

Accesos rápidos: [preparación](#0-preparar-el-entorno-y-repartir-el-trabajo),
[producto](#1-configurar-producto-y-versión-de-prueba),
[expediente](#2-preparar-plantilla-documental-y-cliente-sintético),
[solicitud](#3-crear-y-completar-la-solicitud),
[evaluación/SIC](#4-evaluar-y-revisar-cumplimiento),
[aprobación](#5-revisar-requisitos-y-aprobar),
[contrato/firma](#6-preparar-contrato-y-recibirrevisar-firma-qa),
[desembolso](#7-registrar-desembolso-ficticio-y-abrir-el-crédito),
[pagos y cierre](#8-registrar-pago-qa-recibo-y-reverso),
[ejemplo numérico](#83-caso-numérico-completo-para-la-primera-corrida),
[rutina diaria](#91-rutina-de-apertura-atención-y-cierre-operativo-qa) y
[aceptación](#101-lista-de-aceptación-del-recorrido).

## 0. Preparar el entorno y repartir el trabajo

### 0.1 Elegir el escenario operativo

| Escenario | Configuración del ejercicio | Quién toma las decisiones |
| --- | --- | --- |
| Prestamista que opera solo | Una sucursal, un equipo y política de aprobación **Individual: misma persona, decisión auditada**. | La misma persona puede capturar, dictaminar y aprobar si tiene todos los permisos. Debe completar evaluación y PLD; la modalidad individual no omite requisitos. |
| Financiera o SOFOM pequeña con equipo | Equipos y sucursales necesarios; política **Dual: quien capturó no aprueba** para ensayar separación de funciones. | Otra persona con cuenta propia aprueba. Puede agrupar otras responsabilidades según la organización; recibir/revisar firma y registrar/revertir pagos tienen permisos separados. |

El tamaño o denominación de la institución no selecciona automáticamente una
modalidad. La define una política versionada. En dual, ni el superadmin puede
aprobar una revisión que capturó, modificó como borrador o envió. No crees una
segunda identidad de la misma persona para simular la separación organizativa.

Para una primera corrida individual usa evaluación manual permitida sin SIC.
Repite en otro caso con SIC requerido y, si hay equipo, modalidad dual. Así puedes
aislar un problema de proveedor o de permisos sin cambiar la política de un caso
ya evaluado.

### 0.2 Configurar a la institución

Como administrador autorizado, abre **Empresa / Configuración de empresa**:

1. Elige persona física para representar al prestamista individual o moral para
   representar a la sociedad. En QA utiliza datos sintéticos con formatos válidos.
2. Completa razón social/nombre, RFC, régimen compatible, contacto y domicilio.
   Confirma el código postal con Enter o seleccionándolo y elige la colonia;
   verifica municipio, estado y país. La validación local de RFC no consulta el SAT.
3. Selecciona México, MXN y la zona horaria que usará la institución. Para la
   corrida de ejemplo usa `America/Mexico_City`; comprueba la fecha empresarial.
4. Revisa los parámetros operativos y guarda la configuración completa. El estado
   **Activa** de Empresa no aprueba solicitudes ni abre las banderas financieras.
5. Verifica que nombre y datos correctos llegan a las variables documentales que
   uses. La empresa se configura una vez por instalación; un equipo no representa
   una segunda financiera con otra base de datos o credenciales independientes.

La hora de corte y los valores generales guardados no ejecutan un cierre contable
ni reemplazan la política de mora o la fiscalidad versionada de cada producto.

En esta entrega, el adaptador SIC QA toma host, API key y sandbox de las variables
`CIRCULO_CREDITO_*` a través de `config/sic.php`. Guardar una clave en la sección
Integraciones de Empresa no sustituye esa configuración técnica. Si SIC ya
funciona, no recaptures secretos: confirma con el administrador qué configuración
está efectiva sin imprimirla ni incluirla en capturas.

### 0.3 Organizar sucursales y equipos

1. En **Sucursales**, crea o comprueba la sucursal de prueba activa, domicilio,
   clave y prefijos/consecutivos de sus folios. Conserva identificadores únicos.
2. En **Equipos**, prepara el grupo de trabajo. Una persona puede usar un solo
   equipo; una institución puede separar áreas según su organización.
3. Asigna sucursal principal y sucursales autorizadas a cada operador. La principal
   es su contexto inicial; selecciona la sucursal responsable antes de operar.
4. Cambia de equipo y de sucursal por separado. El equipo determina roles/permisos;
   la sucursal determina el contexto operativo. Cambiar uno no cambia el otro.

Con una cuenta operativa, prueba una sucursal no asignada y comprueba la denegación.
Si necesitas transferir un cliente, utiliza la acción explícita con `transfer clientes`
entre sucursales permitidas. No cambies IDs ni traslades por separado su historial
crediticio; los contratos y créditos conservan su contexto histórico.

Para desactivar una sucursal con usuarios activos o clientes, reasigna primero
sus dependencias. Un equipo no puede desactivarse mientras sea el equipo actual
de algún usuario. No elimines registros para hacer desaparecer un bloqueo.

### 0.4 Dar de alta usuarios y asignar roles

1. Selecciona el equipo correcto y abre **Roles y permisos**. Crea o elige el rol,
   selecciona sus permisos agrupados por módulo y pulsa **Actualizar** cuando haya
   cambios. Revisa **Ver miembros** para comprobar quién lo utiliza.
2. En **Usuarios**, crea al integrante con correo QA controlado, al menos un
   equipo con sus roles, sucursal principal y sucursales permitidas.
3. La cuenta queda pendiente. El invitado abre el enlace recibido y establece su
   contraseña; luego puede iniciar sesión como cuenta activa. Si falla el envío,
   revisa el correo de QA y usa reenviar invitación, sin duplicar la cuenta.
4. Entra con esa cuenta y comprueba menú y acciones. El nombre del rol —por ejemplo,
   Gerente— no concede permisos por sí mismo. Un rol de otro equipo o un rol vacío
   tampoco equivale al rol que acabas de configurar.
5. Tras añadir permisos a una versión nueva, revisa los roles ya existentes de
   cada equipo. Las migraciones crean permisos; no los conceden automáticamente.

El **superadmin global** ya dispone del acceso funcional completo; no necesita
recibir cada permiso. Un rol llamado “Super Admin” no convierte una cuenta en
superadmin global. Para comprobar las denegaciones usa cuentas operativas comunes.
El acceso global conserva las reglas de negocio: modalidad dual, requisitos,
consentimiento, fechas, contexto exigido por SIC/crédito y banderas QA.

Una persona que opere sola puede reunir funciones en su rol operativo. Para
administrar necesitará además los permisos del área correspondiente; `access admin`
permite entrar al panel, no reemplaza permisos como `read roles`/`update roles`,
`create users`/`update users` o `update configuracion-empresa`.

Si alguien deja de operar, desactiva su usuario; se conserva su historia y se
revocan sesiones/tokens. No uses la cuenta del cliente como cuenta de operador.

### 0.5 Asegurar el acceso y comprobar el perfil

1. Desde la portada abre **Iniciar sesión**. Usa la cuenta QA activada; no existe
   autorregistro público. El cliente del expediente no inicia sesión en este flujo.
   Si olvidaste la contraseña, usa **¿Olvidaste tu contraseña?** y comprueba el
   enlace en el correo QA controlado; no crees otra cuenta para recuperar acceso.
2. En **Editar perfil**, revisa datos, contraseña y sesiones. Prueba **Cerrar otras
   sesiones del navegador** con la contraseña, conservando tu sesión de trabajo.
3. En **Autenticación de dos factores**, habilita y confirma contraseña cuando se
   solicite. Escanea el QR con tu autenticador y confirma el código OTP; mostrar
   el QR por sí solo no completa la activación.
4. Guarda códigos de recuperación en un lugar privado. Cierra sesión y vuelve a
   entrar con OTP; en una prueba separada verifica acceso con un código de
   recuperación y conserva los restantes o regenera el conjunto.
5. No adjuntes QR, clave, OTP, recuperación ni contraseña al reporte de hallazgos.

2FA está disponible y aprobado como flujo. Esta entrega no obliga a activarlo a
todas las cuentas por una política central; registra qué cuentas lo probaron.
La configuración de perfil y contraseña debe probarla cada titular autorizado.

### 0.6 Comprobar que realmente es QA

Antes de habilitar acciones o capturar información:

- Confirma con quien administra la instalación qué base de datos, almacenamiento
  privado, colas y correo utiliza. Deben pertenecer al entorno de prueba.
- Usa datos sintéticos y una sucursal de pruebas. No reutilices personas reales ni
  archivos de identificación de clientes reales.
- Usa una cuenta ficticia y referencias claramente identificadas, por ejemplo
  `QA-2026-CR-001`; nunca escribas una referencia bancaria real.
- Los comprobantes de firma/desembolso/pago deben ser archivos o referencias
  sintéticos. No ejecutes transferencias.
- Para SIC, usa exclusivamente las rutas sandbox CDC con API key QA y personas
  sintéticas. No habilites credenciales ni consultas productivas. Si la política
  permite evaluación manual sin SIC, puedes recorrer ese escenario sin consulta.
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
| `ORIGINACION_ANTICIPOS_QA_HABILITADOS=true` | Anticipos y liquidación de créditos QA | Requiere también pagos QA habilitados y reglas contractuales compatibles. |
| `SIC_QA_HABILITADO=true` y `CIRCULO_CREDITO_SANDBOX=true` | FICO v2, Fintech y RC + FICO en simulación | Requiere API key QA y host `https://services.circulodecredito.com.mx`; no habilita transporte productivo. |

Para SIC, mantener cola y scheduler activos; reiniciar workers después de cambiar
configuración. Si PHP necesita un archivo de autoridades confiables, configurar
`SIC_CA_BUNDLE` sin desactivar TLS. Antes de actualizar una instalación existente,
respaldar base/archivos privados y aplicar migraciones aditivas; no reseedearla.

No pongas estas banderas en una instalación que atienda operaciones reales para
completar la guía. Si la VPS hoy contiene solamente datos y operaciones sintéticos,
puedes seguir tras comprobar también correo, colas e integraciones. Si comparte
alguno de esos recursos con operaciones reales, aísla la prueba antes de continuar.

Las banderas no otorgan permisos. Un administrador debe asignar permisos mínimos y
contextos de sucursal a cada cuenta. El seeder crea permisos, pero no los asigna a
roles personalizados. No compartas contraseñas entre participantes.

### 0.7 Repartir permisos por responsabilidad

Usa cuentas distintas para probar tanto el flujo positivo como las denegaciones:

| Participante | Trabajo en esta prueba | Permisos típicos a revisar |
| --- | --- | --- |
| Administrador de organización | Configurar empresa, sucursales, usuarios y roles | `access admin`, lectura y acciones autorizadas de `configuracion-empresa`, `sucursales`, `users`, `roles` y `teams`. |
| Administrador de producto | Crear/configurar versión, política y fiscalidad de prueba | `read`, `create`, `update`, `version`, `activate`, `simulate` y `manage origination productos-crediticios`; `manage commissions productos-crediticios` para administrar el catálogo de comisiones. |
| Gestor documental | Preparar plantilla y generar/descargar documentos QA | Lectura, creación, actualización, versionado y activación de `plantillas-documentos`; `read documentos`, `generate documentos` y `download documentos`. |
| Capturista/asesor | Completar expediente, crear y enviar solicitud | `read clientes`, `create clientes` si da de alta, `update clientes`; `read solicitudes`, `create solicitudes`, `update solicitudes` y `assign solicitudes` según su tarea. |
| Analista | Registrar dictamen de evaluación manual y capacidad de pago | `read evaluacion-solicitudes` y `create evaluacion-solicitudes`, además de lectura de cliente/solicitud. |
| Operador SIC | Preparar consulta sandbox y revisar respuesta/histórico | `create circulo-credito`, `read historial-crediticio`, `read clientes` y `update clientes`; sucursal responsable del cliente. |
| Revisor SIC | Conciliar un intento incierto tras revisión con el proveedor | `reconcile circulo-credito`, además de los permisos del operador SIC. |
| Cumplimiento | Documentar revisión humana y nivel de riesgo | `read cumplimiento` y `create cumplimiento`, además de lectura de cliente/solicitud; notas PLD reservadas. |
| Aprobador | Revisar requisitos y confirmar resolución | `approve solicitudes`; en modalidad dual, distinto de quien capturó o envió. |
| Revisor de solicitudes | Devolver o rechazar; cancelar cuando proceda | `review solicitudes` para revisión; `cancel solicitudes` para cancelación, además de lectura del caso. |
| Formalización | Preparar paquete y recibir copia de firma | `prepare package solicitudes`, `receive signature solicitudes`, `read documentos`, `generate documentos` y lectura de solicitud/cliente. |
| Revisor de firma | Comparar original/copia y aceptar o devolver | `review signature solicitudes`; usa otra cuenta para probar permisos. La identidad distinta del receptor no es una restricción impuesta por el código. |
| Operador de desembolso | Registrar el desembolso ficticio | `disburse creditos`, además de los accesos requeridos a solicitud/documentos. |
| Cajero/operador de pagos | Previsualizar y registrar pago QA | `pay creditos`; acceso al crédito y sucursal correcta. |
| Operador de anticipos/cierre | Cotizar y registrar anticipo o liquidación QA | `prepay creditos` para anticipo y `settle creditos` para liquidación; se conceden por separado. |
| Revisor de correcciones | Revertir el último pago QA con motivo | `reverse payments creditos`, separado del permiso de pago. |
| Consulta de cartera | Revisar crédito, calendario, movimientos y recibos | `read creditos`, `read solicitudes` y `read clientes`; no necesita `pay creditos` para leer un recibo. |
| Consulta de actividad | Filtrar eventos administrativos y exportar CSV | `read activity-log`; limitar la distribución de exportaciones a sus destinatarios autorizados. |
| Cliente de prueba | Persona sintética del expediente; no es una cuenta de usuario | No presupongas que existe un portal de autoservicio para el cliente. |

Las cuentas `consulta.clientes@example.test`, `editor.expedientes@example.test` y
`sin.acceso.clientes@example.test` están descritas en la [referencia de seeders](../reference/seeders.md)
y en el [guion documental](qa-backlog-03-5-documentos-y-plantillas.md). Son cuentas
de datos demo, no garantizadas en la VPS. No ejecutes seeders de desarrollo en la
instalación persistente para crearlas; pide al administrador cuentas QA dedicadas.

Comprueba con una cuenta operativa que selecciona la sucursal responsable antes de
escribir. Un lector de clientes puede consultar otras sucursales y, aun así, no
editar allí. El superadmin tiene excepciones globales en las policies de clientes;
los servicios de solicitudes, crédito y SIC comprueban su contexto operativo
además del acceso funcional. No uses la cuenta global para probar límites de un rol.
Para consultar un crédito se necesitan además `read creditos`, `read solicitudes`
y `read clientes`. Quien descargue el PDF contractual necesita `download documentos`;
quien registre desembolso necesita lectura documental. Los permisos de pago y
reverso, anticipo y liquidación se conceden por separado, pero el código no exige
operadores diferentes.

## 1. Configurar producto y versión de prueba

Con el administrador de producto, crea un producto identificable como QA o usa
**Duplicar versión** sobre uno de prueba para crear otro borrador. No alteres una
versión ya usada por una solicitud o crédito: las versiones y evidencias
formalizadas quedan congeladas.

Si falta el concepto de apertura, abre **Catálogo de comisiones**, registra una
clave/nombre QA y agrégalo antes de configurar la versión. El catálogo identifica
el concepto; su importe, modalidad y fiscalidad se definen en la versión. No marques
origen RECO o revisión documental si no se comprobó. Retirar un concepto lo excluye
de configuraciones nuevas, sin alterar versiones históricas.

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
  revisión manual permitida. Usa la modalidad elegida en 0.1: individual para la
  primera corrida de una sola persona; dual para comprobar separación con otra
  persona aprobadora. Si exige SIC,
  selecciona los servicios admitidos y la regla para resultados sin score:
  **bloquear** o **evaluación manual documentada**. La consulta sandbox debe
  corresponder a la revisión vigente antes de aprobar. Para el escenario sin SIC,
  configura la alternativa manual permitida por el producto.
- Para completar P8, activa **Permitir prepago parcial** y **Permitir liquidación
  anticipada**. Define **Aplicar prepago para** reducir plazo o reducir pago y el
  mínimo de prepago. El mínimo aplica al capital anticipado después de cubrir
  devengos y cargos, no al total recibido. Una segunda corrida con otro producto
  permite comparar ambas modalidades sin editar el contrato del primer crédito.
- Para cada concepto fiscal por separado —interés ordinario, moratorio y cada
  comisión— define en modo de prueba si está gravado, exento, no causa o sigue sin
  definir; si está gravado, define tasa y base configuradas para QA. No asumas 16 %,
  exención ni tasa cero como regla universal. El capital no se trata como base de
  impuesto automáticamente.
- Comisiones: nombre, importe, modalidad (financiada, descontada o pago separado),
  obligatoriedad y tratamiento fiscal de cada una. Omite comisiones opcionales en
  la primera corrida para reducir variables; prueba una segunda corrida si quieres
  cubrirlas. Para cargos de evento/liquidación, define el evento explícito
  (anticipo parcial, liquidación anticipada u ordinaria) y, si son porcentuales,
  su base: monto original solicitado, capital anticipado o capital insoluto antes
  de operar. Un contrato histórico con cargos ambiguos puede bloquear P8; no
  corrijas su snapshot para continuar: prepara otro caso con reglas explícitas.

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

### 1.1 Caso de referencia para completar el ciclo el mismo día

Estos valores son **sintéticos**, elegidos para cotejar la operación; no representan
una oferta, tasa recomendada ni criterio fiscal para un prestamista o SOFOM.
Puedes usar otros valores, anotándolos antes de empezar.

| Campo | Valor para la primera corrida |
| --- | --- |
| Producto | `QA-CICLO-INDIVIDUAL`; versión nueva; rango de monto de $5,000 a $20,000 MXN. |
| Solicitud | $10,000 MXN; capital fijo; 12 pagos mensuales, dentro de los plazos permitidos por el producto. |
| Interés | Ordinario anual 36 % y moratorio anual 72 %, exclusivamente para el ejercicio. |
| Gracia y mora | 0 días, alternativa A/efectiva, ambos intereses. |
| Comisión inicial | Una apertura fija de $500, obligatoria, al inicio y en **pago separado**. |
| Otras comisiones | Ninguna periódica, financiada, descontada, opcional o de evento en esta primera corrida. |
| Fiscalidad | Uso **Prueba / QA**; ordinario, moratorio y apertura gravados con tasa de ejemplo 16 % sobre importe íntegro de cada concepto. |
| Anticipo | Permitir parcial; mínimo de capital anticipado $500; reducir plazo. Permitir liquidación anticipada. |
| Política | Individual, evaluación manual permitida, límite $20,000, vigencia 7 días; documentos y criterio de capacidad documentados. |
| Fecha | El día real de la prueba en la zona de la institución; completar hasta desembolso sin cambiar de día. |

Activa producto y configura su política antes de enviar la solicitud. En el
simulador, marca **Incluir impuestos proyectados** para comparar la proyección
fiscal. Sin marcarla, la tabla es anterior a impuestos; no significa exención.
El simulador no cotiza mora ni cargos futuros de eventos. Registra su CAT
informativo cuando aplique; no lo confundas con la tasa ordinaria ni lo uses como
validación de una oferta institucional.

El monto solicitado y el capital inicial serán $10,000 en este caso; la apertura
se paga aparte, por lo que no reduce el efectivo ni se financia. El cargo inicial
exigible será $580 ($500 de concepto + $80 de impuesto de ejemplo).

Para el caso en equipo, crea otra solicitud/producto identificable con política
dual y SIC requerido; conserva los valores financieros si deseas comparar.
Para **reducir pago**, crea una versión/caso separado desde el principio.

### 1.2 Versiones, activación y cambios posteriores

- Guarda y vuelve a abrir el borrador para comprobar los valores. Activar lo deja
  disponible para originar; programar activación para otra fecha no lo habilita hoy.
- Si la versión ya está activa o fue usada, crea una versión nueva para cambiar
  las condiciones; no esperes que editar el catálogo modifique contratos previos.
- Retirar requiere `retire productos-crediticios` y excluye nuevas originaciones;
  no liquida ni borra créditos existentes. Prueba esta acción con otro caso, sin
  retirar la versión que todavía necesita tu solicitud en evaluación.
- Si cambias la política mientras una solicitud está en revisión, devuelve y
  reenvía para capturar la política actual y renovar sus evidencias.

## 2. Preparar plantilla documental y cliente sintético

### Plantilla

En **Documentos y plantillas**, crea/activa una plantilla de Contrato marcada como
prueba, con identificadores visibles como `QA — SIN VALIDEZ CONTRACTUAL`. No copies
un contrato de una institución para aparentar aprobación. Revisa que el PDF generado
muestre la marca QA en todas sus páginas y que una modificación posterior de la
plantilla no reescriba documentos históricos.

Recorrido mínimo si todavía no tienes una plantilla QA:

1. Pulsa **Nueva plantilla**; captura nombre, clave única sin espacios, tipo
   **Contrato**, resumen de cambios y descripción.
2. Completa encabezado, contenido y pie. Usa **Variables permitidas** para insertar
   datos de Empresa/Cliente/Documento admitidos para ese tipo; evita inventar
   variables o pegar código. Incluye texto sintético y una marca de prueba visible.
3. Guarda el borrador y abre **Previsualizar**. Comprueba variables resueltas,
   encabezado/pie, saltos de página y formato con datos sintéticos. Esa vista previa
   todavía no es el contrato generado de una solicitud.
4. Activa la versión confirmando su inmutabilidad. Para cambios posteriores,
   duplícala y trabaja en un nuevo borrador; no retires la que necesita este caso.

Prepara además un PDF sintético válido con el texto “PRUEBA QA — SIN DATOS REALES”
para las cargas del expediente. Para firma necesitarás otra copia PDF sintética,
con contenido distinto del original generado; renombrar el mismo archivo no basta.

La activación de una plantilla solo la hace seleccionable: **no aprueba su contenido
jurídicamente**. Ve el [guion del paquete contractual QA](qa-paquete-contractual.md)
para editor, variables y visor.

### Cliente y expediente

Usa un cliente persona física sintético dentro de la sucursal QA. Captura solo lo
necesario para probar el flujo. Completa secciones de identidad/datos fiscales,
domicilio, perfil económico, referencias y documentos que la política requiera.
Carga archivos sintéticos claramente marcados y valida cada uno como el rol autorizado.
Si vas a consultar SIC, completa nombre, nacimiento y domicilio mexicano, incluidos
estado, municipio, colonia y código postal. Registra consentimiento vigente con
evidencia privada sintética; el archivo no es una autorización legal de una persona real.

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

### Referencias, vínculos y garantías

En el mismo expediente, prueba las secciones que utilice tu operación:

1. Captura una referencia sintética y confirma su consulta posterior.
2. Registra un vínculo con otro cliente de prueba. No se admite vincular al
   cliente consigo mismo ni repetir el mismo vínculo.
3. Registra una garantía con tipo, descripción, moneda y valor estimado cuando
   corresponda. El propietario puede ser el titular o un cliente previamente
   relacionado; registra primero ese vínculo si la garantía pertenece al tercero.
4. Si preparas un documento de garantía o consentimiento con una plantilla,
   comprueba la entidad relacionada y conserva el documento generado. Generar el
   PDF de consentimiento no registra por sí solo una autorización SIC vigente:
   debe recibirse/registrarse su evidencia en Consentimientos SIC.

Registrar una referencia, vínculo o garantía no crea una obligación solidaria,
avalúo certificado ni cobranza ejecutable. Tampoco amplía automáticamente el monto
autorizable. La evaluación debe documentar qué información revisó.

### Documentos y consentimiento

Para el ejemplo, elige como requeridos INE, comprobante de domicilio, constancia
fiscal y comprobante de ingresos; sube únicamente archivos sintéticos válidos para
el formulario. La política puede exigir un subconjunto u otro tipo admitido;
registra esa selección y valida los requeridos antes de dictaminar.

Comprueba versión, fecha de vigencia, revisión y archivo privado de cada documento.
En consentimiento SIC verifica medio, fecha de otorgamiento, vencimiento si aplica
y evidencia recibida. En una prueba separada revócalo y comprueba que la evidencia
ya no habilita consultas/aprobaciones que requieran ese consentimiento.

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
Cuando la política exija SIC, completa primero la consulta sandbox de esta revisión.

### SIC sandbox e histórico

Si la política permite el escenario sin SIC, continúa con la evaluación manual.
Para el escenario con SIC:

1. Como operador SIC, abre **Consultas SIC → nueva consulta** y selecciona el
   cliente sintético, servicio admitido, consentimiento vigente y revisión actual
   de la solicitud. Confirma sandbox y prepara la consulta una sola vez.
2. Espera el resultado; la página actualiza los estados preparada/en proceso.
   Verifica servicio, marca QA, fecha, folio, modelo, score y razones. Un score no
   sustituye los dictámenes humanos ni aprueba por sí mismo el crédito.
3. En RC + FICO, revisa secciones y gráficas presentes: saldos por moneda, cobertura
   de datos, cuentas con vencido y consultas por mes. Abre **Ver datos de la gráfica**
   y el detalle de cuentas/consultas. FICO y Fintech pueden devolver solo scores y
   razones; no deben mostrar cuentas inventadas ni ceros para datos ausentes.
4. Abre el historial y comprueba que el resultado puede consultarse nuevamente.
   Un histórico sin procedencia verificada o un resultado marcado demostrativo no
   satisface el requisito de aprobación. Si solo se guardó un score en el registro
   antiguo, no esperes recuperar secciones que nunca se almacenaron.
5. Si el resultado queda **incierto**, no reenvíes. El revisor autorizado revisa el
   intento con el proveedor y registra referencia, motivo y confirmación para
   conciliar sin acreditar un resultado; después puede prepararse una consulta nueva.
6. En requisitos de aprobación, verifica que se reconoce la evidencia de la revisión
   actual. Otra API no admitida, revisión anterior, identidad cambiada o consentimiento
   revocado deben bloquearla. Sin score utilizable se aplica la política explícita;
   E2 bloquea incluso con alternativa manual. QA no añade una caducidad inventada.

Las consultas de conectividad con casos oficiales y la migración cifrada del
histórico se detallan en [SIC sandbox](qa-sic.md); el comando de conectividad no
crea evidencia para aprobar esta solicitud.

### Evaluación

Con el analista asignado, revisa solicitud, expediente, ingreso/deuda declarados,
capacidad y condiciones del producto. Registra el dictamen manual con resultado,
fundamento, fuente/metodología y datos mínimos sintéticos. Confirma que el resumen
muestre el último dictamen y que el formulario anterior no siga abierto como si
pudiera editarse: un dictamen nuevo crea evidencia nueva.

Para el recorrido positivo elige **Favorable**; completa fundamento, fuentes y
metodología explicando el criterio de capacidad configurado. No existe un umbral
automático que reemplace ese análisis humano.

Prueba un escenario separado que se devuelva para corregir datos y reenviar. El
dictamen de la revisión previa debe quedar histórico y no satisfacer automáticamente
la revisión nueva. No cargues reportes de crédito reales ni notas innecesarias.

### Cumplimiento/PLD

Con el responsable de cumplimiento, revisa las fuentes y evidencias que la instalación
realmente tenga configuradas. Registra nivel de riesgo, resultado, fundamento y
seguimiento manual. Si no existe proveedor/lista conectado, no interpretes la
ausencia de coincidencias como una búsqueda realizada: documenta que la comprobación
fue manual y limitada a las fuentes revisadas.

Para continuar al crédito, el resultado debe ser **Sin observaciones** con nivel
de riesgo determinado y fundamentos/fuentes/metodología completos. “Pendiente”,
“Bloqueada” o riesgo “Sin determinar” no habilitan la aprobación.

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

**Para operación individual:** registra ambos dictámenes con tu cuenta autorizada,
como actuaciones distintas. La opción de evaluación manual sin SIC no elimina
PLD ni la evaluación favorable. Para el caso en equipo, distribúyelos entre sus
responsables y deja el aprobador dual fuera de la captura y el envío.

Completa primero datos y documentos. Validar un documento, cambiar un domicilio
o sustituir evidencia después de dictaminar puede dejar obsoletos ambos dictámenes.
Si solo cambió el expediente, registra los dictámenes nuevos que se soliciten;
si cambió la identidad o las condiciones/política de la revisión, devuelve y
reenvía antes de volver a evaluar. Recargar la página no renueva la evidencia.

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

En el caso individual, aprobar con la misma cuenta es el resultado esperado si
tiene permisos y cumple los requisitos. En el caso dual, prueba que esa misma
actuación se rechaza incluso para el superadmin y utiliza al aprobador distinto.

En casos separados, comprueba las salidas alternativas:

| Acción | Resultado que debes comprobar |
| --- | --- |
| Devolver | Motivo operativo comprensible, responsable que corrige y nuevo envío/revisión sin borrar la anterior. |
| Rechazar | Resolución e historial conservados; no permite continuar a contrato/desembolso. |
| Cancelar antes del desembolso, donde esté permitido | Caso conservado y fuera del flujo activo; no equivale a eliminarlo ni a revertir un crédito desembolsado. |
| Cambiar condiciones o identidad | La revisión anterior no habilita una aprobación nueva: devuelve, corrige, reenvía y renueva evidencia/dictámenes. |

Si dos personas mantienen abierto el mismo formulario, una acción posterior
puede rechazar la versión obsoleta. Recarga y revisa el estado vigente antes de
actuar; no repitas confirmaciones para intentar superar el control de concurrencia.

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

Revisa también las reglas congeladas de anticipos: si admite parcialidad, mínimo
de capital anticipado, reducción de plazo/pago, liquidación anticipada y comisiones
por evento con su base e impuesto. Deben coincidir con la versión elegida.
Abre todas las páginas del PDF. Si la generación sigue pendiente o falla, revisa
la cola y la causa antes de reintentar; el registro de un trabajo no acredita que
exista un archivo completo. Cambiar la plantilla después no cambia ese paquete.

Para probar la separación de permisos, usa dos cuentas diferentes si cuentas con
un equipo. En operación individual puedes recibir y revisar con la misma cuenta
autorizada; el sistema no bloquea por identidad a quien tenga ambos permisos.
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

Para esta primera corrida utiliza un crédito QA recién desembolsado con política y
fiscalidad explícitas. Después puedes continuar sobre ese crédito activo y su
historia. Una cuota de capital con vencimiento futuro **no es exigible
hoy**: **Registrar pago QA** cubre deuda exigible. Para aplicar a capital futuro,
usa **Anticipos y liquidación**, siguiendo los apartados 8.1 y 8.2. Para comprobar
el pago ordinario el mismo día, crea desde el principio una
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
6. Con un lector autorizado de cartera, abrir el recibo debe estar permitido, pero
   registrar o revertir sin sus permisos debe denegarse. Una cuenta sin lectura
   del crédito tampoco puede abrir el recibo por URL. Cambiar de sucursal debe
   bloquear las operaciones de escritura; no se confunde con la consulta autorizada.
7. Con otra cuenta y `reverse payments creditos`, revertir únicamente el último
   pago permitido, indicando motivo. El registro original debe permanecer y quedar
   enlazado a movimientos compensatorios. Esto no devuelve dinero ni borra historia.

Este flujo aplica solo a datos ficticios; la [decisión técnica de P6](../explanation/adr-0020-pagos-manuales-qa.md)
documenta pagos ordinarios. P8 agrega [anticipos, liquidación y cierre QA](qa-anticipos-liquidacion.md);
P7 agrega [consultas SIC sandbox](qa-sic.md). Conciliación bancaria, CFDI y
operaciones monetarias reales siguen fuera de alcance.

### 8.1 Registrar anticipo y revisar el nuevo calendario

El crédito debe seguir activo y tener vencimientos futuros; las reglas P8 y
comisiones del contrato deben estar definidas desde el apartado 1.

1. Con `prepay creditos`, abre **Anticipos y liquidación → Anticipo parcial**.
   Captura fecha efectiva válida, referencia sintética única y **total recibido**,
   suficiente para cubrir devengos/cargos y el mínimo de capital anticipado.
   Selecciona comisiones opcionales solo si corresponden al evento.
2. Pulsa **Revisar efecto y distribución**. Comprueba que primero cubre vencidos,
   ordinario/moratorio devengados, impuestos y cargos aplicables; el remanente se
   aplica al capital futuro. No asumas que todo el recibo reduce capital.
3. Revisa capital antes/después, cuotas futuras, último vencimiento, intereses
   estimados, distribución y calendario. La gráfica compara las mismas fechas
   suponiendo pagos puntuales; su tabla alternativa permite revisar valores.
   Reducir pago conserva fechas/cantidad; reducir plazo conserva la base de cuota
   o capital periódico según el método, con ajuste final.
4. Cambia el importe y verifica que desaparece la previa. Recalcula, revisa y marca
   confirmación QA. Registrar una vez crea recibo y movimientos; reintentar no duplica.
5. En el crédito, verifica historial, capital y selector de calendarios. El firmado
   original permanece y la operación crea una versión posterior; no reescribe el contrato.

Comprueba rechazo de mínimo incumplido, fecha inválida, comisión incompatible y
falta de permiso. Para B + sustitución durante gracia, conserva el bloqueo documentado.
Si pruebas reverso de anticipo en un caso separado, solo el último admisible se
compensa y restaura el calendario con una nueva versión.

### 8.2 Liquidar y comprobar reapertura mediante reverso

1. Con `settle creditos`, abre **Anticipos y liquidación → Liquidación total**. Usa fecha
   válida y otra referencia sintética única. Deja **Total recibido** vacío para
   cotizar y pulsa **Revisar efecto y distribución**.
2. Revisa el total exacto: capital, devengos e impuestos a la fecha y comisiones
   aplicables. No incluye intereses futuros ni comisiones periódicas de cuotas
   eliminadas; los cargos ya exigibles permanecen. La previa debe mostrar cierre
   total, saldo cero y ausencia de cuotas futuras.
3. En una prueba de rechazo, introduce un total distinto de la cotización y pulsa
   **Revisar efecto y distribución**: debe explicar la discrepancia sin registrar
   saldo a favor ni recortar un excedente.
   Vuelve a cotizar dejando el importe vacío y revisa el total devuelto.
4. Confirma QA con ese importe exacto. Comprueba recibo, estado **Liquidado · QA**,
   historial y calendario conservado; no debe ofrecer registrar nuevos pagos.
5. Con `reverse payments creditos`, abre el recibo de la última liquidación y
   registra motivo de prueba. Comprueba compensación enlazada, recibo original
   intacto, crédito reabierto y calendario restaurado mediante otra versión.

La reapertura es parte de esta prueba y debe quedar registrada como tal. Si quieres
terminar con el crédito liquidado, cotiza nuevamente y registra otra liquidación
con nueva referencia; una referencia anterior no se reutiliza aunque se revierta.

### 8.3 Caso numérico completo para la primera corrida

Usa exactamente el producto de **1.1** y realiza todo en la misma fecha empresarial
del desembolso, antes del primer vencimiento. Todos los importes son MXN. La tasa
de impuesto del ejemplo es sintética; no prescribe el tratamiento de una institución.

| Paso | Captura/acción | Resultado esperado |
| --- | --- | --- |
| Desembolso | Capital de $10,000 y comisión inicial de $500, pago separado, impuesto configurado del 16%. | Capital pendiente $10,000; comisión exigible $580 ($500 + $80). El efectivo del desembolso no se reduce por esa comisión separada. |
| Pago ordinario | Registrar $290 con referencia nueva. | Recibo: $250 a comisión y $40 a su impuesto. Capital pendiente $10,000; comisión más impuesto pendientes $290. |
| Reverso de ese pago | Revertir el último recibo con motivo de prueba, **antes del anticipo**. | Se conserva el recibo original, se compensa y vuelve a haber $580 exigibles; capital $10,000. |
| Anticipo parcial | Total recibido $2,000; reducción de plazo; referencia nueva. | Cubre $580 de comisión/impuesto y anticipa **$1,420 de capital**. Capital pendiente **$8,580**; calendario con **11 cuotas futuras**, conservando el original. |
| Liquidación total | Dejar importe vacío, cotizar y confirmar el total exacto con otra referencia. | En estas condiciones y en el mismo día, cotización **$8,580**, sin intereses devengados ni comisiones adicionales; crédito liquidado, capital/saldo cero. |
| Reverso de liquidación | Revertir el último recibo indicando motivo. | Crédito reabierto, capital $8,580, calendario previo restaurado mediante otra versión; liquidación original y compensación conservadas. |
| Cierre final del ejercicio | Cotizar de nuevo y liquidar con referencia diferente. | Si sigue siendo el mismo día y no hay otros movimientos/cargos, total $8,580; vuelve a quedar liquidado. |

Los importes de distribución y las 11 cuotas se cotejaron con los motores de
simulación y anticipos de esta entrega. No introduzcas $8,580 a ciegas: si cambia
el día, la versión, los impuestos, las comisiones o la secuencia, utiliza la
cotización vigente y explica la diferencia. El mínimo de anticipo se compara con
los $1,420 aplicados a capital, no con los $2,000 del recibo.

Este caso comprueba el ciclo el mismo día. No acredita mora, vencimiento de primera
cuota, gracia ni movimientos de días posteriores: repítelos en casos separados
cuando corresponda su fecha real, sin alterar reloj, contrato ni base de datos.

## 9. Revisar cálculos, operación diaria y actividad

Invita al contador a revisar la **configuración de prueba y los cálculos**, no a
certificar que el producto ya está listo para venderse. Entrega una copia controlada
de los valores de la versión QA, una tabla con escenario y resultados, la simulación, el paquete
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
  Para anticipos/liquidación, cotejar evento y base comercial congelados.
- **Anticipos y liquidación:** comprobar mínimo sobre capital anticipado, cobertura
  de todos los devengos a la fecha, retiro de intereses futuros, total exacto de
  cierre y conservación del calendario original. Revisar ambas modalidades de
  prepago con casos separados y la reapertura mediante compensación.
- **Reversos y fechas:** comprobar ejemplos de pago parcial, fecha efectiva/captura,
  reverso compensatorio y límites de corrección. El sistema bloquea pago retroactivo
  si ya existen movimientos posteriores.
- **Documentos/contrato:** el contador puede revisar consistencia aritmética y
  presentación de importes. La aprobación jurídica del contenido es una revisión
  separada que corresponde a la institución; una plantilla activa no la acredita.
- **Alcance no implementado:** conciliación bancaria, CFDI, retenciones,
  acreditamientos/declaraciones y casos de compensación futura no deben darse
  por cubiertos por esta prueba. Anticipos/liquidación requieren el recorrido P8.

Pide al contador que marque cada punto como “revisado para el escenario”,
“requiere ajuste” o “fuera de alcance”; no es necesario que dé visto bueno a una
regla legal que no haya validado. La guía de [simulación fiscal QA](qa-simulacion-fiscal.md)
incluye preguntas ampliadas para una revisión profesional. **No existe una tasa
universal que esta aplicación deba asumir**: la configuración final corresponde a
cada institución con su asesor.

### 9.1 Rutina de apertura, atención y cierre operativo QA

Esta rutina organiza el trabajo; no es un cierre contable o de caja automatizado.

1. **Al iniciar:** verifica cuenta, equipo, sucursal y fecha empresarial. En el
   tablero revisa clientes, solicitudes, créditos y pendientes que tus permisos
   permiten ver. Un indicador oculto/no disponible no significa cero operaciones.
2. **Atender pendientes:** entra en **Mi trabajo** y Solicitudes, usa búsqueda y
   filtros disponibles y abre el caso antes de actuar. Revisa responsable, etapa,
   requisitos y última actuación; evita atender una captura desactualizada.
3. **Consultar cartera:** busca el crédito en su listado y abre el detalle. El
   capital inicial del listado no es el saldo actual de toda la cartera. Coteja
   capital pendiente, exigible, vencido, días y fecha de cálculo en cada crédito.
   La situación calculada al día no implica que se haya ejecutado un asiento de
   devengos; los movimientos registrados se consultan en su historial.
4. **Consultar calendarios:** alterna el original firmado y las versiones posteriores.
   Elegir una versión para verla no restaura deuda; una restauración operativa solo
   ocurre mediante el reverso admisible y queda registrada como otra versión.
5. **Al terminar:** coteja por crédito referencias, recibos, distribución, reversos
   y estado final. Anota pendientes y su responsable, incluidos intentos SIC
   inciertos. Un recibo y su reverso no son dos cobros ni una devolución bancaria;
   no sumes sus importes como efectivo neto recibido.

Si un crédito histórico aparece **Por revisar** o sin política/fiscalidad suficiente,
no interpretes esa situación como saldo cero ni fuerces su operación. Registra el
caso y usa un crédito nuevo configurado explícitamente para esta prueba.

Un último pago ordinario puede cerrar el crédito si ya cubre todo lo aplicable.
Cuando correspondan comisiones de cierre/liquidación, utiliza **Liquidación total**
para cotizarlas; no sustituyas esa operación con un pago ordinario forzado.

### 9.2 Revisar actividad y conservar evidencia

1. En cada solicitud/crédito, revisa su historia: revisión, decisión, contrato,
   desembolso, recibos y compensaciones deben permitir seguir el mismo caso.
2. Con `read activity-log`, abre **Actividad / Registro de actividad**. Utiliza
   búsqueda y filtros por usuario, evento, entidad y fechas para localizar las
   actuaciones disponibles. Coteja identificador, actor, fecha y contexto que
   registre cada evento; no esperes que todos tengan los mismos campos. La cuenta
   operativa consulta actividad de su equipo actual; el superadmin tiene lectura
   global. Cambiar equipo puede cambiar lo que aparece sin que se haya borrado.
3. Prueba **Exportar CSV** sobre el filtro elegido y compara algunas filas con
   pantalla. Conserva el archivo con acceso controlado: puede incluir correo,
   IP y propiedades del evento. No lo adjuntes completo a un ticket público.
4. Una cuenta sin ese permiso no debe obtener el registro administrativo por URL.
   Tener acceso a la historia de su crédito no concede lectura global de actividad.

La actividad disponible y las evidencias de estos flujos no constituyen una matriz
integral de auditoría. El backlog 02.5 sigue diferido; este CSV tampoco es un
reporte contable, de cartera ni regulatorio.

### 9.3 Comprobar navegación y uso en distintas pantallas

- Recorre el menú con cada rol. Verifica equipo/sucursal actuales y acceso a los
  módulos autorizados. Ocultar un enlace no sustituye la autorización de su URL.
- Si administras **Menú**, revisa sus entradas y permisos de administración de
  `menubar-items`. Cambiar el menú no concede permisos de negocio a un rol.
- En escritorio y móvil comprueba formularios, filtros, tablas, tarjetas, recibos,
  cotizaciones y descarga de PDF. Revisa que importes, fecha, estado y siguiente
  acción sigan siendo legibles; utiliza zoom y navegación con teclado.
- En SIC y anticipos, coteja las gráficas con sus tablas de datos. Ausencia de
  información no debe convertirse en cero; identifica monedas, unidades y fecha.
  Si utilizas tema oscuro, comprueba contraste y etiquetas en ambos temas.
- La portada muestra módulos y capturas ilustrativas, incluida SIC. No representa
  datos del cliente; para consultar un caso inicia sesión y abre su expediente.

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

### 10.1 Lista de aceptación del recorrido

Para cada fila registra **Aprobado**, **Falló**, **Pendiente** o **No aplica**, con
fecha, responsable e identificadores del caso. “No aplica” requiere explicar el
perfil elegido; una prueba todavía no ejecutada queda pendiente. No marques como
aprobado un flujo manual solo porque exista una prueba automatizada de desarrollo.

| Área | Evidencia mínima del ejercicio |
| --- | --- |
| Institución y contexto | Empresa guardada; equipo/sucursal correctos; cuenta operativa activada; rol y permisos comprobados. |
| Seguridad | Inicio/cierre de sesión, recuperación de acceso y prueba 2FA de las cuentas elegidas; no conservar secretos como evidencia. |
| Producto | Identificador y versión activa; política, comisiones, fiscalidad y anticipos explícitos; simulación cotejada. |
| Expediente | Cliente sintético, documentos requeridos validados; referencias/vínculos/garantías si se utilizaron. |
| Solicitud | Borrador, responsable, envío y revisión identificables; un caso separado devuelto/rechazado/cancelado según corresponda. |
| Evaluación y PLD | Dos dictámenes favorables vigentes sobre la revisión/expediente correctos; fundamento sintético y notas reservadas protegidas. |
| SIC, cuando se requiera | Consentimiento, API admitida, consulta y resultado vinculados; histórico consultable, gráficas/datos coherentes. Registrar qué APIs se probaron; una no acredita las tres. |
| Aprobación | Individual positiva o dual con otro aprobador; dual consigo mismo rechazada cuando se pruebe esa modalidad; bloqueo por requisito pendiente comprobado. |
| Formalización | PDF completo y marcado QA, copia distinta recibida y revisión manual; fecha y condiciones coincidentes. |
| Desembolso | Un crédito por solicitud, referencia sintética, calendario original y capital/efectivo correctos; sin transferencia real. |
| Pago y reverso | Vista previa, distribución, recibo y compensación enlazados; lectura de recibo permitida al lector y escritura denegada sin permiso. |
| Anticipo | Capital anticipado cotejado, nuevo calendario y original conservado; mínimo aplicado al capital. Registrar si se probó reducir plazo o pago. |
| Liquidación y reapertura | Cotización exacta, cierre, reverso admisible, reapertura y cierre final con referencia nueva. |
| Trabajo diario y actividad | Caso reencontrable por filtros; fechas/saldos bien interpretados; eventos disponibles y CSV revisados con acceso controlado. |
| Presentación | Formularios y mensajes en español; PDF, móvil y gráficas/tablas legibles; sin datos ausentes presentados como cero. |

Un recorrido individual aprobado no acredita modalidad dual, todas las
periodicidades/métodos, ambos efectos del anticipo ni mora/gracia. Registra esas
variantes en casos separados con la misma estructura. Conserva los pendientes
concretos antes de invitar a otros operadores a repetir el ejercicio.

### 10.2 Bloqueos frecuentes y siguiente acción

| Síntoma | Qué comprobar y cómo continuar |
| --- | --- |
| No aparece un módulo o devuelve acceso denegado | Equipo actual, rol de ese equipo, permisos concretos y sucursal; vuelve a cargar tras la corrección. No basta el nombre del rol ni editar el menú. |
| Función QA deshabilitada | Que sea el entorno aislado correcto y estén activas sus banderas/configuración efectiva; lo revisa quien administra la instalación. |
| Empresa tiene clave SIC pero no consulta | Configuración `CIRCULO_CREDITO_*`, sandbox, API key QA, cola/scheduler y TLS; la clave de Empresa no sustituye la del adaptador actual. Sigue `qa-sic.md`, sin imprimir secretos. |
| Consulta incierta o en proceso | Revisa el estado sin repetir la preparación. Un intento incierto requiere revisión/conciliación autorizada antes de una consulta nueva. |
| SIC no satisface aprobación | API admitida, revisión actual, identidad, consentimiento vigente, procedencia y política para resultado sin score. Un histórico/demostración no sustituye esa evidencia. |
| Dictamen o aprobación obsoletos | Completa primero expediente; renueva dictámenes o devuelve/reenvía si cambió identidad, condiciones o política. No se resuelve solo recargando. |
| Aprobador bloqueado en dual | Otra persona debe aprobar si el usuario capturó/modificó el borrador/envió; también se exige al superadmin. |
| No permite desembolsar hoy | Fecha empresarial igual a la firmada, vigencia de aprobación, paquete completo, copia y revisión aceptada. Si cambió el día previsto, rehace formalización mediante devolución y nueva revisión. |
| No hay capital exigible para pago ordinario | Si aún no vence, utiliza anticipo para capital futuro. El pago ordinario del ejemplo solo cubre la comisión inicial separada. |
| Anticipo menor al mínimo o liquidación con exceso | Revisa lo destinado a devengos/cargos antes del capital; vuelve a cotizar. La liquidación exige el total exacto, sin saldo a favor automático. |
| Crédito histórico “Por revisar” | Documenta qué política/fiscalidad falta; no edites su evidencia congelada. Usa un crédito QA nuevo para el recorrido soportado. |
| Registro cambió mientras confirmabas | Recarga, revisa estado/historial y calcula otra previa si procede; no repitas operaciones hasta confirmar si ya quedaron registradas. |
| Contrato pendiente/fallido | Revisar worker, archivo privado y causa del trabajo; reintentar tras corregirla. No cargar el original como copia firmada. |

Si persiste un fallo, entrega el identificador interno, acción, hora/zona,
resultado esperado/observado y mensaje en español, sin payloads SIC ni archivos
privados. No pruebes soluciones modificando directamente saldos o evidencias.

### 10.3 Qué acredita esta guía y qué falta para operar realmente

Completarla demuestra que los participantes pudieron recorrer **el crédito simple
QA configurado**. Permite ensayar responsabilidades y detectar ajustes antes de
incorporar una operación real. No habilita por sí misma estos pendientes:

| Tema | Situación que debe resolverse por separado |
| --- | --- |
| SIC productivo | Validar entrega del proveedor, credenciales/rutas, autorizaciones y políticas institucionales. El transporte actual de esta guía sigue en sandbox. |
| Dinero y administración financiera | Los registros QA no ejecutan cobros, transferencias, conciliación bancaria, contabilidad ni CFDI. Definir y validar la operación real correspondiente. |
| Contratación | Validar contenido y proceso jurídico institucional; recibir/revisar un PDF QA no acredita una firma electrónica ni un contrato válido. |
| Perfil SOFOM | El perfil institucional y su alcance regulatorio siguen pendientes. La institución y sus asesores deben determinar qué aplica y validar procesos/reportes; no se acredita cumplimiento por activar Empresa o PLD manual. |
| Auditoría y operación técnica | La matriz integral de auditoría del backlog 02.5 sigue diferida. Comprobar por instalación respaldos y recuperación, accesos, colas, monitoreo y responsables operativos. |

Para una SOFOM, conserva como referencias oficiales de revisión institucional
[CNBV: SOFOMES](https://www.gob.mx/cnbv/acciones-y-programas/sociedades-financieras-de-objeto-multiple-sofomes)
y [CONDUSEF: registros](https://www.condusef.gob.mx/index.php?p=registros).
La definición del siguiente alcance y su prioridad corresponde al
[Notion del repositorio](https://app.notion.com/p/1a261db7db7f800c809bf96e22e0d05a),
con los hallazgos y validaciones del ejercicio. Mantén estas brechas explícitas
al presentar el sistema a un prestamista o una financiera pequeña.

### Si necesitas consultar una regla

- [Configuración fiscal de producto](configurar-fiscalidad-productos.md)
- [Solicitudes, evaluación y aprobación](../reference/originacion-solicitudes.md)
- [Decisión técnica sobre pagos QA y límites](../explanation/adr-0020-pagos-manuales-qa.md)
- [SIC sandbox e histórico](qa-sic.md)
- [Anticipos y liquidación QA](qa-anticipos-liquidacion.md)
- [Decisión técnica de anticipos y liquidación](../explanation/adr-0023-anticipos-liquidacion-qa.md)
