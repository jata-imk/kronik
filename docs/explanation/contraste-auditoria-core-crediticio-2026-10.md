# Contraste de la auditoría del core crediticio — octubre de 2026

Investigación realizada el 28–29/10/2026 sobre main `9106a6e`. Fuente externa:
informe `core-crediticio-arquitectura.md`, fechado el 28/10 y aportado por José.
No se copia el informe ni sus enlaces absolutos de otra máquina. Este documento
conserva el contraste verificable; no certifica cumplimiento ni operación real.

## Resultado

La base decimal, transaccional y de evidencia histórica permite evolucionar el
monolito. Corregir defectos reproducidos antes de ampliar productos. La ausencia de
una función futura no demuestra por sí sola un defecto arquitectónico.

| Hallazgo del informe | Contraste con el código |
| --- | --- |
| F-01, redondeo | Confirmado: 100 al 9 % por 7 días proyecta interés 0.17; `MotorPagos` exige 0.18. No usar interés proyectado como reemplazo del devengo real |
| F-02, capital/saldo negativo | Confirmado: principal 0.06 en 12 cuotas de capital fijo genera capital final -0.05; 10000 al 360 % anual por 60 meses desde 28/02/2026 genera primera amortización -41.46 |
| F-03, proyección relacional | Confirmada limitación de consultas/avance masivo; la capacidad máxima no se midió. No requiere eliminar cifrado de evidencia |
| F-04, cuota base con IVA | No confirmado: `MotorAnticipos::recalcular` obtiene capital + interés en su fallback, sin IVA. Metadato explícito sigue siendo útil |
| F-05, equipos/sucursales | Reformular: equipos son contextos de permisos, no tenants. `CreditoPolicy::view`/`SolicitudPolicy::view` permiten consulta según permisos; no contienen el aislamiento por team descrito por el informe. Revisión de sucursal ya tiene tarea |
| F-06, quincenal | Cada 15 días es comportamiento documentado/probado. Añadir 14 días y 15/fin de mes sin reinterpretar contratos previos |
| F-07, referencia tras reverso | Reserva explícita de ADR 0020 y pruebas. Quitar unicidad y bloquear solo un crédito no resuelve competencia entre créditos |
| F-08, gracia | La parcialidad se rechaza antes de persistir, no después. Restricción funcional aceptada y probada; no deshabilitarla por una reproducción que no corresponde al código |
| F-09, API externa ausente | Capacidad futura; no requisito para Bullet/Revolvente ni prueba de dependencia financiera del frontend |
| F-10, contratos/opciones | Confirmada duplicación de listas entre PHP/Vue y arrays de estado; tipar límites y compartir capacidades según necesidad |

Los hashes de snapshots no son evidencia de una cadena criptográfica completa de
movimientos. El trait de inmutabilidad protege operaciones Eloquent; no sustituye
políticas de integridad, respaldo ni acceso a la base de datos.

## Riesgos adicionales de la ampliación

- Un calendario sintético con capital cero en la primera cuota y 100 al final
  permitió exigir 0.18 de interés al 08/01/2026. Al 10/01 el resumen conservó
  `dias_atraso = 0` porque observa capital vencido: definir atraso de obligaciones
  antes de habilitar Bullet.
- Reversos revolventes deben considerar disposiciones posteriores que hayan usado
  capital liberado por el pago; revisar solo una disposición no conserva cupo.
- El motor devenga por día/cuota; no se ha acreditado capacidad con 10 mil créditos.
- Producto/convenios y reconocimiento de retenciones Nómina necesitan límites
  explícitos; no inferir pago o mora antes de acordar política.

## Evidencia y límites de cobertura

Se ejecutaron dos selecciones backend: 81 pruebas/37357 aserciones y 140/1479,
ambas aprobadas; no presentarlas como total de pruebas únicas. Frontend: 41 pruebas
aprobadas en 13 archivos; el harness mostró advertencias de directiva no resuelta
en algunos montajes. Los experimentos financieros se ejecutaron en memoria, sin
modificar la base operativa. Una matriz adicional de 54 escenarios normales sin
impuestos no divergió; no cubre ni invalida los casos frontera reproducidos.

No se ejecutaron carga ni concurrencia financiera multiconexión ni se inspeccionó
el VPS. La evidencia corresponde a la investigación, no a una futura corrección.

## Fuentes y continuidad

- Código: `TablaAmortizacionCreditoSimple`, `MotorPagos`, `MotorAnticipos`,
  `PagoService`, policies, migraciones y pruebas financieras de main `9106a6e`.
- [Banxico, Circular 21/2009 compilada](https://www.banxico.org.mx/marco-normativo/normativa-emitida-por-el-banco-de-mexico/circular-21-2009/%7B29285862-EDE0-567A-BAFB-D261406641A3%7D.pdf),
  numeral 5.1(f) y 5.4: periodos uniformes, regla para pago único y supuestos de
  Revolvente. No convertir el CAT indiscriminadamente en TIR de días reales.
- [Conversación arquitectónica](https://chatgpt.com/share/6ae2c73f-dbf4-83e8-9198-0f539f67606f).
- [Decisiones recientes](https://chatgpt.com/share/6ae411a7-e304-83e8-89eb-f12405a3d929).
- [Iniciativa y prioridades](https://app.notion.com/p/40961db7db7f819a8af1d7c3cfec2df7).
- [ADR 0032](adr-0032-core-crediticio-extensible.md) y
  [referencia del core](../reference/core-crediticio.md).
