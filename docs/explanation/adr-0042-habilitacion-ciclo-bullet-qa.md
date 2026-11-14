# ADR 0042: habilitación del ciclo Bullet en QA

- Fecha: 2026-11-07.
- Estado: aceptado e implementado en PR #73; CI e integración por commit en su
  [evidencia de entrega](../agent-notes/core-crediticio-extensible.md).
- Complementa ADR 0038–0041 y sustituye su bloqueo operativo provisional.

## Contexto

Los incrementos anteriores implementaron cálculo, condiciones, obligaciones, mora,
anticipos y liquidación internamente. Activarlos sin conectar la exigibilidad al
simulador produciría una tabla periódica incluso con interés al vencimiento y un
CAT distinto del contrato. La evidencia sintética de pagos tampoco acredita que
las condiciones recorran solicitud, firma y desembolso.

## Decisión

Habilitar Bullet en motor 2/Act/360 con las dos políticas y combinaciones explícitas
existentes. El adaptador de amortización recibe la política, obligatoria para
Bullet y ausente para los otros métodos; aplica `ExigibilidadInteresBullet` antes
de serializar. Simulación y CAT usan ese mismo recorrido, conservando periodos
sin pago y numeradores hasta el redondeo final. No hay un motor nuevo ni una
normalización de contratos anteriores.

Reutilizar servicios, permisos, estados y banderas QA de aprobación, documentos,
firma, desembolso, pagos y anticipos. Solicitud y paquete conservan condiciones;
el desembolso copia su evidencia al crédito. Las nuevas proyecciones nunca
reescriben la tabla firmada ni sus hashes. Sin migración adicional a las dos
columnas nullable de ADR 0039, sin backfill, permisos ni seeders operativos nuevos.

El resumen Bullet distingue ordinario pendiente devengado, aún no exigible y
vencido, sin impuestos. El atraso corresponde a obligaciones; capital vencido
conserva su significado. Las ayudas y el anexo documental describen la misma
política y el prepago conservando fecha. Las condiciones cifradas permanecen
ocultas: el formulario de anticipo recibe solo la política necesaria como prop.

La concurrencia se verifica con procesos y conexiones independientes contra
MariaDB aislada; el coordinador retiene filas mientras ambos trabajadores
alcanzan sus consultas bloqueantes. Se prueban referencias/idempotencia,
previas competidoras, reversos y rollback de calendarios/movimientos/auditoría.
SQLite y previas ejecutadas secuencialmente no sustituyen esa evidencia.

El reloj E2E reside exclusivamente en el runner PHP de pruebas y un archivo de
`storage/framework/testing`. Exige entorno y base aislados. No añade rutas ni
configuración temporal a la aplicación, ni cambia el reloj del sistema o de QA.
El navegador recorre ambas políticas con PDF real y los servicios vigentes.

## Auditoría y entrega

Los emisores actuales registran cada creación, activación, solicitud, paquete,
firma, desembolso, pago y reverso. No hay evento adicional por calcular, consultar
o resolver capacidades. Reintentos y concurrencia deben conservar emisión única,
actor, sujeto, equipo/sucursal originales y metadatos permitidos.

Integrar requiere review medium, barrido acumulado #73 con #74 consolidada contra
`c30c941` y CI aprobada. El usuario realiza despliegue y validación compartida;
registrar sus resultados separadamente de pruebas aisladas. La integración no
acredita despliegue ni aceptación QA por sí sola. Diaria, capitalización,
reducción de plazo Bullet, SIC productivo y dinero real siguen fuera de alcance.

Contrato en [core crediticio](../reference/core-crediticio.md),
[casos independientes](../reference/bullet-casos-definicion.md),
[guía QA](../how-to/qa-bullet.md) y
[evidencia de la entrega](../agent-notes/core-crediticio-extensible.md).
