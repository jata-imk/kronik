# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 11 application with Inertia, Vue 3, Jetstream, Sanctum, PrimeVue, Tailwind, and Vite. Backend code lives in `app/`: controllers under `app/Http/Controllers`, requests under `app/Http/Requests`, services under `app/Services`, models under `app/Models`, and policies/providers in their Laravel-standard folders. Routes are split across `routes/web.php`, `routes/api.php`, and `routes/console.php`.

Frontend code lives in `resources/js`, with page components in `resources/js/Pages`, shared Vue components in `resources/js/Components`, composables in `resources/js/Composables`, and layouts in `resources/js/Layouts`. CSS and framework assets live in `resources/css` and `resources/external`. Public assets are in `public/`. Database migrations, seeders, factories, and data files are in `database/`. Tests are in `tests/Feature` and `tests/Unit`.

## Build, Test, and Development Commands

- `composer install` and `npm install`: install PHP and Node dependencies.
- `composer dev`: run Laravel server, queue listener, and Vite concurrently.
- `npm run dev`: start Vite only.
- `npm run build`: build production frontend assets.
- `php artisan test` or `vendor/bin/pest`: run the Pest/PHPUnit test suite.
- `vendor/bin/pint`: format PHP code with Laravel Pint.
- `npm run format`: format frontend/public files with Biome.

## Coding Style & Naming Conventions

Use 4-space indentation, LF line endings, UTF-8, and final newlines as defined in `.editorconfig`. PHP follows Laravel conventions: StudlyCase classes, singular Eloquent models, plural table names, and controller/request/service suffixes such as `ClienteController`, `StoreClienteRequest`, and `ClienteService`. Vue components use PascalCase filenames, composables use `useName.js`, and JavaScript uses double quotes per `biome.json`.

## Testing Guidelines

Use Pest with Laravel helpers. Put HTTP, auth, team, and workflow coverage in `tests/Feature`; keep isolated logic tests in `tests/Unit`. Name tests after the behavior under test, for example `UpdateTeamNameTest.php`. Add or update tests when changing controllers, requests, policies, services, migrations, or user-visible Inertia flows.

## Human-Readable Validation (VERY IMPORTANT)

Every validation error exposed to users must be written in clear Spanish. Raw translation keys such as `validation.required`, `validation.integer`, or `validation.in` must never reach the UI. When adding or changing validation rules, update `lang/es/validation.php`, define domain-specific messages and attribute names where useful, and add a regression assertion for user-facing flows. See `docs/reference/validacion-formularios.md`.

## Commit & Pull Request Guidelines

Recent history uses Conventional Commit-style messages, often in Spanish: `feat(menubar): ...`, `fix(menubar): ...`, `refactor: ...`, and `docs: ...`. Keep subjects imperative and scoped when useful. Pull requests should include a short problem/solution summary, linked issue or task when available, test results, migration or seed notes, and screenshots for UI changes.

## Security & Configuration Tips

Do not commit secrets from `.env`; update `.env.example` for new required settings. Treat SIC, credit bureau, geocoding, and scraper integrations as sensitive surfaces. Prefer configuration in `config/` and environment variables over hardcoded credentials or endpoints.

## Project Coordination

The repository's Notion page is `https://app.notion.com/p/1a261db7db7f800c809bf96e22e0d05a`. When the user refers to "el Notion del repositorio", use this page as the project source of context.

Notion is the source of truth for backlog order, scope, and product priorities. Local backlog files are supporting context, not a second priority list. Start documentation discovery at `docs/index.md` or `docs/topic/index.md`.

Backlogs 02 (PR #11), 01.5 (PR #12), 03 (PR #14), and 03.5 (PR #15) are complete. P0–P8 of the coordinated Backlogs 04–07 initiative are integrated for QA (PR #36), including SIC sandbox/history and prepayments/payoff/closure; follow ADR 0022/0023 and their QA guides. Production SIC remains a later validated delivery. 2FA and manual review are approved by the user. Backlog 02.5 (audit and activity matrix) remains intentionally deferred. Never enable production SIC or real-money operations with unvalidated provider, financial, tax, or compliance policies.

## Living Documentation (Required)

- **Diataxis:** put learning material in `docs/tutorials/`, task instructions in `docs/how-to/`, exact contracts/configuration/commands in `docs/reference/`, and rationale in `docs/explanation/`. Maintain one canonical explanation of each behavior and link to it from other documents.
- **Topic navigation:** maintain `docs/topic/<tema>.md` with a brief explanation and descriptive links grouped by document type. Register each topic in `docs/topic/index.md`. Every document in the four Diataxis sections must be reachable through a topic; shared documents may appear in several topics. Topic pages are navigation, not copies of guides, specifications or status reports. Add new durable documents to both their section index and the relevant topics in the same change. When moving or renaming a document, repair incoming links.
- **ADRs:** record architectural decisions in `docs/explanation/adr-*.md` when they affect domain models, module boundaries, integrations, security, deployment or technical choices with lasting consequences. Keep context, decision, consequences and status. Preserve historical rationale; link a superseding ADR when a decision changes. Routine documentation edits do not require an ADR.
- **Agent Notes:** maintain one file per backlog or transversal task in `docs/agent-notes/`, using `template.md`. Record branch/PR, dated state, evidence, blockers and next step; update it on handoff, PR/integration, deployment or QA feedback. On closure, condense it into final delivery evidence and actual remaining work. Do not store secrets or duplicate full Notion specifications or permanent operating instructions.
- **Update triggers:** changes to behavior, permissions, commands, seeders, migrations, environment variables, integrations or UI require reviewing the affected documentation before closing the task. Merge/deployment and later user validation also trigger updates to current summaries, Agent Notes and Notion. Do not leave a confirmed validation marked pending.
- **Evidence and scope:** distinguish implemented, integrated, deployed and validated states. Cite commits/PRs/tests for implementation and identify user-reported QA results as such. Record dates and the tested environment/client when known; do not infer unreported versions, scenarios or production readiness. Preserve historical test results with their commit instead of presenting them as current regression totals.
- **Completion:** update relevant docs, section/topic indexes, Agent Notes and Notion together; check local links and topic coverage. Hooks/CI only detect the presence of documentation changes and do not verify semantic accuracy or freshness. Do not satisfy that check by editing unrelated documentation. See `docs/reference/documentacion-viva.md`, `docs/reference/definicion-terminado.md` and `docs/reference/flujo-tareas-pruebas-pr.md`.
