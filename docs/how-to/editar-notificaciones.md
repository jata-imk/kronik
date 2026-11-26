# Editar y comprobar Notificaciones

Requiere los permisos por acción de
[plantillas de Notificaciones](../reference/plantillas-notificaciones.md).
Una plantilla es global; gestionar una regla no concede permiso para redactarla.

1. Abre Notificaciones desde Operación o desde Comunicaciones.
2. Crea una plantilla con nombre, clave estable, evento y canal SMS/correo.
   En correo selecciona texto o HTML; cambiar formato limpia el cuerpo explícitamente.
3. Redacta e inserta variables desde el panel buscable. Nombre significa primer
   nombre; fecha corresponde al evento. SMS no admite el catálogo documental.
4. Usa **Actualizar previsualización** antes de guardar. SMS muestra texto literal
   y estimación de segmentos; correo HTML muestra contenido renderizado aislado.
   La previa no envía ni guarda nada.
5. Guarda el borrador y actívalo con el permiso correspondiente. La versión activa
   queda inmutable. Para cambiarla, crea una nueva versión desde la elegida.
6. En Comunicaciones → Reglas y plantillas, selecciona una plantilla global
   activa compatible con el evento/canal. Conserva las comprobaciones de
   consentimiento, elegibilidad, horario, anticipación y límites.
7. Consulta cualquier versión exacta desde su historial. Retirar o sustituir
   cancela pendientes sin intento; no se recrean automáticamente con otra redacción.

En documentos, **Previsualización** muestra HTML continuo con recursos autorizados.
**Previsualizar PDF** es una acción separada para comprobar el PDF real.
Las previas no reservan folios ni crean generaciones.

## Validación aislada

Usa el harness y las barreras de [QA de comunicaciones](probar-comunicaciones.md).
Ejecuta un solo proceso PHP de pruebas por checkout, también durante review.

```powershell
php artisan test --filter='NotificationTemplateTest|DocumentoEditorTest|Communication|LabsMobile|Sms'
npm run test:unit
npm run build
$env:E2E_FRESH_DATABASE='true'
npm run test:e2e -- notificaciones.spec.js communications.spec.js documentos-editor.spec.js consulta-versiones.spec.js
```

BD y destinatarios sintéticos, correo fake y HTTP simulado. Revisar borradores sin
guardar, históricos, permisos, teclado, móvil, respuestas tardías y ausencia de
peticiones PDF al usar texto/HTML. La generación PDF se comprueba por separado.
No modificar .env ni activar proveedores para este recorrido.
