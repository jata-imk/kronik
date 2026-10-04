# ADR 0021 — Identidad visual y navegación de Kronik

Fecha: 2026-10-02. Estado: aceptado; adopción incremental.

## Contexto

El flujo QA ya recorre producto, cliente, KYC, solicitud, evaluación, cumplimiento, contrato, desembolso y pagos. La UI creció por módulo y dejó jerarquías, tipografías, colores y navegación distintos. La portada interna no mostraba trabajo operativo y Expedientes KYC carecía de acceso directo.

## Decisión

Un único sistema visual usa PrimeVue con preset Kronik inicial basado en Aura, Inter local, superficies configurables y una sola jerarquía de encabezados. El verde oscuro es el énfasis inicial; el usuario conserva el selector de énfasis, superficie, preset y tema claro/oscuro. Los componentes propios consumen tokens semánticos de PrimeVue para respetar la selección persistida. Una retícula isométrica de puntos, generada en CSS a partir del énfasis elegido, identifica las cabeceras sin decorar el área de trabajo. La cursiva Inter se limita a una frase editorial de la portada. `NavigationService` es fuente del menú lateral con niveles anidados y permisos. El tablero muestra resúmenes y pendientes filtrados por sucursal activa y autorización. Las páginas adoptan los patrones definidos en la [referencia de diseño](../reference/diseno-ui-ux.md), empezando por el flujo principal y las superficies compartidas.

## Consecuencias

Un cambio en tokens alcanza muchas páginas; debe revisarse en claro, oscuro y móvil. Las pantallas con estilos propios se migran sin alterar lógica financiera. El menú contextual persistido sigue disponible para acciones locales, pero no sustituye la estructura global. Las capturas QA son evidencia de revisión, no fixtures para publicar datos. La adaptación completa de formularios y tablas antiguas se registra en la auditoría.
