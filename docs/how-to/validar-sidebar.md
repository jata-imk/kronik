# Validar la expansión del sidebar

El contrato está en [Sistema de diseño UI/UX](../reference/diseno-ui-ux.md#expansión-del-menú-lateral). Este recorrido verifica presentación y navegación, sin modificar operaciones de negocio.

## Preparar una ejecución aislada

Desde el checkout de la entrega, instalar las dependencias del lockfile y compilar con `npm run build`. No usar el servidor ni la base de datos de otro desarrollador. Elegir un puerto libre; el ejemplo usa 8026. En PowerShell:

```powershell
$env:E2E_FRESH_DATABASE = "true"
$env:E2E_PORT = "8026"
$env:E2E_BASE_URL = "http://127.0.0.1:8026"
npm run test:e2e -- sidebar.spec.js permissions.spec.js
```

El arnés crea exclusivamente una SQLite nueva con nombre UUID en `storage/framework/testing/`, aplica migraciones y `E2eSeeder` allí y detiene su servidor al terminar. No ejecutar `migrate:fresh` sobre una base existente ni borrar `public/hot` de otro checkout. `E2E_FRESH_DATABASE=true` rechaza un `public/hot` existente.

## Comprobar comportamiento y presentación

1. En Clientes, verificar Listado seleccionado. Cerrar Clientes: conservar énfasis y descripción «Página actual: Listado», ocultar sus destinos y mantener la URL. Volver a abrirlo.
2. Usar Enter y Espacio sobre Clientes. Comprobar foco visible y estable, flecha, `aria-expanded` y el submenú identificado por `aria-controls`. Con Clientes cerrado, Tab debe pasar a Originación.
3. Abrir Originación: Clientes se cierra y conserva selección. Elegir Todas las solicitudes: cambia selección y abre Originación. Volver atrás: se abre Clientes. Cerrar y recargar: Clientes se abre nuevamente.
4. Filtrar Clientes mediante Inertia: conservar cierre manual. El E2E también inyecta un nivel sintético de grupos anidados en la respuesta autorizada para comprobar teclado, acordeón y ancho en móvil sin cambiar el catálogo del servidor. Las pruebas de componente complementan con remontaje, props equivalentes y cambio de árbol autorizado.
5. Repetir en 1440×900 y 390×844, claro/oscuro, con énfasis emerald/violet y superficies zinc/slate. En móvil y overlay, alternar no cierra el panel; elegir destino sí. Revisar también 768 px, foco y zoom 200 % durante la comprobación visual.
6. Con cuentas sin acceso, comprobar que Clientes no aparece y el acceso directo conserva el 403; con lector, conservar destinos de lectura sin acciones de escritura.

Las capturas de cada combinación se guardan en el directorio de resultados de Playwright como `clientes-cerrado.png` y `clientes-abierto.png`. Revisar las imágenes, consola y, ante fallos, traza/video; una aserción automatizada no acredita por sí sola la inspección visual. Registrar comando, commit, entorno y resultado en la Agent Note y Notion.

## Verificaciones complementarias

```powershell
npm run test:unit
npm run build
node --test tests/E2E/prepare-guards.mjs
php artisan test --filter="navigation exposes product catalogue"
```

Usar el entorno de pruebas configurado con SQLite en memoria para el último comando. No se requieren migraciones operativas, permisos nuevos, proveedores externos ni eventos de auditoría por expandir/contraer.
