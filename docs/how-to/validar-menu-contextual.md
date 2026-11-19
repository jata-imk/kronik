# Validar el menú contextual y la navegación

Usa un checkout propio con dependencias instaladas, build sin `public/hot` y
datos sintéticos. El harness crea una SQLite nueva dentro de ese checkout; no
requiere reiniciar la base operativa ni usar proveedores externos.

## Ejecutar regresiones

Desde PowerShell, elige un puerto libre reservado para esta ejecución:

```powershell
npm run build
$env:E2E_PORT = '8035'
$env:E2E_BASE_URL = 'http://127.0.0.1:8035'
$env:E2E_FRESH_DATABASE = 'true'
npm run test:e2e -- menubar.spec.js menubar-navegacion.spec.js sidebar.spec.js contexto-sucursal.spec.js --workers=1
```

El harness prepara su propia base y detiene su servidor al terminar. Conserva
`test-results/` y `playwright-report/` cuando falle: inspecciona captura, vídeo,
traza y consola antes de ajustar código o esperas. No reutilices un servidor
de otra sesión. El puerto y la URL base deben coincidir.

La comprobación backend del contrato se ejecuta con
`php artisan test --filter=Menubar`. Las pruebas de componentes y guardas están
en la suite `npm run test:unit`. El método exploratorio y los resultados reales
de cada commit se registran en la [nota de la tarea](../agent-notes/menubar-usabilidad.md).

## Revisar la experiencia

1. En administración, crea un grupo y una acción desde el catálogo; distingue
   Qué abre de Dónde aparece. Cambia etiqueta, orden y pantallas, guarda y recarga.
2. Solicita una vista previa. Abre grupos y activa sus hojas: la URL y los datos
   guardados deben permanecer iguales. Sin un recurso válido, comprueba el motivo
   en español; no selecciones un ID de otro registro para forzar el enlace.
3. Revisa escritorio y móvil en claro/oscuro. En escritorio, texto de barra de
   13 px y grupos con flecha; en móvil, despliegue utilizable y objetivos de al
   menos 44 px. Usa Tab, Enter, Espacio, flechas y Escape; comprueba foco visible
   y nombres accesibles. Inspecciona las capturas, además de las aserciones.
4. Desde una edición, modifica un campo y navega mediante la barra. Rechaza el
   descarte: se conservan datos y errores. Acéptalo: navega sin guardar. Repite
   con atrás/adelante, recarga y cierre de diálogos. Guardar no debe pedir descarte.
5. Con un perfil de consulta, verifica ausencia de Configurar y de escrituras
   mediante botones o Enter. Los permisos de gestión y de destino son distintos.
   Los enlaces conservan su href y abrir en otra pestaña sigue disponible.
6. Cambia equipo/sucursal y comprueba limpieza de datos y permisos anteriores,
   incluidas otras pestañas. La captura del contexto anterior no debe reaparecer
   al volver por el historial. Revisa también filtros y recargas parciales.
7. Comprueba vacío, carga, fallo con Reintentar, validación española y cancelación
   de respuestas tardías. En el sidebar, conserva expansión manual, acordeón,
   selección, teclado y navegación Inertia; usa su [guía específica](validar-sidebar.md).

Consulta el [contrato técnico](../menubar-items.md), la
[configuración del catálogo](configurar-menu-contextual.md) y la
[matriz de auditoría](../reference/auditoria-eventos.md). La apertura visual de
menús y la vista previa no crean eventos adicionales; la gestión mantiene sus
eventos transaccionales existentes.
