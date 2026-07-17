# TODO 09: Calidad, Tests y Definición de Terminado

## Objetivo

Evitar marcar funcionalidades como terminadas sin evidencia automatizada y documentación actualizada.

## Criterio General

Una tarea funcional o técnica se considera terminada solo si cumple la [Definición de terminado](../reference/definicion-terminado.md).

## TODO

- [ ] Definir matriz de pruebas por tipo de cambio: backend, frontend, e2e, comandos, seeders y documentación.
- [ ] Agregar pruebas backend para servicios, requests, policies, controllers, seeders críticos y comandos Artisan.
- [ ] Agregar pruebas frontend para componentes Vue con lógica propia, formularios, validaciones y estados de UI.
- [ ] Agregar pruebas e2e para flujos críticos: login, navegación, clientes, roles/permisos, reset de datos, historial crediticio y futuros módulos de originación.
- [ ] Definir fixtures/datos demo estables para e2e basados en `php artisan dev:reset-data`.
- [ ] Documentar cómo correr pruebas por capa en `docs/reference/`.
- [ ] Integrar ejecución mínima de tests en el flujo previo a commit o PR.
- [ ] Registrar en cada tarea del backlog qué pruebas mínimas la desbloquean para marcarse como completada.
