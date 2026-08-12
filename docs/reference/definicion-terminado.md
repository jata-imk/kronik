# Definicion de Terminado

## Regla

Una tarea no se marca como completada si no tiene pruebas aplicables pasando y documentacion actualizada.

## Criterios

- El comportamiento esperado esta implementado.
- Las pruebas automatizadas aplicables pasan.
- La documentacion Diataxis correspondiente esta actualizada.
- Los datos demo o fixtures necesarios estan disponibles si el flujo se valida manualmente o con e2e.
- Los permisos, migraciones, seeders, comandos y variables de entorno afectados estan documentados.
- Todos los errores visibles usan mensajes claros en espanol; ninguna clave `validation.*` llega a la interfaz.
- La tarea indica explicitamente si no requiere tests automatizados y por que.
- La suite completa no debe quedar roja por pruebas existentes si el cambio agrega CI que la va a ejecutar.
- Los E2E deben usar exclusivamente la base aislada y no depender de servicios externos para producir un resultado determinista.

## Matriz de Pruebas

| Tipo de cambio | Prueba minima esperada |
|---|---|
| Controller, request, policy o servicio Laravel | Feature o Unit test en Pest/PHPUnit |
| Comando Artisan | Feature test de comando y efectos en BD cuando aplique |
| Seeder critico | Test o validacion automatizada de datos minimos esperados |
| Migracion | Test de estructura o prueba funcional que dependa de la nueva estructura |
| Componente Vue con logica | Test de componente cuando exista harness frontend |
| Flujo de usuario critico | E2E con Playwright |
| Cambio visual menor | Screenshot/manual QA documentado si no hay logica |
| Documentacion o investigacion | Revision de enlaces, fecha de consulta y trazabilidad; no requiere test automatizado |

## Evidencia

Al cerrar una tarea, registrar comandos ejecutados, resultado, documentos actualizados y riesgo residual.
