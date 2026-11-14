# Tablero: diseño y proyecciones financieras

## Referencias

- [Implementación en Notion](https://app.notion.com/p/41361db7db7f81629e70da6aa0e4fc3f).
- [Definición y prototipo](https://app.notion.com/p/41361db7db7f81ecb2e9c006e1ce427f).
- [Proyecciones del core](https://app.notion.com/p/40961db7db7f813b989fe177f80f5c8d).
- Rama `feat/tablero-proyecciones`, base funcional `67bba2c`.
- [PR #81](https://github.com/jata-imk/kronik/pull/81); código final `9835ef7`,
  integrado en main `19a0614`.
- [Contrato de cifras y disponibilidad](../reference/tablero-financiero.md),
  [ADR 0046](../explanation/adr-0046-lecturas-financieras-tablero.md) y
  [preparación y validación](../how-to/validar-tablero.md).

## Estado actual — 2026-11-14

Implementación y ajuste visual integrados mediante PR #81 en main `19a0614`,
después de #65, #79 y #80, en el orden autorizado. Head final `9835ef7`, review
medium acumulado contra `67bba2c` sin hallazgos accionables y
[CI final aprobada](https://github.com/jata-imk/kronik/actions/runs/43612999462),
incluidos MariaDB, PDF y todos los recorridos de navegador. El árbol integrado
coincide exactamente con el head revisado. El seguimiento visual posterior se
trabaja en `fix/tablero-alineacion`, sobre main `9b94348`.

Sin despliegue ni QA operativo compartido de esta entrega. Las tareas continúan
abiertas para esa aceptación. Las doce modificaciones documentales anteriores
ajenas al tablero conservan sus diferencias y no se incluyeron en la PR.

El lector compartido verifica evidencia original; el proyector reutiliza el motor
y el escritor central mantiene las dos tablas derivadas dentro de las operaciones
actuales. Incluye desembolso, pago, anticipo, liquidación y reverso, actualización
temporal, reconstrucción y diagnóstico. La consulta del tablero usa agregados SQL
y detalles paginados. La regla permanente de consistencia quedó en `AGENTS.md`.

La UI conserva el sistema visual Kronik y usa componentes Vue/composables,
tarjetas, barras mensuales, área de capital, circular de cobros, programación
apilada, antigüedad, listas de atención y embudo de etapas actuales. Gráficas con
tablas, filtros independientes de corte/periodo y detalles paginados. Conserva importes decimales exactos
en texto y tablas. El cambio de alcance/fecha oculta cifras anteriores mientras
carga o falla; las recargas parciales también limpian permisos y datos revocados.

La compatibilidad de contratos sin política de mora está cubierta: no se amplían
bloqueos de desembolso ni se asignan defaults; la lectura queda `no_disponible`
con importes nulos. Una falla real de cálculo/almacenamiento revierte la operación.

## Decisiones y límites

- Aprobados: proyecciones mantenidas por el core, operación/ledger/auditoría/lectura
  atómicos, hoy/cierres mensuales, historia corregida por fecha efectiva, atraso
  contractual separado de gracia y diseño dentro del proyecto.
- Capital prestado pendiente excluye efectivo disponible. Intereses cobrados no
  equivalen a utilidad. Solicitudes por etapa actual no son conversión histórica.
- Los cierres derivados son corregibles; no sustituyen cortes regulatorios
  inmutables ni cierres contables. No se habilitan dinero real ni SIC productivo.
- El usuario consultó cómo organizar informes. Se explicó como orientación:
  tablero y reportes operativos dentro de Informes y analítica; Tesorería y
  Contabilidad con responsabilidades propias. No implica aprobar navegación,
  nuevos reportes, formatos, prioridades o un alcance contable adicional.
- Reportes completos/CSV/XLSX, caja/bancos, conciliación, pólizas, libros y estados
  financieros quedan fuera de este incremento; requieren sus fuentes y definición.
- La aprobación general del prototipo por el equipo fue reportada por el usuario;
  no acredita QA de las cifras implementadas. Los ejemplos adicionales ofrecidos
  por el equipo no se recibieron en esta sesión.

## Evidencia local

Los resultados iniciales siguientes corresponden al incremento anterior al ajuste
visual del 14/11; los resultados finales se registran en su sección específica.

- Backend completo: `php artisan test --compact`: **938 aprobadas, 2 omitidas,
  47083 aserciones**, 114.18 s. Las omitidas incluyen la medición de volumen
  optativa; esa medición sí se ejecutó aparte.
- Frontend completo antes del último refuerzo de formato: **164 aprobadas en
  62 archivos**. Después, selección final de tablero/serie/composable:
  **5 aprobadas en 3 archivos**, incluido un caso nuevo de centavos mayores
  al entero seguro de JavaScript y error de conexión sin conservar cifras.
- Build final aprobado. Conserva avisos existentes de Browserslist, recursos
  estáticos de landing/fuentes y tamaño de chunks; sin dependencias nuevas.
- Chromium aislado: **2 escenarios aprobados**, con datos creados mediante
  servicios reales (dos Bullet de 12000 y anticipo de 3120), capital esperado
  20880 y cobros 3120. Incluye detalle/recarga, usuario sin permisos,
  320/390/768/1440 px, teclado, oscuro, paletas y zoom 200 %; sin errores de consola.
- Capturas sintéticas: [escritorio](../images/tablero/escritorio.png),
  [móvil](../images/tablero/movil.png) y [oscuro](../images/tablero/oscuro.png).
- Pruebas cubren fuentes/cortes, gracia, pagos parciales, ambas políticas Bullet,
  anticipos, liquidación, reversos, rollback de encabezado/obligaciones/operación,
  idempotencia, reconstrucción, auditoría de sistema y consulta sin mutaciones.
  El calendario futuro Bullet tras anticipo se contrasta con **380.48** independientes.
- Revisión interna acumulada contra la base: contrato, puntos de escritura,
  migración, permisos, auditoría, consistencia, UI y regresión. Se corrigieron
  doble descuento de interés programado Bullet, compatibilidad sin mora,
  precisión de tablas, carreras al desmontar Chart y retícula con zoom.
  No se atribuye esta revisión a `/review medium` ni a una revisión independiente.

### Revisión de la PR — 2026-11-13

- Una PR para este incremento: #81, base `67bba2c`, código revisado `6e0da93`.
- Revisión independiente mediante `codex review -c model_reasoning_effort=medium
  --base origin/main` en checkout aislado del commit. Resultado: sin regresiones
  accionables. El revisor no ejecutó pruebas porque ese checkout no tenía
  dependencias; se conservan las pruebas separadas y la verificación del hook.
- El hook pre-push volvió a aprobar backend completo y build antes de publicar
  la rama. La CI posterior aprobó MariaDB y E2E; la evidencia vigente corresponde
  al head final indicado más abajo.
- La revisión cubrió el diff acumulado completo de esta entrega contra la base,
  incluidas las interacciones core/tablero, migración, permisos, auditoría y UI.
  El cierre `b294c70` solo añadió evidencia documental a ese código.

### Integración con capturas históricas — 2026-11-13

El usuario autorizó integrar todas las PR abiertas en orden de publicación:
#65 (`4f03503`), #79 (`ba49793`), #80 (`defe471`) y después #81. Las tres primeras
ya están integradas; #81 conserva su única PR. Se resolvieron conflictos de CI,
reinicio QA, validación y fixtures manteniendo ambos recorridos. ADR del tablero
renumerado a 0046 porque la captura histórica integrada ya usa 0045.

Se añadió una comprobación de interacción: una captura histórica pendiente impide
presentar saldos consolidados vigentes. El aviso distingue movimientos capturados
y ofrece enlace autorizado; completar la captura mantiene proyecciones y auditoría
atómicamente. Prueba de rollback del cierre y de recuperación de cobertura.
Frontend conjunto: 170 aprobadas en 64 archivos; build aprobado. Backend de
`3465468`: 973/47640 aprobados, review medium acumulado contra `67bba2c` sin
hallazgos y [CI aprobada](https://github.com/jata-imk/kronik/actions/runs/43560561315).
El ajuste visual posterior se acredita por separado.

### Capacidad: 10000 créditos sintéticos

Ensayo completo aprobado: **1 prueba, 22 aserciones**, 560.08 s. Windows,
PHP 8.3.16, SQLite `:memory:`, simple con 12 cuotas por crédito. Ejecución real
2026-11-13 21:50:38 en México; corte sintético 2026-11-21. Cada crédito confirma
su transacción raíz, sin acumular savepoints del arnés PHPUnit.

| Fase | Resultado |
| --- | --- |
| Construcción | 159.988 s |
| Avance de un día | 194.986 s |
| Reconstrucción desde originales | 200.198 s |
| Cinco lecturas | 220.81 / 195.20 / 195.00 / 200.13 / 194.60 ms |
| Consultas por lectura | 56 en las cinco |
| Memoria pico | 58 MiB |

Artefacto local: `storage/framework/testing/tablero-capacidad.json`.
Se replica evidencia financiera sintética conservando FK; no representa 10000
originaciones, mezcla de productos, red, concurrencia ni rendimiento productivo.
No extender estos tiempos a cartera antigua, Revolvente o MariaDB.

## Ajuste visual solicitado — 2026-11-14

El usuario contrastó las capturas con el borrador y una referencia de tablero.
Solicitó embudo, gráfica circular, barras verticales, programación futura más
visible, colores, badges, ayudas y listados con acceso al detalle completo.
Se completó en la misma PR #81 y quedó integrado tras aprobar revisión y CI.

Componentes específicos: EmbudoEtapas, AtencionCartera, AntiguedadCartera,
MovimientosPeriodo y ComposicionCobros. SerieFinanciera conserva tablas exactas,
paleta compartida y admite área/apilado. Backend publica listas limitadas y total
programado desde las mismas lecturas; autorización vigente sin cambios de policy.
El embudo muestra estados actuales, sin fabricar conversión, metas o utilidad.

Fixtures E2E ampliados con cuatro créditos, pagos de interés, atraso y etapas
reales por servicios, sin providers. Capital 44880; cobros 3120 + 8 × días del mes
anterior. Pruebas dirigidas backend: 15/269 aprobadas; frontend completo:
172 aprobadas en 65 archivos. Build y dos E2E Chromium aprobados, incluidas
lista de atraso, gráfico circular, etapas, 320/390/768/1440 px, temas y zoom 200 %.
Capturas renovadas con datos poblados. La suite backend completa y review/CI del
head visual quedaron aprobadas, según la evidencia final siguiente.

Medición exclusiva de lectura con 10000 créditos/12 cuotas en SQLite `:memory:`,
Windows/PHP 8.3.16, ejecutada el 14/11 a las 09:47:37 de México: cinco consultas
al tablero de 236.16 / 233.16 / 253.99 / 272.99 / 230.74 ms, 58 consultas SQL
cada una y 58 MiB pico. Las dos consultas adicionales corresponden a las listas
limitadas de atención. Ensayo: 1 prueba/22 aserciones. Artefacto local separado
`storage/framework/testing/tablero-capacidad-lectura.json`; construcción, avance
y reconstrucción son nulos en este ensayo, no se atribuyen a estos tiempos.

## Revisión e integración final — 2026-11-14

- Head `9835ef7`: hook pre-push secuencial, **974 backend aprobadas, 2 omitidas,
  47679 aserciones**, 178.39 s; build aprobado. Frontend: **172/65 archivos**.
- `codex review -c model_reasoning_effort=medium --base 67bba2c` en checkout
  aislado: sin regresiones accionables. Barrido de #65/#79/#80/#81 y sus
  interacciones contra la base funcional, no solo del ajuste visual. El revisor
  ejecutó 50 pruebas dirigidas/826 aserciones, todas aprobadas; son una selección
  de la suite, no se suman al conteo completo. Log local fuera de Git:
  `output/tablero-pr/review-medium-visual.log`.
- [CI 43612999462](https://github.com/jata-imk/kronik/actions/runs/43612999462)
  aprobada: build/frontend/backend, guardas E2E, concurrencia de folios/auditoría
  y Bullet con proyecciones en MariaDB, PDF real, gracia, tablero, captura
  histórica, dos políticas Bullet, originación simple dual y RFC genéricos.
- Integraciones autorizadas en orden: #65 `4f03503`, #79 `ba49793`, #80 `defe471`
  y #81 **`19a0614`**. #81 conserva una sola PR para toda esta entrega.
- Actualización documental posterior sin cambios funcionales ni eventos
  auditables nuevos. Enlaces locales, navegación temática y diferencias locales
  preexistentes verificados. No se migró ni reinició la BD operativa.

## Pendientes reales

1. Desplegar en QA siguiendo la guía, migrar, reconstruir y diagnosticar;
   comprobar el programador activo y la cobertura de las lecturas.
2. Contrastar cifras operativas independientes con el equipo; registrar entorno,
   commit y resultados reales en esta nota y Notion antes de cerrar las tareas.

## Seguimiento de alineación y programador — 2026-11-14

El usuario acepta la composición visual y señala dos alineaciones. La ayuda de
corte pasa a una fila independiente, los controles conservan altura común y los
enlaces de las cuatro tarjetas se alinean al pie. Chromium verifica las posiciones
reales de controles/enlaces, además de móvil, oscuro y zoom. Ayuda más explícita:
hoy o último día de un mes anterior. Sin fórmulas, permisos, operaciones ni nuevos
eventos auditables; la reconstrucción conserva el evento existente.

Diagnóstico solo de lectura en la conexión configurada localmente: dos créditos
con evidencia verificable, sin capturas históricas pendientes y sin ninguna
lectura derivada. Se proporcionaron comandos de reconstrucción y diagnóstico;
no se ejecutaron mutaciones sobre esa BD ni sobre la VPS.

El usuario conserva acceso exclusivo a la VPS; los agentes proporcionan comandos
y revisan sus resultados. Regla permanente registrada en AGENTS.md. El usuario
reporta las tres tareas en `schedule:list` y servicio cron activo, pero
`no crontab for kronik`, sin entradas visibles en `/etc/crontab`, `/etc/cron.d`
ni temporizadores con los nombres consultados. Eso no acredita actualización
automática. Se facilitó entrada permanente por minuto y registro de salida;
el usuario instaló su crontab y reportó reconstrucción y diagnóstico de dos
créditos, ambos sin incidencias. Registro VPS de las 10:46 confirma invocación
automática de Laravel y ejecución de `sic:marcar-inciertas`. Falta observar el
turno de cinco minutos de `creditos:proyectar` y confirmar que desapareció el
aviso al recargar. La guía de despliegue deja de afirmar que no hay tareas.

Validación del ajuste local: seis pruebas frontend dirigidas, build y dos E2E
Chromium aprobados, incluyendo alineación geométrica en escritorio y ausencia
de desbordamiento en 320/390/768/1440 px, oscuro y zoom 200 %. Capturas actualizadas.
Revisión medium y CI del seguimiento se registrarán al publicar su PR.
