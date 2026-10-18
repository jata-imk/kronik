# Cargar el catálogo del menú contextual

El seeder existente `MenubarItemsSeeder` carga 19 módulos y 45 acciones de navegación en **Administración → Menú contextual por módulos**. Incluye clientes/expedientes, solicitudes, evaluación, cumplimiento, SIC/reportes, créditos/pagos/anticipos, productos, documentos, empresa, usuarios, equipos, sucursales, roles, actividad, perfil y tokens API.

## Cargar después del reinicio QA

Tras desplegar y completar el [reinicio mínimo](reset-datos-desarrollo.md), ejecuta:

```bash
php artisan db:seed --class=MenubarItemsSeeder --force
```

Los módulos y permisos deben existir previamente. El reinicio mínimo los conserva; en una instalación nueva los prepara `ModulesAndPermissionsSeeder` o el `SystemSeeder` del arranque inicial.

El catálogo se agrupa en carpetas. Cada acción tiene etiqueta, icono, página destino y los parámetros requeridos como marcadores, por ejemplo `{"credito":"{credito}"}`. Las pantallas de gestión que abren formularios en diálogos se representan por su página principal: no se inventan rutas GET de alta/edición que el sistema no implementa. Se excluyen operaciones de escritura, endpoints JSON auxiliares, exportaciones y pantallas públicas/de demostración; se incluye el visor de PDF generado como acción de documentos.

## Elegir dónde aparecen las acciones

Hay dos conceptos distintos:

- **Página destino:** dónde abre la acción, por ejemplo `clientes.create`.
- **Rutas de activación:** en qué pantallas se muestra esa acción en la barra contextual. Son el campo `routes` de la asociación con el módulo.

Las entradas nuevas tienen sus rutas de activación vacías (`routes: []`). Por eso aparecen en el catálogo administrativo y todavía no se muestran en las barras de las páginas. Edita las asociaciones desde administración para decidir dónde mostrarlas. Los botones automáticos Inicio/Regresar y la navegación principal son independientes del catálogo.

Una acción que necesita un registro debe activarse en un contexto que proporcione sus parámetros. Por ejemplo, el recibo necesita `credito` y `pago`; una página sin esos parámetros no puede generar ese enlace. Las autorizaciones de cada página siguen aplicándose en el servidor.

## Volver a ejecutar

El seeder es aditivo e idempotente: completa las entradas faltantes y conserva nombres, iconos, orden, parámetros, ubicación y rutas de activación que ya hayas personalizado. No usa IDs fijos ni borra menús existentes. Si necesitas empezar con todo desasignado, completa primero el reinicio QA; ejecutar el seeder sobre un menú ya configurado no retira sus activaciones.

`SystemSeeder` utiliza este mismo seeder. El modo demo del reinicio recrea el catálogo con activaciones vacías; los modos mínimo/vacío dejan el menú sin entradas hasta que lo cargues deliberadamente. No hay un segundo seeder de catálogo. `ClienteAccesosMenubarSeeder` sigue reservado para su migración histórica y los fixtures E2E, donde se activan expresamente accesos para probar navegación.

## Verificar

- En administración aparecen las carpetas y acciones con destinos válidos.
- En una carga nueva, la columna de rutas de activación está vacía.
- Abrir una página no muestra automáticamente todas las acciones sembradas.
- Repetir el comando conserva las personalizaciones realizadas.

Pruebas aisladas:

```bash
php artisan test --filter=Menubar
```
