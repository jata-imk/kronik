# ADR 0039: condiciones Bullet y habilitación por capacidad

- Estado: implementado en PR #73; el bloqueo operativo provisional queda sustituido por [ADR 0042](adr-0042-habilitacion-ciclo-bullet-qa.md).
  Integración y despliegue se registran en la Agent Note.
- Fecha: 2026-11-05.
- Complementa ADR 0035 y 0038.

## Contexto

PR #73 prepara cálculo puro Bullet. Las tres bases moratorias y el disparador por
obligación vencida impagada están aclarados. Persistir una opción no demuestra
que pagos, anticipo y mora puedan operarla: ofrecerla desde el catálogo común
sin distinguir preparación de operación habilitaría una combinación incompleta.

## Decisión

Conservar el editor de Productos. El catálogo declara opciones y combinaciones
explícitas; solo Productos pide `paraVue(prepararBorradores: true)`. Guardar y
copiar validan configuración, mientras activación/programación, registro de uso
y simulación conservan validación operativa. Bullet requiere motor 2/Act/360 y
permanece rechazado para operar. Solicitudes recibe las opciones operativas.

Añadir columnas nullable `base_ordinaria` y `politica_interes_bullet` a las reglas,
sin backfill ni modificación de snapshots/hashes anteriores. `base_moratoria`
existente conserva su significado histórico `capital_vencido` para los métodos
actuales. Nuevas configuraciones explicitan ordinario `saldo_insoluto`; Bullet
exige política `periodicos`/`al_vencimiento`, base moratoria `saldo_insoluto`,
`capital_inicial` u `obligacion_vencida` y prepago `conservar_fecha`.
«Igual al ordinario» es una acción de edición que copia la base efectiva; no se
persiste una referencia mutable. No se habilitan bases ordinarias adicionales.

En este incremento Bullet se configura en versión separada: mezclarlo con métodos
actuales exigiría políticas/bases/prepago por método que el contrato común aún no
representa. Esta restricción de compatibilidad no modifica versiones previas ni
define una prohibición permanente del dominio. Al cambiar método mediante una
acción explícita, Vue limpia condiciones incompatibles, ajusta prepago y avisa;
no elige base moratoria por el usuario. Backend rechaza payloads incompatibles.

El calendario semanal/cada 15 días/mensual y su número de periodos definen la
duración y vencimiento final. En periódicos también definen vencimientos de
interés; al vencimiento no introducen pagos intermedios. La consulta no aplica
valores predeterminados ni conversiones y no tiene controles de mutación.

## Consecuencias

Una migración aditiva es necesaria antes de usar la configuración nueva. No hay
seeders, permisos ni variables nuevas. Guardado/copia/actualización conservan
los eventos existentes y la lista de metadatos permitidos. La resolución y
consulta no generan actividad adicional.

Este incremento no congela todavía un contrato Bullet operativo: rechaza su
activación antes de persistir snapshot o retirar la versión activa. La habilitación
posterior deberá integrar obligaciones, mora, anticipos y congelar estas mismas
condiciones con sus capacidades probadas. Diaria y capitalización siguen separadas.

## Evidencia

[Contrato de Productos](../reference/productos-crediticios.md),
[casos Bullet](../reference/bullet-casos-definicion.md),
[Agent Note](../agent-notes/core-crediticio-extensible.md).
`BulletCondicionesTest`, pruebas Vue del editor/cambio de método y E2E Productos
cubren persistencia/copia, incompatibilidad, consulta y rechazo operativo. Los
resultados ejecutados se registran en la nota; no acreditan despliegue.
