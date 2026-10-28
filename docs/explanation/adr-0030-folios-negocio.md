# ADR 0030: Identificadores de negocio y ámbitos de numeración

## Estado

Aceptado: decisiones de producto confirmadas por el usuario el 2026-10-26;
implementación iniciada el 2026-10-27.

## Contexto

Los cuatro contadores de sucursal no se utilizaban. Solicitudes, contratos,
créditos y recibos se identificaban con IDs técnicos. Numerar después de firmar
no permitiría que el contrato conservado incluyera su número. Usar IDs físicos
de sucursal para todas las reservas permitiría reutilizarlas si QA recrea filas.

## Decisión

Numeración continua por sucursal de origen para los cuatro tipos. Prefijo y
siguiente número configurables en Sucursales, con formato acotado y seis dígitos
mínimos. El contrato se numera al confirmar la preparación. Asignación y reserva
pertenecen a la transacción de creación; se respaldan con locks y unicidad en BD.
Los IDs siguen siendo claves técnicas y no cambian las rutas existentes.

Un ámbito estable conserva prefijos históricos y secuencias independientemente
del ID físico de sucursal. La recreación controlada por QA recupera el ámbito
de la clave original, sin liberar números. Cambiar clave/prefijo no renumera
registros. Los contadores heredados sirven únicamente para importar.

El folio visible lleva guiones. La exportación futura los retirará; una clave
derivada en BD detecta colisiones ahora sin crear otra cuenta pública editable.
Completar historia conserva documentos y snapshots y señala la asignación tardía.
No se habilita reporteo SIC ni se certifica su compatibilidad contratada.

## Consecuencias

La configuración tiene versión y guardado independiente de datos generales.
El ledger y sus reservas sobreviven al reinicio QA. La reversión del esquema
con folios emitidos no es destructiva: necesita respaldo consistente. Se requiere
concurrencia real en MariaDB; SQLite no demuestra esta propiedad. Contrato,
operación e inventario posterior en [Folios](../reference/folios.md).

Las transacciones exteriores que emiten folios reintentan deadlocks hasta cinco veces, porque los locks de usuarios/recursos y las claves foráneas pueden cruzarse con la numeración. No se reintenta solo un fragmento dentro de una transacción abortada. Las creaciones directas restauran su estado Eloquent antes de repetir. El CI fuerza este cruce con procesos independientes.
