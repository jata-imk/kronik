# Tablero financiero

Contrato de lectura de cartera de Kronik. Implementación local en
`feat/tablero-proyecciones`; integración, despliegue y QA compartido se registran
en la [Agent Note](../agent-notes/tablero-propuesta-visual.md).

## Responsabilidades y fuentes

Originación publica solicitudes por etapa actual. El core conserva condiciones,
cronogramas, pagos y movimientos; `LecturaCreditoService` selecciona y verifica
esa evidencia. `ProyectorLecturaCredito` traduce resultados del motor a obligaciones
de lectura; no introduce otra fórmula de interés. `ProyeccionCreditoService` es
el único escritor de las dos tablas derivadas. `ConsultaCartera` y `TableroService`
agregan mediante SQL, sin descifrar snapshots ni ejecutar el motor al abrir el tablero.

Las operaciones y previas siguen usando evidencia original. La lectura individual
y las proyecciones comparten su selección, huellas y versión de cronograma.
No hay conciliación bancaria, utilidad neta, contabilidad general ni reporteo SIC.

El tablero pertenece a la consulta transversal de informes y analítica. Los
reportes operativos detallados y sus exportaciones son una entrega posterior
que reutilizará este diccionario. Tesorería requiere cuentas, saldos, gastos,
transferencias y conciliación; Contabilidad requiere catálogo, asientos, libros
y estados financieros. Cobros/desembolsos del core y programación contractual
no bastan para representar caja disponible ni un estado de flujos de efectivo.

## Fechas y correcciones

- `corte`: hoy en la zona de `FechaEmpresa` o el último día de un mes cerrado.
  Incluye operaciones con fecha efectiva menor o igual al corte. En un mismo día
  prevalece el último ID conservado, incluido un reverso.
- `desde`/`hasta`: fechas inclusivas independientes para movimientos, hasta hoy.
  Predeterminado: inicio del mes actual a hoy. Se validan formato y orden en español.
- Historia corregida: una captura posterior con fecha efectiva anterior y su
  reverso admisible afectan los cortes correspondientes. No se reconstruye lo
  conocido en una fecha de captura anterior.
- Los cierres de esta lectura son derivados reconstruibles y corregibles. No
  sustituyen cortes regulatorios inmutables ni cierres contables de otras tareas.
- Vencido: obligación exigible pendiente con fecha contractual estrictamente
  anterior al corte. Lo exigible hoy todavía no está vencido. La gracia regula
  el devengo moratorio; no desplaza el vencimiento mostrado.
- Cobros: pagos, anticipos y liquidaciones no revertidos, por fecha efectiva.
  Los reversos conservan evidencia y compensan la operación original; no son
  un reembolso bancario. El desglose usa abonos por concepto/impuesto del original,
  sin sumar los devengos ni las compensaciones genéricas como si fueran cobros.

## Diccionario de indicadores

Todos los importes viajan como cadenas decimales en MXN; los cálculos monetarios
usan Decimal/BCMath y los agregados SQL de la base. No se calcula dinero en Vue.

| Indicador | Definición y fuente | Referencia y detalle |
| --- | --- | --- |
| Capital pendiente | Suma de `capital_insoluto` del motor, publicada en `credito_lecturas.capital`; incluye principal contractual financiado, sin sumar efectivo disponible ni intereses | Corte; créditos con principal positivo |
| Importe vencido pendiente | Suma de conceptos e impuestos pendientes con vencimiento anterior al corte; ordinario no exigible y principal futuro quedan fuera | Corte; importe vencido por crédito |
| Créditos/clientes con atraso | Créditos con vencido positivo y clientes distintos de esos créditos | Corte; créditos con atraso, sin confundir conteo con importe |
| Cobros registrados | Capital, ordinario, moratorio, comisiones e impuestos aplicados, netos de reversos; suma del desglose igual al total recibido aplicable | Periodo; pagos, anticipos y liquidaciones no revertidos |
| Desembolsos efectivos | Suma de `credito_desembolsos.importe`; no sustituirla por capital financiado, que puede incluir cargos | Periodo; transferencias registradas |
| Capital recuperado acumulado | Abonos netos a capital desde el inicio hasta el corte | Corte; no es porcentaje de conversión, ingreso ni ganancia |
| Antigüedad | Todo el vencido del crédito se agrupa por su obligación pendiente más antigua: 1–7, 8–30, 31–60, 61–90 y más de 90 días | Corte; los rangos suman el vencido total |
| Próximos vencimientos | Capital pendiente y programación de intereses/cargos/impuestos del cronograma vigente, por cuatro intervalos de siete días posteriores al corte | Siguientes 28 días; escenario contractual, sin mora futura hipotética ni garantía de cobro |
| Evolución de capital | Saldos a seis cierres mensuales completos hasta el corte | Un cierre sin cobertura queda sin dato, no en cero |
| Cobros/desembolsos mensuales | Movimientos netos de seis meses completos anteriores al mes de `hasta` | No comparar mes parcial como si fuera completo |
| Solicitudes por etapa | Conteo por estado actual de `Solicitud` en el alcance autorizado | Situación actual, independiente del corte financiero; no representa conversión por generación |

