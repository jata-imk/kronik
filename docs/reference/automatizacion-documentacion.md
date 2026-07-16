---
type: reference
area: workflow
status: active
---

# Automatización de Documentación

## Objetivo

Antes de cada commit, cualquier cambio de código, configuración, base de datos, deploy o lógica de negocio debe venir acompañado por documentación correspondiente cuando aplique.

## Forma general recomendada

La forma más portable entre herramientas es usar Git:

- `AGENTS.md` y `CLAUDE.md` guardan la regla para agentes.
- Un hook `pre-commit` puede bloquear commits cuando hay cambios relevantes sin docs.
- CI puede repetir la misma verificación para evitar bypass local.

Esto funciona independientemente de si el cambio viene de Claude Code, Codex, opencode, Antigravity, otro agente o edición manual.

## Hook local

El repo incluye `.githooks/pre-commit`. Para activarlo:

```bash
git config core.hooksPath .githooks
```

El hook revisa cambios staged. Si detecta cambios en código/configuración sin cambios staged en documentación, bloquea el commit y pide actualizar docs o justificarlo con `DOCS_NOT_NEEDED=1`.

Para un cambio donde realmente no aplica documentación:

```bash
DOCS_NOT_NEEDED=1 git commit -m "fix: ..."
```

En PowerShell:

```powershell
$env:DOCS_NOT_NEEDED = "1"
git commit -m "fix: ..."
Remove-Item Env:\DOCS_NOT_NEEDED
```

## Agentes y herramientas

Los hooks propios de cada agente son útiles, pero no son la capa principal de cumplimiento porque cambian por herramienta. Úsalos como ayuda local. La regla común debe vivir en Git y en CI.

Relacionado:

- [Estrategia de documentación](../explanation/documentacion.md)
- [Índice principal](../index.md)
