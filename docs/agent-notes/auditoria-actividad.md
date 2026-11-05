# Matriz de auditoría y actividad

## Referencias

- Notion: [alcance y plan integral](https://app.notion.com/p/21461db7db7f80d5b813c2899522a72b).
- Rama preparada: `feat/cierre-auditoria-actividad`, base `0707ce9`.
- PR de esta entrega: ninguna; no hay código funcional nuevo.
- Matriz única: [eventos de auditoría](../reference/auditoria-eventos.md).
- ADR relacionados: 0028 (cuentas/membresías), 0031 (consulta), 0036 (contexto).

## Objetivo

Completar la cobertura técnica de todos los módulos vigentes y procesos
transversales, reconciliando acciones, emisores y pruebas. El plan detallado y los
criterios por bloque están en Notion; esta nota conserva únicamente el traspaso.

## Estado — 04/11/2026

Plan definido y trabajo pausado por instrucción del usuario antes de implementar.
El usuario primero autorizó completar todo lo pendiente y después indicó terminar
la definición y pausar. Notion queda To Do, con la pausa explícita en su nota;
no se declara auditoría implementada, integrada, desplegada ni validada.

Revisión estática de `0707ce9`. Se confirman faltantes de equipos/roles y accesos
fallidos/logout. La reconciliación incluye 2FA y tokens API habilitados, exportación
CSV, activaciones programadas de productos, estados SIC automáticos y coherencia
transaccional/reintentos de PDF y de otros emisores. No se infiere cobertura por
la mera existencia de un caso del enum o un evento de Jetstream.

## Definición para retomar

1. Equipos/roles/permisos: servicios compartidos, contexto del equipo afectado y
   sucursal original, transacción con auditoría, rutas administrativas/Jetstream,
   Super Admin/delegado y cambios efectivos sin duplicación.
2. Accesos: logout, fallos/limitación/rechazo, 2FA y tokens; conservar ciclos de
   cuentas/invitaciones/contraseñas existentes. Fallos sin identidad autenticada
   son anónimos y no infieren equipo desde el correo introducido.
3. Automatismos: PDF, activación/retiro de versiones y estados SIC; sistema distinto
   de iniciador, contexto persistido incluido null, rollback y reentrega. Capturar
   contexto al programar puede requerir migración aditiva; no está creada.
4. Reconciliar cada acción del inventario vigente con pruebas y la matriz canónica.
   Consultas/filtros/simulación pura sin evento nuevo; exportación CSV auditable
   sin copiar registros ni filtros sensibles y sin afirmar recepción efectiva.
5. Pruebas dirigidas y regresión completa, E2E aislados, documentación/ADR pertinentes,
   review medium por PR y barrido acumulado antes del cierre técnico. Despliegue y
   QA de la nueva entrega se acreditarán por separado cuando ocurran.

Retención, resguardo, integridad/detección de alteraciones y alertas quedan como
seguimiento separado aprobado por el usuario. No cambiar plazos ni habilitar
borrados automáticos. El plan no habilita SIC productivo ni cambia reglas
financieras, Bullet, canales de notificaciones o capacidades del core.

## Evidencia y cierres confirmados

- Sin nuevas pruebas funcionales: esta entrega solo define trabajo y actualiza
  documentación/Notion. No hubo consultas a proveedores ni escrituras de negocio.
- [Contexto de sucursal](contexto-sucursal-transversal.md): usuario confirma el
  04/11/2026 despliegue y QA satisfactorio sin incidencias; tarea Notion completada.
- [Integridad matemática e históricos](core-crediticio-extensible.md): usuario
  confirma revisión satisfactoria; cartera de uno o dos clientes sin crédito
  activo o de prueba, sin tratamiento correctivo requerido. Tarea Notion completada.
  No es una inspección nueva de BD realizada por el agente ni cierra toda la
  arquitectura del core.
- Se conserva el cambio documental previo de Bullet en la Agent Note del core.
- El usuario autoriza commit y push de esta documentación el 04/11/2026 para
  retomar desde otra PC. Esta publicación no reanuda la implementación financiera
  ni de auditoría y no integra cambios en `main`.

## Siguiente paso

Esperar que el usuario reanude. En otra PC, obtener los cambios de `origin` y
seleccionar `feat/cierre-auditoria-actividad`, conservando cualquier cambio local.
Leer esta nota, AGENTS.md y el plan integral de Notion, contrastar rama/diff y
remoto, y ejecutar el plan; no reabrir los dos cierres confirmados ni volver a pedir
definición de políticas excluidas. La rama transporta únicamente documentación;
no hay PR, integración en `main` ni despliegue de esta entrega.
