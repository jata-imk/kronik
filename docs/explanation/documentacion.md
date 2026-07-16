---
type: explanation
area: documentation
status: active
---

# Estrategia de Documentación

El repositorio usa una estrategia híbrida:

- Diataxis en Markdown dentro de `docs/` como fuente actual.
- Estructura compatible con una migración futura a Docusaurus si se necesita un sitio navegable.

## Categorías

- `tutorials`: guías de aprendizaje paso a paso.
- `how-to`: recetas operativas para tareas concretas.
- `reference`: comandos, variables, tablas, seeders y contratos técnicos.
- `explanation`: contexto, decisiones y lógica de negocio.
- `todos`: backlog funcional y técnico.

## Obsidian

Abrir el repositorio o la carpeta `docs/` como vault de Obsidian permite ver el grafo de documentos. Para que el grafo sea útil, cada documento debe enlazar a documentos relacionados usando enlaces Markdown relativos.

Ejemplo:

```md
Ver también:

- [Seeders](../reference/seeders.md)
- [Reset de datos de desarrollo](../how-to/reset-datos-desarrollo.md)
```

Se prefieren enlaces Markdown estándar sobre enlaces wiki `[[...]]` para mantener compatibilidad con GitHub y Docusaurus.

Relacionado:

- [Índice principal](../index.md)
- [Decisiones técnicas](decisiones.md)
- [Automatización de documentación](../reference/automatizacion-documentacion.md)
