# Sistema de diseño UI/UX de Kronik

Estado: vigente desde octubre de 2026. Aplica a pantallas nuevas y a la renovación de pantallas existentes.

## Identidad

Kronik es una herramienta de trabajo para seguir un crédito de principio a fin. Debe sentirse **clara, serena y precisa**. La información financiera y el siguiente paso tienen prioridad sobre la decoración. Productos crediticios, Documentos y plantillas y Expediente KYC son las referencias internas de densidad, jerarquía y calidad. PrimeVue aporta controles consistentes; Jetstream, patrones de cuenta; Notion, edición enfocada; Vercel y Apple, espacio y precisión; Aspel SAE, orientación operativa. Ninguna interfaz se copia literalmente.

### Referencias externas y lectura aplicada

- [LoanPro Smart Panel](https://help.loanpro.io/management-and-operations/smart-panel) mantiene información y acciones relevantes al lado del crédito; en Kronik esto inspira un resumen contextual y el siguiente paso en detalles, sin copiar su panel.
- [LoanPro Smart Checklist](https://help.loanpro.io/smart-checklist) hace visibles pasos y estados del trabajo; en Kronik inspira requisitos pendientes y continuidad entre KYC, aprobación, firma y pago.
- [Mambu, ejemplo de configuración de producto](https://cloud.mambu.com/hubfs/2025/Product%20brochures/Product%20Brochure%20_%20Stored%20Value%20Accounts%20_%20Aug25.pdf) muestra una navegación por entidades financieras y configuración; en Kronik se traduce a secciones Operación y Configuración.
- [Aspel SAE, ficha técnica](https://www.aspel.com.mx/assets/aspelcom/images/productos/sae/ficha-tecnica-sae-9.pdf) documenta catálogos y perfiles de usuario; inspira la separación entre administración, catálogos y trabajo diario para equipos pequeños.

Las aplicaciones citadas son referencias de organización del trabajo. Los colores, componentes y comportamiento final se definen en Kronik.

## Tokens y tipografía

La apariencia es configurable por navegador desde **Personalizar apariencia** en la barra superior: color de énfasis, color de fondo, estilo de controles, modo del menú y tema claro/oscuro. La selección se conserva en `layoutConfig` de localStorage. El verde es solo el valor inicial. `resources/js/theme/kronik.js` define el preset inicial; `AppConfigurator.vue` aplica las selecciones y `resources/css/kronik.css` consume los tokens semánticos de PrimeVue. No crear otro juego de colores fijo dentro de una página.

Las muestras del personalizador muestran un círculo de 1.5rem dentro de un botón de al menos 44px: el color se ve compacto, pero sigue siendo cómodo al tacto. La selección se indica con contorno y `aria-pressed`.

| Token | Claro | Oscuro | Uso |
| --- | --- | --- | --- |
| Énfasis | `--p-primary-color` | `--p-primary-color` | Acción primaria; depende de la selección |
| Texto de acción | `--p-primary-contrast-color` | `--p-primary-contrast-color` | Contraste sobre acción primaria |
| Tinta | `--p-text-color` | `--p-text-color` | Texto principal |
| Secundario | `--p-text-muted-color` | `--p-text-muted-color` | Ayudas, etiquetas y metadatos |
| Lienzo | `--p-surface-50` | `--p-surface-950` | Fondo de aplicación; depende de la superficie elegida |
| Borde | `--p-surface-200` | `--p-surface-700` | Separación de superficies |

- Usar **Inter Variable**, distribuida localmente con `@fontsource-variable/inter`. Cifras financieras con numerales tabulares (`k-financial`).
- Escala recomendada: título de página 1.6rem/700, sección 1.15rem/700, texto 1rem, ayuda .85rem y ceja .75rem/700. En pantallas de hasta 575px, la raíz usa 93.75% (15px con la preferencia normal del navegador); los títulos y descripciones se ajustan por componente.
- Espaciado de base 4px; intervalos habituales 8, 12, 16, 24 y 32px. Radio de superficie 1rem; controles entre .5 y .75rem. Sombra suave solo para capas principales.
- El color de énfasis comunica acciones y foco; nunca debe usarse como único indicador de estado. Error, advertencia y éxito deben incluir texto e icono cuando sea útil. Evitar colores de marca fijos en CSS o Tailwind para elementos que deben responder al selector.
- En tema claro, los botones de paletas alternativas toman el tono 700 para sostener texto blanco legible; el verde inicial conserva su tono 500, que ya es oscuro. En tema oscuro, PrimeVue usa un tono claro con texto de superficie oscura. Comprobar contraste tras añadir una paleta nueva, incluido hover y foco.

### Tipografía, ritmo y profundidad

| Elemento | Regla | Fuente técnica |
| --- | --- | --- |
| Texto de producto | Inter Variable; pesos 400, 600 y 700 como base. Evitar cambiar de familia entre módulos. | `resources/js/app.js`, `resources/css/kronik.css` |
| Acento manuscrito | Caveat Variable en fragmentos breves y deliberados de la landing y el acceso: una idea por bloque. También puede destacar una frase editorial en un estado vacío o bienvenida. Nunca en cifras, etiquetas, estados, campos, tablas ni instrucciones de seguridad. La interfaz operativa sigue en Inter. | `@fontsource-variable/caveat`, `.k-display-accent` |
| Cifras | Numerales tabulares y alineación consistente; moneda con dos decimales y unidad explícita. | `.k-financial` |
| Espaciado | Retícula base de 4 px. Usar 8 px entre elementos relacionados, 12–16 px dentro de controles, 24 px en cabeceras y 32 px para separar bloques mayores. | `--k-space-4/6/8` y utilidades Tailwind |
| Radios | 16 px en paneles; 12 px en controles y acciones. No mezclar radios arbitrarios dentro de un mismo módulo. | `--k-radius`, `--k-radius-control` |
| Sombras | Una elevación leve en tarjetas principales y una mayor solo para capas flotantes. Bordes del token de superficie antes de añadir sombra. En oscuro, sombra más profunda y borde visible. | `--k-shadow-panel`, `--k-shadow-float`, `--k-line` |

El editor de documentos conserva sus familias tipográficas propias para representar el documento final; esas fuentes no se aplican a la interfaz de operación.

En móvil, usar 15px equivalentes (1rem) para texto corriente y una altura de línea de 1.42 en el área de trabajo: caben más datos sin compactar en exceso. Las entradas de formularios mantienen un mínimo de 16px para evitar zoom automático en iOS; los botones táctiles conservan al menos 44×44px. Reducir títulos y texto auxiliar por componente mediante `rem` o `clamp()`; no bajar la raíz a 85–90 %, porque también encogería controles y texto crítico. Usar `rem` en tamaños de letra, controles y ritmos de interfaz; `px` es válido para bordes de 1 px y detalles físicos. Comprobar el diseño a 320, 390, 768 y 1440 px y con zoom 200 %.

### Portada pública y movimiento

La portada explica el recorrido en cuatro etapas y muestra capturas reales de cada módulo junto a su explicación. Guardar las capturas optimizadas en `public/images/landing/`, difuminar filas con datos de clientes o usuarios antes de publicarlas y actualizar las imágenes cuando cambie la interfaz. Cada imagen lleva `alt`, pie de foto y enlace para ampliarla. No poner cifras de prueba como promesas comerciales.

En escritorio, la primera pantalla usa dos columnas: titular y acción a la izquierda; maqueta del espacio de trabajo a la derecha. En móvil se apilan. `HeroParticleBurst.vue` dibuja en canvas puntos redondos con una distribución seudoaleatoria reproducible, sin hélice. El área radial completa se expande y contrae suavemente; no late cada punto por separado. El centro persigue el cursor con velocidad limitada y resorte amortiguado. El seguimiento cubre todo el ancho visible del hero, también sobre la maqueta, y conserva la última posición al salir; no vuelve al centro salvo al iniciar o activar movimiento reducido. El color principal responde al énfasis seleccionado y los tonos cálidos y azules son secundarios. El lienzo se ajusta a la densidad de píxeles, con límite para cuidar el rendimiento. El panel de maqueta usa un degradado suave, sin retícula de puntos que compita con las partículas; la retícula isométrica queda reservada a las cabeceras internas. El centro de la constelación queda despejado para mantener la legibilidad. El titular revela la segunda línea como máquina de escribir, con el texto completo reservado en el flujo para evitar saltos y un `aria-label` estático para lectores de pantalla. Con movimiento reducido se muestra completo de inmediato y el canvas dibuja una sola imagen estática.

Una banda fotográfica separa el recorrido de la galería y una segunda foto aparece como textura tenue tras el encabezado de la galería. Ambas se sirven localmente, optimizadas y con degradado para asegurar contraste; nunca se colocan debajo de tablas o datos financieros. Fuentes: [escritorio con documentos, Cht Gsml en Unsplash](https://unsplash.com/photos/desk-with-papers-glasses-calculator-and-office-supplies-sW02MHv37yk) y [revisión de documentos, Mikhail Nilov en Pexels](https://www.pexels.com/photo/a-person-examining-documents-8296970/). Sus licencias permiten el uso gratuito en una web comercial: [Unsplash](https://unsplash.com/license), [Pexels](https://www.pexels.com/license/). Mantener aquí autor, página y licencia si se sustituyen.

`DoodleUnderline.vue` dibuja dos trazos irregulares bajo una frase breve y cursiva; usarlo en encabezados editoriales de la portada, nunca en datos o controles. El panel de cierre puede llevar un borde luminoso animado con el color configurado y un trazo SVG que señale la acción. El pie usa un fondo oscuro deliberado, texto de alto contraste y enlaces reales al recorrido, la plataforma y el acceso. No inventar precios, métricas, avales ni promesas financieras.

Las entradas al hacer scroll se activan con `IntersectionObserver` en `useScrollReveal`; el contenido sigue visible si JavaScript falla. Animar solo opacidad y transformación, no tamaños ni posiciones que provoquen saltos. Desactivar partículas, flotación, subrayados, cursor, borde luminoso, revelados y elevación animada con `prefers-reduced-motion: reduce`. No usar movimiento en formularios, tablas financieras ni instrucciones de seguridad.

### Retícula de puntos

La imagen de referencia se interpreta como una **retícula isométrica de puntos**, construida en CSS con dos capas de puntos de 1 px sobre una celda de 40 × 40 px, desplazadas 20 px entre sí. Se aplica en `PageHeader` con degradado de transparencia hacia la izquierda; el texto y los botones siempre quedan encima. Su tinta usa `--k-pattern-ink`, derivada de la paleta de énfasis seleccionada, y la opacidad baja en modo oscuro. Se usa como textura de cabecera; no cubrir tablas, campos, alertas ni resúmenes financieros. No aporta información: debe ser invisible para tecnologías de asistencia y no interceptar clics. No guardar una copia rasterizada de la referencia, porque perdería adaptación al tema y nitidez en pantallas de alta densidad.

## Estructura y navegación

El menú lateral se define en `NavigationService` y admite hasta tres niveles: sección, grupo y destino. Orden: **Inicio → Operación → Configuración → Administración → Cuenta**. Dentro de Operación: Clientes (Listado, Expedientes KYC, Consultas SIC), Originación (Solicitudes, Evaluación, Cumplimiento), Créditos y pagos. Mantener la ruta activa visible y abrir sus ancestros. La disponibilidad depende de permisos reales; nunca anunciar una acción que termina en 403. Los accesos contextuales son propios de la página y no deben duplicar el menú principal.

En móvil, el menú es un panel modal: se cierra con el botón «Cerrar menú», al tocar la máscara, al elegir una ruta o con Escape. El botón del encabezado indica si está abierto mediante `aria-expanded`.

Cada página presenta ceja de contexto, título descriptivo, una línea que explique la tarea y, si existe, una acción primaria. Usar `resources/js/Components/PageHeader.vue` en el slot `card-header` de `AppLayout` (o en `header` cuando el contenido de cuenta queda fuera de la tarjeta): el mismo fondo de énfasis tenue, borde, espaciado y jerarquía deben repetirse en listados, formularios y detalles. Las acciones van en su slot `actions`, arriba a la derecha en escritorio y debajo del título en móvil. Los bloques de métricas, alertas o resumen financiero van en el contenido, después del encabezado; nunca sustituyen la cabecera. El tablero muestra trabajo pendiente, métricas y accesos rápidos del usuario y sucursal activa. Las métricas son enlaces a la fuente; no confundir capital inicial con saldo actualizado.

## Patrones de interfaz

| Necesidad | Patrón | Regla |
| --- | --- | --- |
| Búsqueda/listado | Barra de filtros + tabla + paginador | Etiquetas visibles, filtros persistidos en URL, vacío con siguiente paso y columnas secundarias ocultas o desplazables en móvil. |
| Detalle | Resumen, estado y secciones | Identificador visible, contexto de cliente/sucursal y acciones según permiso y estado. |
| Captura | Campos agrupados por propósito | Etiqueta siempre visible, ayuda breve antes del error y errores claros en español junto al campo. |
| Proceso largo | Etapas y siguiente paso | Mostrar etapa actual y requisitos pendientes; no usar color como única señal. |
| Documentos | Lista, vista previa, historial | Acciones de ver y descargar explícitas; diferenciar borrador, vigente y rechazado. |
| Dinero | Monto alineado y tabular | Moneda y dos decimales, origen de la cifra y fecha de corte cuando corresponda. |
| Acción delicada | Confirmación contextual | Explicar efecto, referencia y alcance QA antes de ejecutar; conservar mensajes de éxito o error. |

Reutilizar PrimeVue (`Button`, `InputText`, `Select`, `DataTable`, `Tag`, `Dialog`, `Toast`) y las clases `k-page-*`, `k-surface`, `k-feature`, `k-action-link`. Antes de crear un componente, buscar uno compartido en `resources/js/Components`; extraerlo si ya se repite y su comportamiento es el mismo. Mantener estado local reactivo en Vue; props de servidor son la fuente de verdad para datos y permisos. No duplicar reglas de negocio en JS.

## Responsive y accesibilidad

- Verificar 1440px, 1024px, 768px y 390px; revisar también 320px si la pantalla contiene tablas o formularios complejos. No permitir desplazamiento horizontal de toda la página. Las tablas pueden tener desplazamiento **dentro de su contenedor** con columna de identidad y acción accesibles.
- Mínimo 44×44px para blancos táctiles. Menú y submenús operables con teclado, foco visible y `aria-expanded` donde aplique. Modal con título y foco gestionado por PrimeVue.
- Contraste objetivo WCAG AA: 4.5:1 para texto normal y 3:1 para texto grande y componentes. No colocar texto verde claro sobre blanco ni texto secundario demasiado tenue.
- Probar tema claro y oscuro con al menos dos colores de énfasis y dos paletas de superficie; verificar que la elección persista al recargar y al navegar. Las superficies deben usar tokens. En zoom 200%, el contenido debe seguir legible y operable.
- Pantallas vacías, carga, error, sin permiso y éxito requieren mensajes específicos. Los errores de validación al usuario son en español y no pueden mostrar claves `validation.*`.

## Criterio de aceptación para una pantalla nueva

1. Ubicación y nombre coherentes en el menú, con permisos y ruta activa.
2. Encabezado, textura de puntos, superficies, controles, estados y cifras conformes a tokens y patrones; sin colores o sombras de marca fijados en una página.
3. Verificada en escritorio, tableta y móvil con datos reales de prueba, vacío y error pertinente.
4. Navegación por teclado, foco y etiquetas comprobados; sin acción decorativa inerte.
5. Pruebas de permisos y flujo cuando cambie backend o conducta visible; capturas de revisión sin datos sensibles en el PR.

La auditoría y orden de renovación están en [Auditoría UI de QA](../explanation/auditoria-ui-qa-2026-10.md). La decisión técnica está en [ADR 0021](../explanation/adr-0021-identidad-visual-kronik.md).
