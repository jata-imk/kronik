# ADR 0029: Estatus global con efecto operativo

## Estado

Aceptado por el usuario el 2026-10-26; implementación en curso.

## Contexto

El estatus solo validaba datos al activar. Un bloqueo general de escritura
impediría atender la cartera existente y no protegería trabajos ya encolados.

## Decisión

Centralizar capacidades en servicios y workers, comunicadas por Inertia.
Borrador permite captura/simulación; Suspendida detiene toda originación,
incluidas altas de clientes. Cobranza, mantenimiento y consulta histórica siguen
disponibles. Super Admin tampoco omite las invariantes. Exigir RFC y correo en
documentos finales; capturar contexto de cola y distinguir sistema e iniciador.
Permitir reporteo futuro de cartera previa en Suspendida sin decidir aplicabilidad
regulatoria. Contrato en [estatus operativo](../reference/estatus-operativo-empresa.md).

## Consecuencias

Migración aditiva de contexto documental, sin inventar historia. Bloquear empresa
antes que recursos. Fixtures de originación requieren empresa activa explícita.
Los permisos y habilitaciones QA permanecen independientes.
