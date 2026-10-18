# ADR 0024: reinicio integral de una instalación QA

Estado: implementado, pendiente de revisión e integración.

## Contexto

El reinicio de desarrollo anterior no cubría los módulos de originación, cartera, documentos ni evidencias SIC añadidos posteriormente. Usaba `TRUNCATE` con restricciones desactivadas y dejaba archivos privados sin tratar. Para preparar la siguiente ronda de colaboradores se necesita eliminar todos los datos de prueba conservando un acceso global, un equipo y Matriz.

## Decisión

- `dev:reset-data` delega en un servicio de mantenimiento independiente de los servicios financieros. Se mantienen las restricciones de inmutabilidad y conservación de los modelos en los flujos normales.
- El alcance es explícito: cada tabla está clasificada como reiniciable o conservada. Una nueva tabla sin clasificar bloquea el comando hasta revisar su política y dependencias.
- Se usa una transacción con eliminaciones ordenadas y claves foráneas activas; no se reinician secuencias. Se rompe únicamente la referencia de reversos que impide eliminar el conjunto de pagos QA.
- El modo `--minimal` conserva una cuenta global existente, activa y verificada, con contraseña y 2FA. Conserva su primer equipo propio, Matriz y empresa; crea solo los contextos faltantes, sin usuarios ni datos demo.
- Se exige una declaración explícita `--confirm-qa`; se bloquean operaciones marcadas como reales y se exige mantenimiento fuera de local/testing. Los registros antiguos sin marcador no prueban que sean ficticios: corresponde al operador confirmar el carácter exclusivamente QA de la instalación. `APP_ENV=production` solo admite modo mínimo para ese caso.
- Los archivos se validan antes del borrado. El commit incluye un registro duradero en `development_data_resets` con conteos, operador, cuenta conservada, modo y manifiesto cifrado. La eliminación de archivos ocurre después del commit y puede reanudarse con el mismo UUID sin repetir cambios operativos.
- El historial de mantenimiento se conserva aunque se retire la actividad operativa QA. No guarda contraseñas ni respuestas SIC; los pendientes están cifrados con la clave de la aplicación.

## Consecuencias

Un fallo de base revierte sus cambios y deja los archivos intactos. Un fallo posterior de almacenamiento no puede revertir el commit: el comando informa que la base ya se reinició, devuelve error y conserva los pendientes para reintentar. Un siguiente reinicio queda bloqueado hasta resolverlos.

El operador necesita respaldo coherente de base, archivos y clave de cifrado, detener todos los escritores y conservar la configuración de discos durante la limpieza. El bloqueo de proceso solo coordina un nodo. El comando no sustituye un mecanismo de retención de datos reales ni una purga de servicios externos.

La estructura actual soportada usa sesiones, colas y actividad en la misma base (o almacenamiento efímero/síncrono en pruebas). Otras configuraciones deben ampliar de forma explícita el procedimiento. Los archivos huérfanos, logs, respaldos y exportaciones requieren revisión separada.

Operación y recuperación: [Reiniciar datos de desarrollo o QA](../how-to/reset-datos-desarrollo.md).
