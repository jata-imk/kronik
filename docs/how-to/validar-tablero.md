# Preparar y validar el tablero

Consultar antes el [contrato de cifras y disponibilidad](../reference/tablero-financiero.md).
Usar exclusivamente datos sintéticos para estos recorridos. La entrega no habilita
dinero real, SIC productivo ni políticas faltantes de contratos.

## Despliegue y preparación

1. Confirmar conexión, entorno y respaldo de QA. Revisar la migración
   `2026_11_13_000000_create_credito_lecturas`: crea dos tablas derivadas y un índice
   de pagos. No elimina ni reescribe contratos, pagos o movimientos.
2. Ejecutar las migraciones pendientes y compilar el frontend mediante los
   procedimientos habituales del entorno. No se añaden variables ni seeders
   productivos. El reinicio QA existente incluye las tablas derivadas.
3. Ejecutar `php artisan creditos:proyectar --reconstruir`. El proceso trabaja
   por crédito, conserva avances completos y termina con error si hay incidencias.
   No repetir pagos para preparar el tablero.
4. Ejecutar `php artisan creditos:proyectar --diagnosticar`. No escribe ni audita;
   compara originales, checkpoints, resúmenes y obligaciones. Un contrato sin
   política de mora requiere revisión de su evidencia, no un default nuevo.
5. Mantener el programador Laravel activo. `creditos:proyectar` se ejecuta cada
   cinco minutos, sin solaparse, y usa la zona de la institución. Un fallo invalida
   las lecturas afectadas. Confirmar el estado al cambiar el día o cerrar mes.

`--credito=ID` limita actualización, reconstrucción o diagnóstico a un recurso.
La reconstrucción registra `credito.projections_rebuilt` con resultado global y
sin importes ni snapshots. Una consola sin iniciador conocido se registra como
sistema con contexto desconocido explícito; no se atribuye al usuario conectado.

## Casos de QA

- Comparar tarjetas, detalles y recibos con importes independientes. Separar
  capital financiado del efectivo desembolsado y cobros de utilidad/caja bancaria.
- Verificar día del vencimiento y día siguiente, gracia efectiva/retroactiva,
  interés Bullet vencido con principal futuro y ordinario aún no exigible.
- Registrar pago parcial, anticipo, liquidación y reverso admisible. Comprobar
  cierres afectados, calendario correcto, conservación de centavos y reintentos.
- Cambiar corte sin cambiar periodo; después cambiar periodo sin alterar saldos.
  Revisar seis meses completos y huecos de cierres sin datos.
- Probar sucursal activa, institucional explícita, ausencia de activa, equipo sin
  permisos y cambios rápidos de contexto. No conservar datos del alcance anterior.
- Revisar 320/390/768/1440 px, teclado, zoom 200 %, temas claro/oscuro y cambio de
  énfasis/superficie. Las gráficas ofrecen sus datos en tabla.

## Pruebas y medición reproducible

Backend dirigido: `php artisan test --filter='Tablero|ProyectorLecturaCredito'`.
Frontend: `npm run test:unit`; compilación: `npm run build`.

El recorrido Chromium se ejecuta con `E2E_TABLERO_FIXTURES=true` y
`npm run test:e2e -- tablero.spec.js`. Dos créditos Bullet y un anticipo se
registran mediante servicios reales solo en la SQLite E2E protegida; genera
capturas de escritorio, móvil, oscuro, cambio de paleta y zoom.

La medición explícita usa PHPUnit sobre SQLite `:memory:` con datos sintéticos,
verifica entorno y no toca la BD operativa. En PowerShell:

```powershell
$env:KRONIK_TABLERO_BENCHMARK='1'
$env:KRONIK_TABLERO_VOLUMEN='10000'
$env:KRONIK_TABLERO_DIAS='1'
php artisan test --compact tests/Feature/TableroCapacidadTest.php
```

El artefacto `storage/framework/testing/tablero-capacidad.json` registra volumen,
cuotas, corte sintético, tiempo real de ejecución, construcción, avance diario,
reconstrucción, cinco lecturas, consultas y memoria pico. Replica evidencia
financiera de un escenario simple conservando claves foráneas; no acredita 10 mil
originaciones, una mezcla de productos ni concurrencia. `KRONIK_TABLERO_DIAS`
permite medir mayor antigüedad; la duración crece con días y obligaciones.
El ensayo confirma la transacción envolvente de fixtures en esa BD efímera antes
de medir, para que cada crédito use transacciones raíz y no acumule miles de
savepoints de PHPUnit. No se realiza esta preparación sobre conexiones operativas.

`KRONIK_TABLERO_FASE=lectura` mide exclusivamente la consulta, replicando una
proyección previamente calculada. Su artefacto es `tablero-capacidad-lectura.json`;
construcción, avance y reconstrucción quedan nulos, nunca se atribuyen a ese ensayo.
La ejecución completa publica avance por lotes en `tablero-capacidad-progreso.json`.

MariaDB multiconexión se verifica mediante `tests/Concurrency/bullet.php` en la
base aislada `kronik_test`, también en CI. Registrar los resultados reales y el
entorno en la Agent Note; SQLite y estimaciones no certifican ese comportamiento.
