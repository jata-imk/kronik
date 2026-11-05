# ADR 0037: cobertura técnica de auditoría

## Estado y contexto

Decisión implementada localmente el 05/11/2026, sujeta a las validaciones y revisión
registradas en la [Agent Note](../agent-notes/auditoria-actividad.md). Continúa los
ADR [0028](adr-0028-acciones-cuenta-membresia.md),
[0031](adr-0031-consulta-actividad-equipo-sucursal.md) y
[0036](adr-0036-alcance-consulta-sucursal.md). No los sustituye.

La cobertura anterior contenía emisores parciales, operaciones con actividad fuera
de su transacción y automatismos sin contexto de programación. Un evento Jetstream
o un caso del enum no prueban persistencia. El plan de Notion exige reconciliar
todos los módulos y procesos transversales sin ampliar reglas financieras.

## Decisión

- Mantener ActivityEvent/ActivityLogService y una sola
  [matriz por acción](../reference/auditoria-eventos.md). No introducir observers
  globales que auditen escrituras técnicas, seeders o cálculos como negocio.
- Compartir servicios de equipos/roles entre administración y Jetstream. Las
  transacciones incluyen actividad; se comparan cambios efectivos y se restaura
  el contexto temporal de permisos. Crear un equipo registra las copias iniciales
  de roles como creaciones distintas, sin duplicar su asignación inicial de permisos.
- En accesos no autenticados, conservar actor/sujeto/equipo/sucursal null. No usar
  el correo introducido para atribuir un intento. La consulta global autorizada
  muestra **Anónimo** y conserva la distinción respecto del sistema ejecutor.
- Registrar login después de los controles y sesión preparada; registrar 2FA
  completado después del guard, validando estado de cuenta y material 2FA actual
  bajo bloqueo, incluido el usuario memoizado por Fortify. La sesión
  provisional de una cuenta inactiva se cierra incluso si falla su auditoría.
- Mantener las rutas originales de Fortify/Jetstream. Una frontera transaccional
  específica de seguridad compara cambios efectivos de 2FA, tokens y verificación
  de correo, sin copiar secretos, capacidades ni nombres personalizados al log.
- Los comandos de permisos/roles tienen contexto explícito de sistema, sin usuario
  supuesto. La opción de equipo se valida; los roles globales y por equipo no se
  confunden. Cache y restauración de contexto no producen eventos adicionales.
- Persistir tres IDs nullable de programación en producto_versiones. Capturar el
  usuario iniciador y contexto cuando programa; no inferir históricos desde el
  creador. No usar cascadas que destruyan la referencia histórica. El sistema
  ejecutor conserva causer null; la versión y related conservan recurso/iniciador.
- Serializar activaciones/retiros por producto y revalidar cada versión bajo
  bloqueo. Las transiciones SIC y los resultados PDF comparten transacción con su
  actividad y no duplican resultados ante reentrega.
- Preparar el CSV completo antes de registrar su generación. El stream temporal
  evita incluir el propio evento sin conservar filas ni filtros en metadatos.
  Generado no significa recibido por el usuario.

## Consecuencias y compatibilidad

Los códigos existentes, históricos sin clasificar y códigos desconocidos se
conservan. Los códigos nuevos y la migración aditiva se documentan en la matriz.
La activación programada ahora tiene código propio; el código de activación
acredita una transición efectiva. No se reescriben eventos anteriores.

La atomicidad es de base de datos: el filesystem no comparte su transacción.
Escribir un PDF no acredita éxito hasta confirmar estado y evento; un fallo puede
dejar bytes sin resultado confirmado y será tratado por el reintento existente.
Se mantiene la protección de originales contractuales con huella registrada.
Eliminar archivos de clientes ocurre después del commit, para conservar evidencia
ante rollback de la eliminación del dominio.

Aplicar la migración antes de reanudar workers y programador de la nueva versión.
No hay seeders, variables ni permisos nuevos. Concurrencia real se verifica con
conexiones independientes sobre MariaDB aislada, además de pruebas Pest de
rollback, privacidad y contexto; los E2E usan fixtures y BD dedicada.

La regla de lectura del ADR 0031 sigue vigente: sucursal como filtro del equipo
autorizado, sin imponer asignaciones operativas al auditor ni cambiar preferencias.
Retención, resguardo, detección de alteraciones y alertas son seguimiento separado
aprobado. Esta entrega no acredita conformidad legal, SIC productivo ni dinero real.
