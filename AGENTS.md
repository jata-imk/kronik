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

## Mandatory Audit Coverage

Every module creation or change must review and maintain its auditable events as part of the same delivery, without waiting for a separate user reminder. Implement the corresponding events using `App\Enums\ActivityEvent` and `App\Services\ActivityLogService`, update `docs/reference/auditoria-eventos.md`, and add or update behavioral tests. For purely visual changes or changes with no new auditable action, state why no additional event is needed; do not create duplicate or meaningless logs.

Cover critical business actions, user/account lifecycle (pending account creation, invitation send/resend and operational failure, initial password setup/account activation, password recovery and changes), team membership, roles/permissions, and administrative actions including Super Admin. Distinguish mail transport acceptance from actual delivery. Preserve actor, affected resource, result, and the original team/branch context; background jobs must distinguish the system executor from the initiating user and must not derive the original context from mutable user preferences.

Follow the reference contract's metadata allowlist. Never log passwords, password hashes, tokens, invitation/recovery URLs, secrets, SIC responses, document contents, or sensitive before/after values. Keep reserved reasons and evidence in permission-controlled domain records and reference them by identifier. Test event emission, actor/subject, team/branch isolation, permitted results, absence of sensitive data, and retry behavior where relevant. An enum case or a Jetstream event alone is not proof of a persisted audit event.

Maintain one audit matrix in `docs/reference/auditoria-eventos.md`, using the `modules` catalog as the inventory and reconciling it with the enum, actual emitters, and tests. Include cross-cutting authentication/profile/system flows even if they have no module entry. Track module, action/trigger, event code, actor/initiator, subject, result, team/branch context, allowed metadata, read permissions, implementation status, emitter, and test evidence. Explicitly justify non-applicable actions. Technical auditability supports evidence and does not by itself certify legal compliance; retention and integrity requirements must follow approved policies.

## Project Coordination

The repository's Notion page is `https://app.notion.com/p/1a261db7db7f800c809bf96e22e0d05a`. When the user refers to "el Notion del repositorio", use this page as the project source of context.

Notion is the source of truth for backlog order, scope, and product priorities. Architectural decisions belong in `docs/explanation/adr-*.md`; durable operating and usage documentation belongs in the appropriate Diataxis section. Keep temporary execution context and handoffs in one file per backlog under `docs/agent-notes/`, following its template. Agent Notes must not contain secrets or duplicate the full specification from Notion.

Backlogs 02 (PR #11), 01.5 (PR #12), 03 (PR #14), and 03.5 (PR #15) are complete. P0–P6 of the coordinated Backlogs 04–07 initiative are integrated. P7 sandbox/history and P8 prepayments/payoff/closure are implemented on the delivery branch pending review and integration; follow ADR 0022/0023 and their QA guides. Production SIC remains a later validated delivery. 2FA and manual review are approved by the user. The audit and activity matrix (historical reference 02.5) was reprioritized by the user on 2026-10-26 for 2026-10-31; coverage remains pending. See https://app.notion.com/p/21461db7db7f80d5b813c2899522a72b. Never enable production SIC or real-money operations with unvalidated provider, financial, tax, or compliance policies.
