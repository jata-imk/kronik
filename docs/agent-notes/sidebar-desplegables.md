# Sidebar: expansión manual de grupos

## Referencias

- Notion: https://app.notion.com/p/41361db7db7f81b894d0f7f3b248e5dd
- Rama: `fix/sidebar-desplegables`, base `7117c4c` de `origin/main`.
- Worktree: `storage/framework/testing/sidebar-worktree` del checkout original.
- Commit de implementación: `c0aa7e5`, local y sin publicar; contiene el código, pruebas, documentación y capturas validados.
- PR: no abierta; entrega local para revisión, sin integrar ni desplegar.
- Contrato: [Sistema de diseño](../reference/diseno-ui-ux.md#expansión-del-menú-lateral).
- Procedimiento: [Validar sidebar](../how-to/validar-sidebar.md).

## Estado actual

Implementado y validado localmente el 2026-11-17; listo para revisión. No acredita integración, despliegue ni QA reportado por el usuario.

El checkout original permanece en `main` y conserva los registros, capturas, `docs/audits/` y stackdump sin seguimiento iniciales. Las referencias remotas se contrastaron antes y después: `main` en `7117c4c` y `feat/comunicaciones-notificaciones` de Bryan en `793e684`, sin cambios por esta entrega.

Decisiones confirmadas: autoabrir al navegar/recargar; conservar cierres durante filtros/recargas parciales; acordeón entre hermanos; resaltar grupo seleccionado y describir el destino para accesibilidad. La expansión compartida sobrevive al remontaje del layout y se descarta ante cambios del árbol autorizado.

Sin migraciones operativas, seeders nuevos, variables, permisos ni eventos auditables nuevos. Solo se prepararon bases E2E nuevas con UUID. El `.env` del worktree es sintético y exclusivamente de pruebas; no se copiaron secretos ni la conexión operativa. La matriz justifica que expandir/contraer no muta negocio. No cambia arquitectura ni requiere ADR nuevo. El menubar superior configurable queda fuera de la entrega.

## Evidencia

Entorno local Windows, Node 22.22.3 y PHP 8.3.29. Inspección visual mediante Chromium 158.0.8127.36; E2E mediante el proyecto `chromium` del repositorio.

| Verificación | Resultado |
| --- | --- |
| Regresión contra código original | Falla al cerrar Clientes: `expected true to be false`. |
| Menú/layout focalizados | 15 pruebas aprobadas; 11 nuevas sobre el componente recursivo real. |
| `npm run test:unit` | 183 pruebas aprobadas en 66 archivos. Se ejecutó fuera del sandbox después de un bloqueo de acceso de esbuild. |
| `npm run build` | Correcto tras el ajuste final de foco, tamaño y sangría. |
| `php artisan test --filter="navigation exposes product catalogue" --display-warnings` | 1 prueba y 7 aserciones aprobadas, sin avisos tras configurar el entorno de pruebas aislado. |
| `node --test tests/E2E/prepare-guards.mjs` | 4 pruebas aprobadas. |
| `npm run test:e2e -- sidebar.spec.js permissions.spec.js --max-failures=1 --output=test-results-sidebar-final` | 10 pruebas aprobadas. `E2E_FRESH_DATABASE=true`, puerto 8026, base UUID nueva. |
| Enlaces y diff | 92 enlaces locales de los siete documentos afectados y `git diff --check` revisados sin errores. |

Los E2E verifican clics repetidos, selección persistente, Enter/Espacio, foco visible de 2px, ARIA, Tab sin destinos ocultos, filtros Inertia, atrás, recarga, escritorio/móvil, ambos temas y modo overlay. Incluyen un nivel adicional sintético de grupos anidados para probar teclado, acordeón y ancho en móvil, sin modificar NavigationService. Conservan ausencia de Clientes y 403 sin permiso, acceso de lector y restricción administrativa.

La inspección visual detectó y corrigió el contorno de foco oculto por Sakai. Se comprobó en 1440×900 y 390×844, con emerald/zinc en claro y violet/slate en oscuro: grupo cerrado seleccionado, foco visible, altura mínima 44px, subárbol inerte y ausencia de desbordamiento horizontal. Comprobaciones adicionales a 320 y 768px y simulación CSS de escala 200%; esta última no equivale a una certificación de todos los niveles de zoom del navegador. Navegación real Inertia con atrás/adelante contrastada después de esperar la selección de cada página.

Capturas sintéticas revisadas:

- [Escritorio claro](../audits/sidebar-desplegables/escritorio-claro.png).
- [Escritorio oscuro](../audits/sidebar-desplegables/escritorio-oscuro.png).
- [Móvil claro](../audits/sidebar-desplegables/movil-claro.png).
- [Móvil oscuro](../audits/sidebar-desplegables/movil-oscuro.png).

La primera ejecución E2E usó un build anterior al ajuste del nombre accesible; se revisaron contexto, traza, captura y video representativos y se recompiló antes de las pasadas aprobadas. La suite frontend emite avisos de directivas de pruebas ajenas al sidebar. El build informa referencias a recursos de portada/fuentes no distribuidos en este checkout, datos de Browserslist antiguos y chunks grandes; no impidieron compilar. Ninguno de estos avisos se presenta como validación de esas superficies.

Notion actualizado con los criterios verificados y la evidencia real. Se conserva `Kanban Status=Doing` y `Done=__NO__` por la revisión e integración pendientes; sin fecha nueva comprometida.

## Revisión y siguiente paso

Revisado el diff acumulado frente a `7117c4c`: cambio limitado al sidebar, su estado, estilos, pruebas y documentación; sin cambios backend, financieros, de permisos ni del menubar superior. No se ha solicitado `/review medium` porque no se abrió PR.

Revisar la rama local. Al abrir una PR, solicitar `/review medium`, resolver hallazgos y registrar el commit revisado antes de una eventual integración. La integración y el despliegue siguen pendientes por instrucción del usuario.
