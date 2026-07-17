# Definición de Terminado

## Regla

Una tarea no se marca como completada si no tiene pruebas aplicables pasando y documentación actualizada.

## Criterios

- El comportamiento esperado está implementado.
- Las pruebas automatizadas aplicables pasan.
- La documentación Diataxis correspondiente está actualizada.
- Los datos demo o fixtures necesarios están disponibles si el flujo se valida manualmente o con e2e.
- Los permisos, migraciones, seeders, comandos y variables de entorno afectados están documentados.
- La tarea indica explícitamente si no requiere tests automatizados y por qué.

## Matriz de Pruebas

| Tipo de cambio | Prueba mínima esperada |
|---|---|
| Controller, request, policy o servicio Laravel | Feature o Unit test en Pest/PHPUnit |
| Comando Artisan | Feature test de comando y efectos en BD cuando aplique |
| Seeder crítico | Test o validación automatizada de datos mínimos esperados |
| Migración | Test de estructura o prueba funcional que dependa de la nueva estructura |
| Componente Vue con lógica | Test de componente cuando exista harness frontend |
| Flujo de usuario crítico | E2E con Playwright |
| Cambio visual menor | Screenshot/manual QA documentado si no hay lógica |
| Documentación o investigación | Revisión de enlaces, fecha de consulta y trazabilidad; no requiere test automatizado |

## Documentación Requerida

- Tutorial: cuando cambia onboarding o flujo guiado.
- How-to: cuando cambia una operación concreta.
- Reference: cuando cambian comandos, variables, seeders, rutas, permisos o estructura.
- Explanation: cuando cambia una decisión técnica o regla de negocio.
- TODO: cuando aparece, se cancela o se completa trabajo pendiente.

## Evidencia

Al cerrar una tarea, registrar:

- Comandos de prueba ejecutados.
- Resultado de pruebas.
- Documentos actualizados.
- Notas de riesgo residual, si existen.
