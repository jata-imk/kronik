# Validar el menú contextual y la navegación

Para las pruebas automatizadas, usa un checkout propio con dependencias instaladas, build sin `public/hot` y
datos sintéticos. El harness crea una SQLite nueva dentro de ese checkout; no
requiere reiniciar la base operativa ni usar proveedores externos.

## Desplegar en QA

Las PR #85/#88 están integradas; commit funcional de integración
`1f4808962f06eff71b3a826ef0580a68f4db907c`. Esta entrega no añade migraciones,
dependencias, permisos ni variables de entorno. Conserva la configuración y las
personalizaciones existentes. El usuario ejecuta el despliegue en la VPS y
comparte el resultado; integración no significa despliegue comprobado.

Tras crear/verificar el respaldo habitual, entra en la carpeta raíz de Kronik
como usuario del sitio. Estos comandos usan PHP 8.3 y la ubicación de Composer
del [procedimiento de la VPS](desplegar-vps-nuevo.md#12-despliegues-posteriores):

```bash
(
    set -e
    test "$(git branch --show-current)" = main
    test -z "$(git status --porcelain)"
    git fetch origin
    git merge-base --is-ancestor 1f4808962f06eff71b3a826ef0580a68f4db907c origin/main
    php8.3 artisan down --retry=60
    git pull --ff-only origin main
    php8.3 /usr/local/bin/composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    npm ci
    npm run build
    php8.3 artisan optimize:clear
    php8.3 artisan optimize
    php8.3 artisan queue:restart
    php8.3 artisan up
    git log -1 --oneline
)
```

El bloque se detiene si hay cambios locales, una rama distinta o falla un paso.
Si falla después de activar mantenimiento, conserva ese estado hasta resolverlo;
no ejecuta una reapertura automática. `queue:restart` solicita el reinicio:
comprueba la recuperación del worker mediante su administrador de procesos actual.

No hace falta sembrar para actualizar barra/editor. Si el catálogo está vacío o
incompleto, puedes completarlo mediante el seeder aditivo, que conserva etiquetas,
jerarquía, orden y activaciones personalizadas y no activa las entradas nuevas:

```bash
php8.3 artisan db:seed --class=MenubarItemsSeeder --force
```

Consulta sus prerrequisitos en [configuración del catálogo](configurar-menu-contextual.md).
Para esta entrega no se requiere el seeder general ni reiniciar los datos de QA.
Después recarga el navegador y revisa administración, vista previa sin efectos,
barra compacta, descarte de captura y sidebar en escritorio/móvil y ambos temas.
Registra commit desplegado, resultado y siguientes pasos en la Agent Note y Notion.

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
