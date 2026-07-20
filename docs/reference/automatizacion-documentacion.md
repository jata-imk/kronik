# Automatizacion de Documentacion

## Objetivo

Antes de cada commit y PR, cualquier cambio de codigo, configuracion, base de datos, frontend, tests o reglas de negocio debe venir acompañado por documentacion correspondiente cuando aplique.

## Hook Local

El repo incluye `.githooks/pre-commit`. Para activarlo:

```bash
git config core.hooksPath .githooks
```

El hook revisa cambios staged. Si detecta cambios relevantes sin cambios staged en documentacion, bloquea el commit y pide actualizar docs o justificarlo:

```bash
DOCS_NOT_NEEDED=1 git commit -m "fix: ..."
```

En PowerShell:

```powershell
$env:DOCS_NOT_NEEDED = "1"
git commit -m "fix: ..."
Remove-Item Env:\DOCS_NOT_NEEDED
```

## CI

El workflow de GitHub Actions repite la misma regla para evitar depender de hooks locales.

Relacionado:

- [CI](ci.md)
- [Definicion de terminado](definicion-terminado.md)
