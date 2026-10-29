# Folios de negocio

Los folios identifican registros; los IDs siguen siendo las claves técnicas y
las rutas no cambian. La numeración es continua y no se reinicia por año.

| Registro | Ámbito | Asignación | Ejemplo |
| --- | --- | --- | --- |
| Solicitud | Sucursal de origen | Confirmar creación | MTZ-SOL-000001 |
| Contrato | Sucursal de solicitud | Confirmar preparación del contrato y tabla | MTZ-CTO-000001 |
| Crédito | Sucursal de origen | Confirmar desembolso que crea el crédito | MTZ-CRE-000001 |
| Recibo | Sucursal del crédito | Confirmar pago, anticipo, liquidación o reverso | MTZ-REC-000001 |

El contrato se identifica desde `SolicitudPaquete`, antes de recibir la firma;
la formalización conserva su vínculo a ese contrato. Una nueva aprobación con
otro contrato recibe otro folio. Reintentar el mismo PDF conserva el existente.
Un reverso tiene recibo propio y mantiene el vínculo y número del original.

## Configuración y formato

En **Sucursales → Folios** se configura el prefijo y siguiente número con los
permisos existentes `read sucursales` / `update sucursales`. El prefijo admite
A–Z, números y guiones entre grupos: máximo 20 caracteres, 16 retirando guiones.
Los contadores tienen seis dígitos mínimos, crecen sin truncarse y no exceden
999999999999999 ni la capacidad de 25 caracteres normalizados. Al agotarla se
rechaza la emisión con mensaje en español; nunca se trunca o reutiliza.

El prefijo puede cambiar solo para futuras emisiones. Todos los anteriores
permanecen reservados para ese ámbito, incluyendo variantes que difieran solo
por guiones. El siguiente número no disminuye, ni siquiera al cambiar prefijo.
Adelantarlo deja números sin utilizar. Abrir formularios, simular y consultar
vistas previas no reserva números.

`PUT admin/sucursales/{sucursal}/folios` recibe `prefijo_folio`, `version` y
`siguientes` con `solicitud`, `contrato`, `credito`, `recibo`. Devuelve redirección
Inertia con éxito o errores de validación españoles. Cada emisión o cambio
incrementa la versión; guardar una captura desactualizada obliga a recargar.
Editar datos generales de sucursal ignora prefijos y contadores antiguos.

## Persistencia e invariantes

`FolioService` y `TieneFolio` asignan dentro de la transacción del registro, incluso
al crear directamente un modelo fuera de un servicio. `lockForUpdate` serializa
ámbito/secuencia; las restricciones únicas respaldan emisión y vinculación.
Un rollback revierte el número, la reserva y el registro. Las transacciones completas de creación de solicitud/contrato/crédito y registro/reverso de pago reintentan deadlocks hasta cinco veces; las creaciones directas restauran también el estado del modelo antes de reintentar. Cancelar, sustituir
contratos o reversar pagos conserva identificadores y sus evidencias.

- `folio_ambitos`: identidad estable, prefijo vigente y versión.
- `folio_secuencias`: tipo y siguiente número por ámbito; nuevos tipos no exigen
  añadir columnas de contador.
- `folio_prefijos`: reservas históricas y unicidad sin guiones.
- `folio_asignaciones`: folio, número, registro de negocio y sucursal originales;
  clave normalizada generada por la BD para comprobar unicidad.
- Registros de negocio: `folio`, `folio_asignado_en`, `folio_historico`.

