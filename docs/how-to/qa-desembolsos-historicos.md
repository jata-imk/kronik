# QA: desembolsos pasados y pagos cronológicos

## Preparación y límites

Usar una instalación QA y personas, comprobantes y documentos sintéticos. Registrar commit, entorno, fecha institucional y navegador. El operador mantiene permisos de Producto, Solicitudes, Documentos y Créditos en su equipo y la sucursal activa responsable; el E2E también usa una cuenta sin bandera Super Admin. No ejecutar transferencias ni usar evidencias reales.

La migración aditiva `2026_11_11_100000` crea control operativo de captura. Aplicarla por el procedimiento autorizado del entorno; no reiniciar BD ni usar migrate:fresh. Las banderas existentes de aprobación, paquete, firma, desembolso, pagos y anticipos deben corresponder a QA. No hay variables ni permisos operativos nuevos.

Contrato canónico: [fechas efectivas y captura](../reference/captura-historica-creditos.md). La formalización actual de un escenario pasado no acredita que la firma, aprobación o configuración existieran entonces.

## Recorrido por interfaz

1. Crear dos productos Bullet sintéticos separados: 12000 MXN, ordinario 24 %, moratorio 36 %, Act/360, tres periodos mensuales, gracia efectiva de tres días, ambos intereses, base moratoria obligación vencida, sin comisiones y fiscalidad no_causa para ambos conceptos. Estos valores aíslan aritmética; no prescriben configuración comercial.
2. En uno elegir intereses periódicos y en otro al vencimiento. Activar sus borradores con vigencia 01/09/2026 y confirmar el uso histórico QA. La activación debe mostrar el momento real; consultar después la misma versión y comprobar que es inmutable.
3. Simular disposición 01/09/2026 y tres periodos. Periódicos: 240 el 01/10, 248 el 01/11 y 12240 el 01/12. Al vencimiento: cero en los intervalos y 12728 al 01/12. Fórmula independiente: 12000 × 24 × días / 36000, con 30, 31 y 30 días.
4. Capturar solicitud con esa versión y fecha; enviar, dictaminar y aprobar conforme a la política. Generar contrato/PDF, abrir y descargarlo, recibir copia firmada sintética y aceptar con fecha real de firma. Verificar fecha efectiva de tabla y conservar el PDF original. Una fecha distinta requiere devolver y formalizar otra vez.
5. Registrar desembolso con la fecha firmada y confirmación histórica explícita. Revisar que no ejecuta una transferencia nueva. En crédito y listado debe aparecer captura pendiente; fecha efectiva 01/09 y fecha de registro real son distintas. El saldo todavía es provisional.
6. En periódicos, registrar 100 el 01/10 y 50 el 03/10. Revisar distribución y confirmar cada pago, conservando referencias sintéticas únicas. En al vencimiento no hay interés exigible antes del 01/12: registrar anticipos de 3120 el 16/09 y 1000 el 17/09, sin forzarlos por la ruta de pago ordinario.
7. Comparar el estado con la tabla independiente siguiente. Revertir solo la última operación admisible; conservar recibo original, compensación y versiones de calendario. Descargar otra vez el PDF original y verificar que conserva los mismos bytes.
8. Al terminar, confirmar que se incorporaron todos los pagos conocidos y completar captura desde el detalle. Repetir el mismo envío no debe duplicar cierre ni actividad. Completar no exige saldo cero, no agrega movimientos financieros ni envía avisos acumulados. Revisar escritorio/móvil y lectura con una cuenta sin permiso de pago.

## Oráculo a 11/11/2026

Para otros días cambia el corte del cálculo; conservar las fechas efectivas reales. No modificar el reloj de QA para copiar esta tabla.

| Resultado | Periódicos, tras los dos pagos | Al vencimiento, tras los dos anticipos |
| --- | --- | --- |
| Capital pendiente | 12000.00 | 7880.00 |
| Capital vencido | 0.00 | 0.00 |
| Interés vencido | 338.00 | 0.00 |
| Interés todavía no exigible | 80.00 | 414.85 |
| Mora | 5.16 | 0.00 |
| Exigible | 343.16 | 0.00 |

