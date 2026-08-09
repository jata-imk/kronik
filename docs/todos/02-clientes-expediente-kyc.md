# TODO 02: Clientes, Expediente y KYC

## Objetivo

Extender el alta actual de clientes hacia un expediente completo de solicitante.

## Estado

Completado en `feat/backlog-02-clientes-expediente-kyc` y publicado en la PR #10. Verificacion local y CI de GitHub aprobados.

## Completado

- [x] Agregar ocupacion, actividad economica, ingresos, egresos y origen de recursos.
- [x] Crear documentos privados por cliente: INE, comprobante de domicilio, constancia fiscal, comprobantes de ingreso y adicionales.
- [x] Registrar estado documental: pendiente, recibido, validado, rechazado y vencido.
- [x] Agregar referencias personales y laborales.
- [x] Agregar avales, obligados solidarios y garantias vinculadas al cliente.
- [x] Registrar consentimiento SIC con fecha, usuario, medio y evidencia.
- [x] Corregir la estrategia morph mediante un mapa global para el alias `clientes`.
- [x] Agregar pruebas de creacion y actualizacion del expediente.
- [x] Corregir la revision posterior: domicilio heredado, telefono internacional, fechas, paises, mensajes de validacion, permisos y confirmacion real de eliminacion.
- [x] Mostrar relaciones entrantes y salientes, enlazar expedientes y limitar propietarios de garantias a clientes relacionados.

## Pendiente relacionado

- [ ] Incorporar visor privado de PDF, JPG y PNG desde la accion `Revisar`, sin exponer rutas fisicas ni URLs publicas persistentes.
- [ ] Diseñar plantillas versionadas para consentimiento SIC, garantias y contratos; la generacion y firma quedan fuera de esta correccion.

## Decisiones

- Las relaciones por solicitud se implementaran en Backlog 04 porque `solicitudes_credito` aun no existe.
- OCR y proveedores KYC permanecen en su tarea independiente de Notion.
- Backlog 05 definira cuando un consentimiento es legalmente vigente y bloqueara consultas sin autorizacion.

## Verificacion

- `php artisan test`: 64 pruebas aprobadas, 217 aserciones y 1 skip esperado.
- `npm run build`: aprobado; conserva advertencias preexistentes de Browserslist y del chunk `FormCliente`.
- `vendor/bin/pint --dirty --test`: aprobado.
- `npx biome check` sobre las vistas modificadas: aprobado.
- Playwright con Chrome local: escritorio y movil, guardado de perfil, navegacion completa y dialogos; consola sin errores ni advertencias.