La clave normalizada es una restricción técnica, no otro número de cuenta
editable. Se guarda el folio visible y el exportador futuro retirará únicamente
guiones. El [esquema público CDC DT-03-0001 v25](https://www.circulodecredito.com.mx/documents/10588964/10638423/DT-03-0001.pdf)
define `CuentaActual` alfanumérica hasta 25 caracteres. La versión contratada de
CDC y Buró debe validarse antes del reporteo productivo; no se implementa aquí.

Los contadores antiguos de `sucursales` se conservan como datos de importación,
se ocultan en props y dejan de gobernar emisiones. El reinicio QA clasifica y
conserva las cuatro tablas de numeración. Si recrea una sucursal con su clave
original, recupera su ámbito anterior; no reinicia contadores ni libera prefijos.
Cambiar la clave de una sucursal existente no cambia su ámbito. Una clave que
todavía identifica el ámbito de otra sucursal no puede crear otra numeración.

## Migración y documentos

La migración de esquema es aditiva; después una migración de datos comprueba
prefijos y completa registros por `created_at, id`, desde el siguiente configurado.
Los errores detienen la migración de datos y revierten sus asignaciones sin
deshacer el esquema ya aplicado. Corregir la configuración diagnosticada y reintentar.
El proceso omite folios existentes y puede reanudarse; no cambia timestamps,
snapshots cifrados, firmas, PDF ni hashes históricos.

`php artisan folios:completar --dry-run` valida y cuenta pendientes sin escribir.
`php artisan folios:completar` completa pendientes transaccionalmente. Ejecutar
con mantenimiento y workers detenidos tras confirmar entorno y respaldo. No
ejecutar un reinicio QA para preparar una migración. Los folios asignados impiden
retirar destructivamente el esquema; una reversión requiere copia consistente.

Las nuevas plantillas de contrato pueden usar `solicitud.folio` y `contrato.folio`;
las vistas previas HTML/PDF los ilustran con datos sintéticos sin reservar números.
el anexo también los imprime aunque la plantilla no los incluya. Se congelan
antes del renderizado. Crédito y recibo nacen después de preparar el contrato y
se muestran en cartera/comprobantes; no se insertan retrospectivamente en PDF
firmados. La interfaz señala la asignación posterior en registros históricos.

## Auditoría, ayuda y tipos posteriores

Los eventos de negocio existentes conservan su sujeto y permiten resolver el
folio desde la asignación. La actividad paginada añade `subject.folio` sin copiar
contenido de negocio. `sucursal.folios.updated` registra nombres de campos,
actor y sucursal seleccionada. `folio.historical_assigned` identifica al sistema,
recurso y sucursal original, con equipo desconocido explícito. No se infieren
preferencias actuales ni se registran valores fiscales/documentales.

`FieldHelp` conserva la interfaz de la ayuda financiera; el import antiguo de
Productos delega al común. Se utiliza en Empresa/Sucursales con ejemplos vivos.
Escape cierra primero la ayuda abierta y devuelve el foco al botón; con la ayuda
cerrada permite que el formulario contenedor procese su cierre habitual.
Los campos de empresa sin consumidor operativo quedan marcados y deshabilitados.
Cambios de ayuda, banner o vista previa no añaden eventos de negocio.

| Inventario posterior | Ámbito | Formato continuo reservado |
| --- | --- | --- |
| Disposición | Sucursal | PREFIJO-DIS-000001 |
| Cliente | Empresa | CLI-000001 |
| Consentimiento / consulta SIC | Empresa | AUT-000001 / SIC-000001 |
| Documento generado | Empresa | DOC-000001 |
| Dictamen / resolución | Empresa | DIC-000001 / RES-000001 |
| Garantía | Empresa | GAR-000001 |
| Envío SIC / alerta / caso / reporte PLD | Empresa | ENV-000001 / ALR-000001 / CAS-000001 / RPL-000001 |

Estos tipos posteriores no emiten ni crean secuencias en esta entrega.

## Verificación

`FoliosTest` cubre atomicidad, reintentos, límites, prefijos, versión, permisos,
auditoría y más de un lote histórico. `tests/Concurrency/folios.php` usa ocho
procesos independientes, fuerza además el cruce de bloqueos actor/numeración entre un servicio y una inserción de modelo, y exige `APP_ENV=testing`, driver MySQL/MariaDB y base
`kronik_test`. CI lo ejecuta después de Pest. SQLite local valida comportamiento;
no acredita bloqueos de filas. Véase [ADR 0030](../explanation/adr-0030-folios-negocio.md).
Las conexiones MySQL/MariaDB reconocen el conflicto snapshot `HY000/1020` como
concurrencia para reintentar la transacción exterior con el límite configurado;
no intentan recuperar un savepoint eliminado por el motor. `MariaDbConcurrencyTest`
simula el aborto total y cubre reversión, reintentos agotados y otros errores SQL.
El mismo caso aislado comprueba que la creación directa conserva las fechas
explícitas aportadas por el llamador, fuera de la transacción de Pest.
