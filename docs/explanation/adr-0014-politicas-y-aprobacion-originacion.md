# ADR 0014: políticas versionadas y aprobación de originación

- Estado: aceptado para el incremento P3
- Fecha: 2026-09-20

## Contexto

Los productos activados son inmutables y ya tienen usos históricos. Incorporar
reglas de aprobación mutando esas versiones alteraría el significado de revisiones
existentes. Los dictámenes por sí solos no satisfacen todos los requisitos.

## Decisión

Versionar `OriginacionPolitica` por versión de producto, separada de sus condiciones
financieras. Guardar exige permiso específico, confirmación y referencia de la
validación interna del operador. No seleccionar modalidad/SIC/límite/vigencia por
defecto. La revisión de solicitud conserva política, número y hash al enviarse.
Una política nueva exige devolver y reenviar solicitudes anteriores antes de aprobar.

La política define modalidad individual/dual, SIC requerido/manual permitido,
documentos, monto máximo, días de vigencia y criterio humano de capacidad. No
introduce un score ni un umbral financiero calculado. El límite técnico de vigencia
es 1–365 días y se requiere al menos un tipo documental: no son plazos ni checklist
legales universales. La aplicabilidad real debe validarse fuera de este formulario.

`SolicitudRequisitosService` es la fuente de comprobaciones del detalle y de la
acción transaccional. El permiso no omite invariantes: en dual no aprueba quien haya
creado, modificado captura o enviado la solicitud, incluso si es Super Admin.
Los requisitos SIC integrado siguen bloqueados hasta integrar un proveedor válido.

La huella del expediente incluye datos evaluados, identidad fiscal, domicilio y
metadatos de documentos actuales; cambios exigen renovar dictámenes. Los cambios
materiales de identidad respecto a la revisión exigen nueva revisión de solicitud.
Las escrituras de cliente/documentos se serializan por cliente; aprobar bloquea
solicitud → cliente → versión de producto. No afirmar concurrencia multiconexión
probada solo por usar bloqueos y SQLite.

La resolución conserva revisión, política, dictámenes utilizados, documentos y
vigencia, sin modificar dictámenes previos. La vigencia incluye el día de aprobación
como primer día. Una aprobación puede devolverse para nueva revisión o cancelarse
antes de formalización; ninguna de esas acciones borra su evidencia.

## Habilitación y consecuencias

`ORIGINACION_APROBACIONES_HABILITADAS=false` es el valor entregado. Solo el booleano
explícito `true` permite evaluar habilitación; la bandera nunca sustituye requisitos
ni valida por sí misma al operador. No se modifica `.env` ni se habilita dinero real.
Primera aprobación limitada a persona física; perfil del operador, impuestos,
contratos y metodología siguen requiriendo validación especializada.

P4 debe volver a comprobar vigencia y evidencia antes de formalizar. No reutilizar
el simple estado `aprobada` como permiso para firmar o desembolsar. La expiración se
presenta como bloqueo informativo, conservando el estado y la historia de resolución.
