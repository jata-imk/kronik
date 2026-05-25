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

## Commit & Pull Request Guidelines

Recent history uses Conventional Commit-style messages, often in Spanish: `feat(menubar): ...`, `fix(menubar): ...`, `refactor: ...`, and `docs: ...`. Keep subjects imperative and scoped when useful. Pull requests should include a short problem/solution summary, linked issue or task when available, test results, migration or seed notes, and screenshots for UI changes.

## Security & Configuration Tips

Do not commit secrets from `.env`; update `.env.example` for new required settings. Treat SIC, credit bureau, geocoding, and scraper integrations as sensitive surfaces. Prefer configuration in `config/` and environment variables over hardcoded credentials or endpoints.
