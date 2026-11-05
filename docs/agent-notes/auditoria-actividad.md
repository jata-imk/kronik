# Matriz de auditoría y actividad

## Referencias

- Notion: [alcance y plan integral](https://app.notion.com/p/21461db7db7f80d5b813c2899522a72b).
- Rama: `feat/cierre-auditoria-actividad`, continuidad `80b2f92`, base `0707ce9`.
- PR de esta entrega: [#72](https://github.com/jata-imk/kronik/pull/72), borrador;
  implementación principal `3868b1c`, cierre de regresiones en curso.
- Matriz única: [eventos de auditoría](../reference/auditoria-eventos.md).
- ADR relacionados: 0028 (cuentas/membresías), 0031 (consulta), 0036 (contexto), [0037](../explanation/adr-0037-cobertura-tecnica-auditoria.md) (cobertura técnica).

## Objetivo

Completar la cobertura técnica de todos los módulos vigentes y procesos
transversales, reconciliando acciones, emisores y pruebas. El plan detallado y los
criterios por bloque están en Notion; esta nota conserva únicamente el traspaso.

## Estado — 05/11/2026

Implementación reanudada por autorización explícita del usuario. `git fetch origin`
ejecutado y rama de continuidad seleccionada desde `80b2f92`; se conservan los
archivos locales sin seguimiento. Notion: Doing. PR #72 publicada en borrador, sin integración ni despliegue nuevos.

Implementados localmente equipos/roles, accesos/2FA/tokens, CSV, contexto de
programación, transiciones automáticas SIC y coherencia transaccional de PDF,
productos, plantillas y operaciones del expediente. Reconciliación, ampliación de
pruebas y revisión final en curso. No declarar cierre técnico todavía.

Validaciones en curso: suite MariaDB aislada y concurrencia de productos/folios
aprobadas en el barrido anterior; regresiones de seguridad posteriores aprobadas.
Frontend: 140 pruebas y build aprobados. E2E core: 8 aprobados; documentos,
productos y SIC: 6 aprobados. CI de `3868b1c` aprobada en GitHub Actions.
Revisión medium del commit `832d922` contra `0707ce9` completada tras
un primer intento interrumpido por cuota. Dos hallazgos P2: recuperación del
fallo terminal al regenerar PDF común y detección de cambios solo de vigencia.
Corregidos con regresiones; revisión del siguiente commit pendiente.
Cierre de seguridad: 29 pruebas, 360 aserciones; recorrido dual y revisión del
commit final en curso. Barrido manual acumulado: se corrigió la comparación
de producto con instancia obsoleta y se alineó el orden de bloqueos producto →
versión; su regresión conserva un único evento ante reenvío concurrente.
También se corrigieron la atomicidad del traslado de cliente y los eventos de
menú sin cambios efectivos. Regresión dirigida: 83 pruebas, 845 aserciones.
Hook de `832d922`: MariaDB 693 pruebas, una omitida, 43 753 aserciones; build
aprobado. El E2E dual se repite aislado tras restaurar Browsershot del lockfile
y comprobar Chrome: un intento anterior agotó el timeout de arranque de 10 s
durante la ejecución paralela de pruebas completas. No se cuenta como aprobado. Los conteos definitivos
se registrarán con el commit que corresponda. Las dependencias exactas se
restauraron mediante npm ci con espejo, conservando lockfile e integridad.

Captura de consulta de un intento anónimo, con fixtures aisladas:
[detalle sin identidad atribuida](../assets/auditoria-actividad/actividad-anonima.png).

## Bloques autorizados

1. Equipos/roles/permisos: servicios compartidos, contexto del equipo afectado y
   sucursal original, transacción con auditoría, rutas administrativas/Jetstream,
   Super Admin/delegado y cambios efectivos sin duplicación.
2. Accesos: logout, fallos/limitación/rechazo, 2FA y tokens; conservar ciclos de
   cuentas/invitaciones/contraseñas existentes. Fallos sin identidad autenticada
   son anónimos y no infieren equipo desde el correo introducido.
3. Automatismos: PDF, activación/retiro de versiones y estados SIC; sistema distinto
   de iniciador, contexto persistido incluido null, rollback y reentrega. Capturar
   contexto al programar mediante migración aditiva nullable, sin backfill.
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

## Evidencia histórica del traspaso y cierres confirmados — 04/11/2026

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

Completar reconciliación por acción y regresiones de emisores existentes, resolver
validación frontend/build/E2E, actualizar contrato/matriz y ADR, publicar la PR y
completar review medium y barrido acumulado. Conservar los cierres de sucursal e
integridad ya confirmados. Registrar el estado real de cada validación; ninguna
prueba ni revisión pendiente puede darse por aprobada por inferencia.