En Bullet periódico puede haber interés vencido con capital vencido cero. Bullet
al vencimiento conserva ordinario devengado todavía no exigible. Pagos parciales
dejan remanentes; anticipos y liquidación usan el cronograma que su evidencia
selecciona, incluidos calendarios restaurados mediante reverso.

Los contratos que no conservan política de mora explícita mantienen el desembolso
ya autorizado por el sistema. Su proyección se identifica como `no_disponible`,
con importes nulos; no se inventan reglas ni se amplían bloqueos de originación.
El agregado del alcance queda pendiente de revisión. Esto es una limitación de
evidencia contractual, distinta de un fallo de almacenamiento o cálculo.

## Consistencia, disponibilidad y acceso

La operación de negocio, sus movimientos/calendarios, las proyecciones afectadas
y su auditoría comparten transacción y bloqueo por crédito. Un fallo real de
actualización revierte la operación completa. Reintentos conservan idempotencia.
Pagos pasados actualizan los cierres afectados y hoy; no se modifica evidencia
inmutable ni se permite retroactividad adicional.

El programador actualiza cada cinco minutos con el mismo servicio. Avanza estados
derivados verificados por días pendientes; no persiste devengos de negocio. Conserva
hoy y cierres mensuales. Una reconstrucción explícita parte de la evidencia original.

La cobertura compara todos los créditos desembolsados hasta el corte contra
lecturas de la versión vigente, con cursor de pago y huellas de condiciones,
calendario original, calendario aplicable y pago de origen. Una lectura ausente,
incompatible o invalidada impide publicar saldos y vencimientos consolidados.
Los movimientos registrados conservan su periodo y se muestran por separado.
El diagnóstico compara también estado cifrado, resumen y obligaciones publicadas.
Las huellas y la reconstrucción no sustituyen los controles de integridad de BD.

Se requieren las policies de lectura vigentes de Créditos; Solicitudes y Clientes
conservan sus permisos propios. El equipo actual decide permisos; la sucursal
activa es el alcance predeterminado. `scope=all` conserva la consulta institucional
autorizada existente. Los equipos no se convierten en tenants de créditos.
Sin sucursal activa no hay consolidado implícito, incluido Super Admin.

`GET /dashboard` añade `finanzas`, `detalleFinanciero` y `etapas`; mantiene las
props existentes. `detalle` admite capital, vencido, atraso, cobros, desembolsos y
proximos; pagina 25 registros y conserva fechas/alcance. La petición agrupa su
lectura en una transacción; MariaDB debe mantener su aislamiento REPEATABLE READ.
No hay endpoints de escritura nuevos ni consultas que creen recursos de negocio.

## Interfaz y verificación

La página compone filtros, indicadores, paneles y detalles. Composables coordinan
estado reactivo, URL y cancelación; las props del servidor conservan la autoridad.
Se usan PrimeVue, tokens configurables y las gráficas compartidas. Cifras exactas
en texto/tablas; la conversión numérica para representar gráficos no recalcula
obligaciones. Fechas civiles no cambian por la zona del navegador.

Las pruebas cubren fuentes, permisos, rollback, idempotencia, cortes, gracia,
Bullet, reconstrucción y ausencia de mutaciones al consultar. La medición de
capacidad registra entorno y límites; ver la [guía de validación](../how-to/validar-tablero.md).

Capturas con datos sintéticos: [escritorio](../images/tablero/escritorio.png),
[móvil](../images/tablero/movil.png) y [modo oscuro](../images/tablero/oscuro.png).

Decisión: [ADR 0045](../explanation/adr-0045-lecturas-financieras-tablero.md).
