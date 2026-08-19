# Repository Guidelines

## Project Structure & Module Organization

Two independent applications:

- `backend/` is a Laravel 13 JSON API (PHP 8.3+, MySQL, Sanctum).
- `frontend/` is a Vue 3 single-page application built with Vite, Pinia, and Vue Router.
- `docs/` contains project status notes; `CLAUDE.md` records domain decisions.

Backend code follows Laravel conventions: controllers in `app/Http/Controllers/Api/V1`, validation in `app/Http/Requests`, business logic in `app/Services`, resources in `app/Http/Resources`, and migrations in `database/migrations`. Frontend pages live in `src/pages`, shared UI in `src/components`, state in `src/stores`, and API wrappers in `src/services`.

## Build, Test, and Development Commands

Run these from the relevant directory.

```sh
cd backend && composer setup       # install dependencies, configure, migrate, build
cd backend && php artisan serve    # serve the API at :8000
cd backend && composer test        # run the Laravel/PHPUnit suite
cd backend && vendor/bin/pint      # format PHP changes
cd frontend && npm install
cd frontend && npm run dev         # start the SPA dev server
cd frontend && npm run build       # produce a production build
cd frontend && npm run format      # format src/ with Prettier
```

The SPA expects `http://127.0.0.1:8000/api/v1`.

## Coding Style & Naming Conventions

Use Laravel/PHP conventions: `StudlyCase` classes, `camelCase` methods, singular models, and timestamped migration names such as `2026_08_19_create_widgets_table.php`. Keep controllers thin; put multi-model writes in services. Add database changes through migrations only.

Use Vue SFCs named in `PascalCase` (for example, `CustomerCreate.vue`), camelCase JavaScript modules, and `@/` imports for `src/`. Components call Pinia stores; stores call `services/`; only services should write API URLs. Follow the surrounding frontend formatting; Prettier is configured for no semicolons and single quotes.

## Testing Guidelines

Backend tests use PHPUnit under `backend/tests/Feature` and `backend/tests/Unit`. Name tests by behavior, for example `TransportJobProfitTest.php`; use `php artisan test --filter=TransportJobProfitTest` for focused work. The suite uses in-memory SQLite, so migrations must remain SQLite-compatible. No frontend test runner is configured; run `npm run build` after UI changes.

## Commit & Pull Request Guidelines

Recent commits favor short imperative summaries, such as `Add unexpected cost update endpoint`. Use a clear, scoped subject; avoid vague messages like `asd`. PRs should explain the user-visible change, note migrations or configuration requirements, link issues, include test/build results, and attach screenshots for UI changes.

## Security & Configuration

Do not commit `.env` files, tokens, or local database credentials. API endpoints use Sanctum bearer tokens; preserve authentication middleware when adding routes. Never expose internal job notes in customer-facing responses.
