# Estatus operativo de la empresa

La configuración es única por instalación. `EmpresaOperacionService` concentra
las decisiones de `OperacionEmpresa`. Se aplican también a Super Admin y API.

| Acción | Borrador | Activa | Suspendida |
| --- | --- | --- | --- |
| Configurar, simular y previsualizar con datos sintéticos | Sí | Sí | Sí |
| Crear clientes y capturar/editar borradores de solicitudes | Sí | Sí | No |
| Enviar, dictaminar y aprobar solicitudes | No | Sí | No |
| Documentos finales, consultas SIC, recibir/aceptar firmas y desembolsar | No | Sí | No |
| Mantener expedientes, consultar historia, cancelar/devolver/rechazar | Sí | Sí | Sí |
| Cobrar, anticipar, liquidar, reversar y consultar comprobantes | Sí | Sí | Sí |

Sin configuración se considera Borrador. La activación sigue exigiendo los datos
del [contrato de configuración](configuracion-empresa.md). Generar documentos
finales exige razón social, RFC válido y correo aunque la plantilla no los use;
no obliga a insertarlos en el contenido. Los límites y habilitaciones QA siguen
vigentes. Activar la empresa no habilita proveedores productivos ni dinero real.

## Interfaces y colas

Inertia comparte `empresaOperacion`: `estatus`, `motivo` y `capacidades` por código
del enum. El banner enlaza configuración con `read configuracion-empresa`. La
directiva `empresa-operacion` deshabilita acciones sin sustituir las demás
condiciones. Backend devuelve errores españoles bajo `empresa`.

Los servicios leen el estado dentro de la transacción con bloqueo compartido;
la actualización de configuración usa bloqueo exclusivo. Los workers comprueban
otra vez antes del efecto: una consulta preparada se cancela sin enviar y un PDF
pendiente queda fallido con `EmpresaNoOperativa`. La redelivery no procesa de nuevo
ese bloqueo. Reactivar no reenvía automáticamente trabajos cancelados. No se
deshacen consultas ya enviadas ni se modifican documentos generados.

Nuevos documentos guardan `team_id` y `sucursal_id` al solicitarse; antiguos
conservan contexto desconocido, sin inferir preferencias actuales. Migrar antes
de ejecutar workers.

## Auditoría y política futura

`empresa.operacion_bloqueada` registra capacidad, resultado y estatus sin datos
fiscales ni contenido. En HTTP se emite después del rollback. En cola ejecuta
el sistema. `related` identifica la consulta/iniciador o el documento cuyo `creado_por` conserva al iniciador, con contexto original. El cambio
de estatus conserva `empresa.updated`.
El estado terminal del PDF bloqueado y su evento se confirman en la misma
transacción. Si falla la inserción de auditoría, ambos se revierten y la cola
puede reintentarlos; una redelivery posterior al éxito no duplica el evento.

`reportar_cartera` queda definido para módulos futuros: Activa/Suspendida permiten
reporte SIC y monitoreo/reporte PLD de cartera existente. Borrador permite
preparación interna, sin envíos externos. No implementa reportes ni acredita
aplicabilidad legal. Véase [ADR 0029](../explanation/adr-0029-estatus-operativo-empresa.md).
