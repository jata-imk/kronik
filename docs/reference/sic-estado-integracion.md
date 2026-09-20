# Estado de la integración SIC

Desde el saneamiento P1 (2026-09-20), las consultas heredadas están cerradas,
incluido el acceso directo a sus servicios/repositories. No hay flag de activación.
Los SDK se conservan como referencia para el adaptador contratado de P7; no son
una integración productiva validada. Véase [ADR 0011](../explanation/adr-0011-integracion-sic-segura.md).

## Acceso y navegación

- Consultas SIC exige `read historial-crediticio` y `read clientes`.
- Preparación de consulta exige `create circulo-credito` y acceso al cliente;
  al especificar cliente se exige además su permiso de actualización/sucursal.
- La pantalla de preparación informa el bloqueo y no envía datos ni genera costos.
- El histórico conserva proveedor, servicio, fecha y estado registrado, con
  búsqueda, filtro y paginación. No muestra respuestas ni errores crudos, score,
  comparativas, transacciones o recomendaciones demostrativas.
- Procedencia de los históricos: **no verificada**. No usarlos para aprobación.
- No se puede eliminar desde ClienteService un cliente con consultas SIC.
- No se modifican ni cifran retroactivamente los JSON existentes en este incremento.
  Restringir copias/acceso a BD; la migración cifrada pertenece a P7.

## Inventario preservado

`app/Services/SICs/CirculoDeCredito/` conserva SDK FicoScorev2, FintechScore y
RCFicoScore. Sus seis puntos de entrada Service/Repository ahora rechazan ejecución.
`Sic`, `SicApi`, `SicQuery` y `SicQueryResult` se conservan; no se elimina evidencia.
`SicsSeeder` conserva siete servicios de catálogo: no implica que estén disponibles.
El consentimiento privado existente continúa accesible en el expediente; no
habilita por sí solo el adaptador. No hay llamadas al proveedor en pruebas.

## Reanudación productiva

Requiere contrato/API, datos reales, consentimiento ligado a operación, firma y
verificación, TLS, estados inciertos, conciliación e idempotencia/costos. Se probará
con fakes y validación del proveedor antes de abrir ejecución. No volver a conectar
el controlador a los SDK de sandbox ni reintroducir personas de demostración.

## Navegación general

`NavigationService` filtra por Gate cada entrada; Vue solo renderiza el resultado.
Productos y documentos no dependen de `access admin`. Se conserva el Menubar
contextual. Los ejemplos Sakai siguen en su ruta de desarrollo, no en sidebar.
No requiere migraciones ni seeders nuevos; revisar asignaciones del permiso SIC
existente a los perfiles que realmente necesitan leer esos datos.