Periódicos: quedan 240 − 100 − 50 = 90 del primer vencimiento y 248 del segundo. Gracia efectiva: 38 días sobre 90 generan 3.42 y siete sobre 248 generan 1.74; redondear por obligación, no cada día. El ordinario de noviembre todavía no exigible es 12000 × 24 × 10 / 36000 = 80. Después de revertir el pago de 50, el primer remanente es 140 y su mora 5.32: exigible total 395.06.

Al vencimiento: los primeros 15 días generan 120 conservados; 3120 reducen capital a 8880. El día siguiente genera 5.92; el segundo anticipo reduce capital a 7880. Hasta el 11/11 transcurren otros 55 días: (1200000 × 24 × 15 + 888000 × 24 + 788000 × 24 × 55) / 36000 = 41485.333… centavos, redondeados a 414.85. No vence antes del 01/12. Al revertir el segundo anticipo, quedan capital 8880 e interés conservado/no exigible 120 + 56 × 5.92 = 451.52.

Para reproducir en meses futuros, usar el primer día de dos meses antes como disposición y tres vencimientos mensuales. El E2E calcula sus oráculos con días civiles e importes enteros en centavos, sin importar el motor ni tomar cifras del PDF como resultado esperado.

## Fronteras, seguridad y auditoría

- Rechazar fecha de desembolso futura, diferente de la firmada o anterior a vigencia; rechazar vigencia histórica sin confirmación o puerta QA. No retirar la activa si se intenta una vigencia repetida.
- Rechazar pago anterior al desembolso, futuro o anterior a movimientos posteriores, incluidos reversos. Admitir operaciones secuenciales del mismo día cuando cumplan las demás reglas.
- Clonar condiciones para gracia retroactiva, sustitución y las otras bases. Ver [casos Bullet](../reference/bullet-casos-definicion.md): no cambiar contratos existentes para variar el caso. Durante B+sustitución, el parcial aplicado a la obligación en gracia sigue bloqueado; cubrirla completa sigue permitido.
- Probar vencimiento de interés sin capital vencido, día dentro de gracia y primero posterior, cese del disparador al cubrir obligación, anticipo/liquidación/reapertura y regresión simple/hoy. Las pruebas aisladas fijan el reloj únicamente para estas fronteras exactas.
- Revisar actividad de desembolso, pago, reverso y cierre: un evento por operación, actor/sujeto y contexto correctos; sin importes, referencias, motivos ni documentos en metadatos generales. La consulta no crea devengos ni captura operativa.

## Automatización aislada

Desde el worktree de la entrega, después de instalar dependencias y compilar:

```powershell
npm run build
$env:E2E_HISTORICO_FIXTURES = "true"
$env:E2E_FRESH_DATABASE = "true"
$env:E2E_BULLET_FIXTURES = "false"
node tests/E2E/run.js historico-ciclo.spec.js --output=test-results-historico
```

Puede indicarse DOCUMENTOS_CHROME_PATH con el Chrome de pruebas instalado. El runner crea SQLite nueva por ejecución mediante apertura exclusiva y usa migrate/seed sobre esa base vacía. Rechaza rutas externas, archivos existentes, combinación con reloj Bullet y public/hot en modo de base nueva; no elimina archivos preexistentes. Los fixtures y esa variable son exclusivos de pruebas, no configuración operativa.

Regresión: desactivar E2E_HISTORICO_FIXTURES, mantener E2E_FRESH_DATABASE=true y ejecutar por separado Bullet con E2E_BULLET_FIXTURES=true y aprobación dual con E2E_ORIGINACION_DUAL=true. Retirar la bandera del escenario anterior. El reloj Bullet permanece exclusivo del harness.

Pest cubre fronteras y consulta sin escritura; Vitest, confirmaciones/teclado/cambio de recurso y fechas civiles. CI comprueba también concurrencia multiconexión MariaDB; SQLite local no la acredita. La medición de consulta de 30/365/1825 días conserva tiempos/memoria/consultas del entorno observado, sin SLA ni validación de 10 mil créditos.

Estado y evidencia: [Agent Note](../agent-notes/desembolsos-historicos.md). Distinguir implementación, integración, despliegue y QA compartido; la prueba local no sustituye la aceptación del usuario.
