# Menubar: usabilidad y configuración contextual

## Referencias

- Notion: https://app.notion.com/p/41361db7db7f81968ea5cfc45a96b51d
- Base funcional verificada tras `git fetch origin`: `8884a78d738f9623c5d72fc194c4ecc55b400874`, con PR #83 y #84 integradas.
- Primer incremento: rama `feat/menubar-catalogo`; worktree propio `storage/framework/testing/menubar-worktree`.
- PR: pendiente de publicación. Integración y despliegue no autorizados.

## Estado actual

Implementación iniciada el 2026-11-18 por instrucción del usuario. Se preservan los archivos locales, worktrees y servicios ajenos, incluida la rama independiente de comunicaciones de Bryan. La aceptación del sidebar sigue pendiente y no forma parte del cierre de esta tarea.

## Decisiones confirmadas

Configuración compartida para toda la instalación con permisos actuales; catálogo de 19 módulos; visibilidad según pantalla; guardado explícito por elemento; opciones técnicas en apartado avanzado. Confirmar descarte de formularios, sin borradores persistentes ni altas silenciosas.

Mantener PrimeVue Menubar: grupos superiores con texto y flecha, iconos opcionales en submenús; tipografía compacta propia de la barra y control Configurar neutro. No cambiar la tipografía general ni introducir preferencias personales o separación por financiera.

## Incrementos

1. Catálogo común, contexto, validación, persistencia y previsualización sin efectos.
2. Editor guiado y agregado desde la pantalla real.
3. Barra compacta, navegación segura y regresión conjunta.

Cada PR tendrá revisión medium, pruebas y documentación; barrido acumulado contra la base al completar. Aún no se acredita implementación validada, integración ni despliegue.

La segunda revisión detectó que imponer nombres de pantalla canónicos antes de actualizar los editores antiguos impediría guardar algunas selecciones. Se agrupan los incrementos 1 y 2 en una primera PR coherente; la segunda entrega integra barra y navegación segura. El otro hallazgo, destinos aceptados pero no soportados por autorización, se corrige alineando validador y selector avanzado con el catálogo. No se publica la rama backend aislada como entrega integrable.

## Evidencia inicial

Exploración de `8884a78` con Playwright MCP en puerto 8031 y SQLite UUID nueva: listado sin barra cuando no hay activaciones; edición de cliente con Abrir expediente/Nueva solicitud; administración y agregado rápido. Este último preselecciona Listado aunque se abra desde edición. Las capturas usan fixtures sintéticos; la propuesta visual solo modificó el navegador y no se guardó. Build correcto y 4 pruebas frontend aprobadas antes de comenzar la implementación.

## Siguiente paso

Revisión medium del commit `64b5a5c` frente a `8884a78`: hallazgo P2 en autorización de enlaces de roles para un equipo seleccionado distinto del actual. Se corrige reutilizando `TeamContext`, con regresión de ambos sentidos y restauración del contexto. Revisión posterior requerida.

Primer incremento implementado: catálogo común, previsualización sin efectos, destinos autorizados y persistencia validada. Build correcto. Pruebas focalizadas iniciales: 27 aprobadas; se amplió cobertura de jerarquía y autorización. Completar revisión medium y publicar la PR antes del editor.

Editor guiado en worktree propio `storage/framework/testing/menubar-ui-worktree`, rama `feat/menubar-editor`. Catálogo y editor se agrupan en la primera PR por compatibilidad; la barra/navegación quedan en la siguiente.

## Evidencia durante la primera entrega

Backend del commit 64b5a5c: suite completa con 991 aprobadas, 2 omitidas y 47 782 aserciones; 414.96 s. Se omiten renderizado documental optativo y medición de capacidad. Los ajustes posteriores de roles/destinos están cubiertos por 35 pruebas focalizadas (603 aserciones), todavía pendientes de la nueva suite completa.

El editor reutiliza Form.vue para administración y agregado contextual. Build y 8 pruebas focalizadas de formulario/consulta/previsualización aprobadas; se amplía la cobertura del renderizado nativo y E2E. La exploración MCP usa puerto 8033 y SQLite UUID propia. Detectó Escape que propagaba desde MultiSelect al diálogo; se limita el cierre del diálogo mientras hay un selector abierto. Se revisaron capturas y errores del recorrido. El primer fallo de selección fue un locator al input oculto de MultiSelect, no un error de persistencia.

Suite frontend completa: 69 archivos y 190 pruebas aprobadas. Se detiene la propagación de Escape desde el selector abierto; comprobado con MCP. El siguiente E2E detectó latencia de guardado durante ejecución concurrente de la suite; traza: POST 302 en 18 s y GET posterior cancelado al agotar el límite de 15 s. Captura, vídeo, traza y consola inspeccionados; el recorrido usa ahora 45 s para esperar persistencia y se ejecuta sin la suite frontend concurrente. La exploración detectó además un delimitador incorrecto en el marcador de navegación documental nativa; corregido y pruebas backend repetidas.

[Captura del editor y vista previa](../audits/menubar-2026-11-18/editor-vista-previa.png), Chromium con fixtures sintéticos. Vista previa sin enlaces ejecutables ni escrituras. Capturas finales adicionales y resultados E2E pendientes del recorrido en curso.
