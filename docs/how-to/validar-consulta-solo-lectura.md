# Validar consulta de solo lectura

Usar SQLite en memoria para backend y la BD E2E aislada para navegador. Nunca
preparar este recorrido con reset o seeders sobre datos operativos.

1. Crear datos sintéticos y perfiles de lectura, escritura y sin acceso, además
   de Super Admin. Comprobar el equipo Spatie y su restauración ante rechazo.
2. Abrir por URL/ID una versión fuera de la primera página y del filtro visible.
   Confirmar identidad, estado, campos completos y 404 con ID ajeno/inexistente.
3. Consultar borrador, utilizado, activa, programada y retirada según el módulo.
   Editar solo donde el dominio lo permite. No exigir escritura para leer.
4. Probar Enter, atajos, pegado, arrastre, controles e inserción/subida de recursos:
   no deben cambiar datos ni enviar mutaciones en consulta. GET no crea recursos.
5. Cerrar/reabrir y alternar recursos/modos. Retardar una respuesta anterior y
   comprobar que no sobrescribe la selección nueva. Repetir con recarga parcial,
   atrás/adelante y cambio de equipo/sucursal conforme a PR #66.
6. Comprobar datos históricos sin defaults, relaciones inactivas y permisos de
   campos reservados. Mantener PDF/descargas autorizados sobre la versión exacta.
7. Repetir en escritorio y móvil de 390 px. Conservar capturas sintéticas, trazas
   y consola cuando haya fallos. Verificar foco, cierre y navegación de pestañas.
8. Comparar datos, hashes y auditoría antes/después: consulta sin mutaciones,
   eventos de lectura existentes conservados y ningún éxito ante escritura rechazada.

[Contrato e inventario](../reference/consulta-edicion-historicos.md).
