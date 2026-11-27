# Tablero: diseño y proyecciones financieras

## Estado final — 2026-11-17

Entrega del tablero y seguimiento visual implementados e integrados; el usuario
confirma el 17/11 que hizo el merge, desplegó y probó en QA con resultado
satisfactorio. Se registra esa aceptación general sin atribuir escenarios,
navegador, importes de contraste o SHA desplegado que no detalló.

- [PR #81](https://github.com/jata-imk/kronik/pull/81): código `9835ef7`,
  integración `19a0614`.
- [PR #82](https://github.com/jata-imk/kronik/pull/82): código `bf264ab`,
  integración `bfa0b87`, realizada por el usuario el 14/11 según GitHub.
- Base funcional del conjunto: `67bba2c`. Integra también #65 `4f03503`,
  #79 `ba49793` y #80 `defe471`, en el orden autorizado.
- El árbol de `bfa0b87` coincide con el head revisado `bf264ab`.
- Main local y remoto sincronizados. Cambios documentales previos ajenos a esta
  entrega preservados; no se incluyeron en las PR.

## Referencias canónicas

- [Implementación en Notion](https://app.notion.com/p/41361db7db7f81629e70da6aa0e4fc3f).
- [Definición y prototipo](https://app.notion.com/p/41361db7db7f81ecb2e9c006e1ce427f).
- [Proyecciones del core](https://app.notion.com/p/40961db7db7f813b989fe177f80f5c8d).
- [Contrato de fuentes, cifras y disponibilidad](../reference/tablero-financiero.md).
- [ADR 0046: lecturas derivadas](../explanation/adr-0046-lecturas-financieras-tablero.md).
- [Preparación y validación](../how-to/validar-tablero.md).

## Entrega y límites

Lector compartido de evidencia original, proyector que reutiliza el motor y único
escritor transaccional de las dos tablas derivadas. Desembolso, pago, anticipo,
liquidación y reverso mantienen operación, movimientos, auditoría y proyecciones
en la misma transacción. Actualización temporal sin devengos, reconstrucción y
diagnóstico; regla permanente de consistencia en AGENTS.md.

Agregados SQL y detalles paginados autorizados, cortes de hoy/cierres mensuales
corregibles y periodos de movimientos independientes. Una captura histórica
pendiente o fuente incompatible no publica saldos consolidados vigentes. Un
contrato sin política de mora queda sin lectura, sin inventar defaults.

Vue usa componentes/composables y paleta Kronik: métricas, barras mensuales,
área de capital, circular de cobros, antigüedad, programación contractual,
listas de atención y embudo de estados actuales. Mantiene importes exactos en
texto/tablas, descarta respuestas tardías y limpia datos/permisos revocados.
#82 alinea controles y enlaces; explica el corte en una fila independiente.
Son ajustes visuales y de consulta, sin acciones auditables adicionales.

Capital pendiente no incluye efectivo disponible; intereses cobrados no son
utilidad; estados actuales no son conversión histórica. Reportes completos y
CSV/XLSX, Tesorería, Contabilidad, cortes regulatorios inmutables, SIC productivo
y dinero real conservan sus alcances propios. No se cierran por esta entrega.

## Pruebas, revisión y CI

- `9835ef7`: backend completo **974 aprobadas, 2 omitidas, 47679 aserciones**,
  178.39 s; frontend **172/65 archivos**; build y dos E2E Chromium aprobados.
- `bf264ab`: hook pre-push completo **974/2/47679**, 144.45 s y build aprobado;
  seis pruebas frontend dirigidas en dos archivos y dos E2E aprobados.
  Comprueban alineación geométrica, detalle, permisos, 320/390/768/1440 px,
  teclado, oscuro, paletas y zoom 200 %, sin errores de consola.
- [CI #81 aprobada](https://github.com/jata-imk/kronik/actions/runs/43612999462)
  y [CI #82 aprobada](https://github.com/jata-imk/kronik/actions/runs/43617769673):
  suites, MariaDB multiconexión con proyecciones, PDF real y regresiones
  tablero/histórico/Bullet/simple/gracia/RFC.
- Reviews medium de #81 contra `67bba2c` y #82 contra `9b94348` sin hallazgos.
  Barrido medium acumulado final del 17/11, head `bfa0b87` contra `67bba2c`:
  sin regresiones accionables; 50 pruebas dirigidas/826 aserciones aprobadas.
  Son una selección de la suite, no se suman al conteo completo.
  Log local: `output/tablero-pr/review-medium-cierre-17nov.log`.
- Casos de motor cubren ambas políticas Bullet, pagos parciales, gracia,
  anticipo/liquidación/reverso, rollback, idempotencia, reconstrucción, auditoría
  y consulta sin mutaciones. Obligación futura Bullet tras anticipo:
  oráculo independiente **380.48**.
- Fixtures E2E por servicios reales en SQLite aislada: cuatro créditos,
  capital esperado **44880**; cobros **3120 + 8 × días del mes anterior**.
- Capturas sintéticas: [escritorio](../images/tablero/escritorio.png),
  [móvil](../images/tablero/movil.png) y [oscuro](../images/tablero/oscuro.png).

## Capacidad medida

Windows/PHP 8.3.16/SQLite en memoria, 10000 créditos simples sintéticos con
12 cuotas; no acredita originaciones, cartera antigua, otros productos,
líneas/disposiciones, red, concurrencia o rendimiento productivo.

| Ensayo | Resultado |
| --- | --- |
| Completo, 13/11 a las 21:50:38 de México | Construcción 159.988 s; avance diario 194.986 s; reconstrucción 200.198 s |
| Lecturas del ensayo completo | 194.60–220.81 ms; 56 consultas; 58 MiB |
| Solo lectura, 14/11 a las 09:47:37 de México | 230.74–272.99 ms; 58 consultas; 58 MiB |

Ambos ensayos aprobaron una prueba/22 aserciones; corte sintético 2026-11-21.
Artefactos separados: `storage/framework/testing/tablero-capacidad.json` y
`tablero-capacidad-lectura.json`. El segundo no mide construcción/avance/reconstrucción.

## QA reportado y operación

El 14/11 el usuario confirmó `19a0614` en su VPS. Dos créditos conservaban
evidencia verificable, sin captura pendiente, pero no tenían lecturas derivadas.
El usuario ejecutó reconstrucción y diagnóstico: **dos verificados, cero
incidencias** en ambos; desapareció el aviso al consultar Todas las sucursales.

Faltaba el crontab del usuario del sitio. El usuario instaló la llamada por
minuto a Laravel; registro de las **10:50:02**: `creditos:proyectar` **DONE**.
Cron activo y **enabled**, con autoinicio. El 17/11 confirmó además despliegue y
QA satisfactorios después de #82; no aportó nuevo SHA ni detalle de escenarios.

Acceso al VPS exclusivo del usuario, según AGENTS.md. Los agentes facilitan
comandos y revisan salidas; no accedieron por SSH ni ejecutaron mutaciones en
esa BD/VPS. La guía de despliegue documenta las tareas actuales y distingue
declaración, configuración y ejecución real.

## Continuidad fuera de este incremento

- Backlog 08 conserva la definición de reportes operativos y CSV/XLSX; no hay
  una aprobación nueva de formatos, navegación o alcance contable.
- Notion programa revisión de calendario crediticio/inhábiles y periodicidad
  diaria desde el 16/11, coordinada con aceptación de Bullet. Canales y
  notificaciones pertenecen al frente de Brian.
- Proyecciones para productos futuros, capacidad de líneas/disposiciones,
  aceptación integral Bullet e históricos conservan sus tareas y evidencia
  propia; la aceptación del tablero no las cierra.
