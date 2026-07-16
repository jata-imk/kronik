---
type: explanation
area: architecture
status: active
---

# Decisiones Técnicas

## Documentación híbrida

Se usa Markdown + Diataxis dentro del repositorio como fuente principal. La estructura queda lista para una migración futura a Docusaurus si se requiere publicar un sitio navegable.

Motivos:

- Mantiene la documentación cerca del código.
- Funciona con GitHub, Obsidian y editores comunes.
- Evita agregar tooling antes de necesitar publicación web.

Relacionado:

- [Estrategia de documentación](documentacion.md)
- [Índice principal](../index.md)

## Reset de datos sin borrar catálogos pesados

Se agregó `php artisan dev:reset-data` para limpiar datos volátiles sin borrar SAT/SEPOMEX.

Motivos:

- `migrate:fresh --seed` borra todas las tablas.
- SAT/SEPOMEX dependen de scrapers y pueden tardar mucho.
- El flujo dev necesita resets rápidos y repetibles.

Relacionado:

- [Reset de datos de desarrollo](../how-to/reset-datos-desarrollo.md)
- [Seeders](../reference/seeders.md)
