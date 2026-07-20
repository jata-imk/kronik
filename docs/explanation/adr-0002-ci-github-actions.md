# ADR 0002: CI con GitHub Actions

## Estado

Aceptada.

## Contexto

Kronik se trabaja desde mas de una PC y con apoyo de agentes. Los hooks locales ayudan, pero dependen de que cada maquina tenga `.githooks` activado. El proyecto tambien combina Laravel, Inertia/Vue, migraciones MariaDB, seeders y permisos Spatie, por lo que una validacion solo local es fragil.

## Decision

Agregar CI real con GitHub Actions en `.github/workflows/ci.yml`.

El workflow debe correr en PRs y pushes relevantes, levantar MariaDB, instalar dependencias PHP/Node, verificar documentacion, ejecutar `php artisan test` y compilar frontend con `npm run build`.

## Consecuencias

- Las PRs tendran una validacion reproducible fuera de la PC local.
- Los hooks siguen siendo utiles para detectar problemas antes de subir cambios.
- Si CI falla, la PR no debe considerarse lista.
- Los flujos e2e con Playwright quedan como siguiente capa de calidad para flujos criticos.
