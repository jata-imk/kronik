# Tablero: diseño y proyecciones financieras

## Referencias

- [Implementación en Notion](https://app.notion.com/p/41361db7db7f81629e70da6aa0e4fc3f).
- [Definición y prototipo](https://app.notion.com/p/41361db7db7f81ecb2e9c006e1ce427f).
- [Proyecciones del core](https://app.notion.com/p/40961db7db7f813b989fe177f80f5c8d).
- Rama `feat/tablero-proyecciones`, base funcional `67bba2c`.
- [PR #81](https://github.com/jata-imk/kronik/pull/81); código `6e0da93`.
- [Contrato de cifras y disponibilidad](../reference/tablero-financiero.md),
  [ADR 0046](../explanation/adr-0046-lecturas-financieras-tablero.md) y
  [preparación y validación](../how-to/validar-tablero.md).

## Estado actual — 2026-11-13

Implementación publicada en la PR #81, con revisión medium del código aprobada
sin hallazgos accionables. CI en curso. Sin integración, despliegue ni QA
compartido de esta entrega. Se conservaron las modificaciones documentales
anteriores ajenas al tablero; no se incluyeron en la PR. La retoma tras el apagado
de la PC no perdió código. Las tareas siguen abiertas para integración y QA.

El lector compartido verifica evidencia original; el proyector reutiliza el motor
y el escritor central mantiene las dos tablas derivadas dentro de las operaciones
actuales. Incluye desembolso, pago, anticipo, liquidación y reverso, actualización
temporal, reconstrucción y diagnóstico. La consulta del tablero usa agregados SQL
y detalles paginados. La regla permanente de consistencia quedó en `AGENTS.md`.

La UI conserva el sistema visual Kronik y usa componentes Vue/composables,
tarjetas, tres gráficas con tablas, filtros independientes de corte/periodo,
detalle y etapas actuales de solicitudes. Conserva importes decimales exactos
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

- Backend completo: `php artisan test --compact`: **938 aprobadas, 2 omitidas,
  47083 aserciones**, 114.18 s. Las omitidas incluyen la medición de volumen
  optativa; esa medición sí se ejecutó aparte. Backend sin cambios posteriores.
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
  la rama. CI valida además MariaDB y E2E sobre la PR; su resultado está pendiente.
- La revisión cubrió el diff acumulado completo de esta entrega contra la base,
  incluidas las interacciones core/tablero, migración, permisos, auditoría y UI.
  No hubo correcciones posteriores de código. Los cambios posteriores a
  `6e0da93` de esta sesión solo registran el estado y evidencia documental.

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

## Siguiente paso

1. Comprobar CI de la PR #81 y resolver fallos antes de dejarla lista. La revisión
   medium del código `6e0da93` ya terminó sin hallazgos accionables.
2. Confirmar en CI concurrencia MariaDB con `tests/Concurrency/bullet.php`, que
   contrasta también proyecciones. La cuenta local `kronik` recibió acceso
   denegado a `kronik_test`; no se migró ni reinició la BD operativa.
3. Desplegar en QA siguiendo la guía, migrar, reconstruir y diagnosticar.
   Contrastar cifras operativas independientes con el equipo; registrar entorno,
   commit y resultados reales en esta nota y Notion antes de cerrar las tareas.
