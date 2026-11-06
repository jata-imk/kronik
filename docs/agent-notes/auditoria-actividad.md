# Matriz de auditoría y actividad

## Referencias

- Notion: [alcance, plan y validaciones](https://app.notion.com/p/21461db7db7f80d5b813c2899522a72b).
- Rama: `feat/cierre-auditoria-actividad`; continuidad `80b2f92`; base funcional `0707ce9`.
- Entrega: [PR #72](https://github.com/jata-imk/kronik/pull/72).
- Código revisado: [1bd109f](https://github.com/jata-imk/kronik/commit/1bd109f3adc0d394e6674c6dfaa90d65ffa99f68).
- Contrato único: [matriz por acción](../reference/auditoria-eventos.md).
- Decisión: [ADR 0037](../explanation/adr-0037-cobertura-tecnica-auditoria.md), continuación de ADR 0028, 0031 y 0036.

## Estado — 05/11/2026

Cobertura técnica implementada en la rama y revisada contra `0707ce9`. La PR
incluye todos los incrementos de esta funcionalidad desde `80b2f92`. Se conservaron
los archivos locales preexistentes sin seguimiento. Notion sigue Doing mientras
se registra la integración y el despliegue/QA de este incremento.

La publicación pasa por el hook pre-push (suite backend y build) y la CI
(MariaDB, frontend, build y concurrencia). Los resultados del head publicado se
consultan en [checks de la PR](https://github.com/jata-imk/kronik/pull/72/checks)
y en Notion; los conteos pertenecen al commit que los produjo. No se integra ni
se despliega como parte de esta ejecución.

## Resultado

- Matriz reconciliada: 119 códigos, 19 módulos del inventario vigente y procesos
  transversales; una fila por acción, emisor y prueba. Consultas/cálculos puros y
  mantenimiento técnico tienen justificación expresa de no aplicabilidad.
- Equipos/roles/permisos comparten servicios y conservan equipo afectado y sucursal
  original. Incluye administración, Jetstream, Super Admin, delegados y comandos.
- Accesos, logout, rechazos/limitación, ciclo 2FA, tokens, verificación de correo,
  cierre de otras sesiones, cambio de equipo y CSV tienen actividad efectiva.
  Los fallos sin identidad autenticada siguen anónimos, sin inferir identidad
  ni contexto desde el correo. Generado/aceptado por transporte no significa recibido.
- Mutaciones de productos, plantillas, expediente, traslado de clientes, menú,
  solicitudes y configuración conservan actividad en su transacción y comparan
  cambios efectivos. No copiar evidencias, motivos, valores sensibles ni snapshots.
- Activaciones programadas, estados SIC y PDF separan sistema ejecutor e iniciador,
  conservan contexto persistido incluido null y evitan duplicados por reentrega.
  Concurrencia real se comprueba con conexiones MariaDB independientes.

## Validaciones y revisión

- `/review medium`, base `0707ce9`, código `1bd109f`: sin hallazgos accionables.
  Revisión de solo lectura; las pruebas se ejecutan por separado. Barrido acumulado
  de la PR completa: contratos, permisos, migración, transacciones, contexto,
  privacidad, históricos, reintentos y navegación de consulta.
- Hallazgos previos resueltos: PDF común perdido puede quedar fallido tras agotar
  regeneración; se conserva la protección del original contractual. Cambiar solo
  vigencia produce actividad. Un borrador con configuración heredada no soportada
  puede corregirse sin que la comparación de auditoría valide el estado anterior.
- Barrido manual: comparación de producto bajo bloqueo con orden producto → versión;
  traslado atómico y menú sin eventos ante no-op. Desafío 2FA revocado mientras
  está abierto exige reiniciar acceso y audita rechazo, sin descifrar null.
- Regresiones: 83 pruebas/845 aserciones en `b882fd1`; tres casos de producto/54
  aserciones en `8e115b8`; seguridad 31 pruebas/380 aserciones en `1bd109f`.
- Frontend: 140 pruebas aprobadas; build aprobado. CI de `832d922` aprobada,
  incluyendo MariaDB y ambas comprobaciones de concurrencia. Hook de ese commit:
  693 pruebas, una omitida y 43 753 aserciones. Evidencia final del head en los checks.
- E2E Chromium con BD aislada: ocho recorridos de acceso/equipos/roles/auditoría
  y seis de documentos/productos/SIC aprobados en `3868b1c`; recorrido dual
  completo aprobado en `1bd109f` (3,5 minutos), con PDF real, firmas, desembolso,
  pagos, anticipos, liquidación y reversos sintéticos. Sin proveedores externos.
- Para PDF se usó Chromium headless instalado, mediante `DOCUMENTOS_CHROME_PATH`.
  Chrome de escritorio agotó el límite de medición en este equipo. El recorrido
  dual conserva todas sus aserciones y dispone de 300 s para el ciclo completo.
- Pint sobre PHP cambiado, diff y enlaces locales revisados; cada código tiene una
  sola fila. Dependencias restauradas desde lockfiles sin modificarlos.

Captura con fixtures sintéticas: [detalle de intento anónimo](../assets/auditoria-actividad/actividad-anonima.png).

## Despliegue y pendientes reales

Aplicar `2026_11_05_000000_add_scheduling_context_to_producto_versiones` antes de
reanudar workers/programador del código nuevo. Añade `programada_por`,
`programada_team_id` y `programada_sucursal_id`, nullable, sin backfill ni cascadas.
No requiere seeders, permisos ni variables nuevos. El filesystem no comparte
transacciones SQL; sus límites y la protección de originales están en ADR 0037.

Pendientes: integrar PR #72 y registrar por separado despliegue y QA de este
incremento. Retención, resguardo, integridad/detección de alteraciones y alertas
siguen como seguimiento separado aprobado; no se cambian políticas ni se habilita
limpieza automática. SIC productivo, dinero real, reglas financieras, Bullet y
notificaciones de otro compañero conservan su alcance previo.

## Cierres anteriores conservados

El usuario confirmó el 04/11/2026 despliegue y QA satisfactorios de
[contexto de sucursal](contexto-sucursal-transversal.md). También confirmó revisión
de [integridad matemática e históricos](core-crediticio-extensible.md), con cartera
de uno o dos clientes sin crédito activo o de prueba y sin tratamiento correctivo.
Ambas tareas Notion siguen cerradas; esta entrega no las reabre. Se conserva la
definición documental anterior de Bullet. `80b2f92` fue el traspaso documental
publicado, previo a la autorización de reanudación del 05/11/2026.
