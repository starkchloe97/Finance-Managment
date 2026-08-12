# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

Two independent applications, no shared build:

- `backend/` — Laravel 13 (PHP 8.3+) JSON API, MySQL, Sanctum token auth
- `frontend/` — standalone Vue 3 SPA (Vite 8, Pinia, vue-router), talks to the backend over HTTP

The frontend is **not** wired through `laravel-vite-plugin`. `backend/package.json` (Tailwind + Vite) only builds the Laravel Blade welcome page and is unrelated to the SPA. The SPA hardcodes the API origin in [frontend/src/api/axios.js](frontend/src/api/axios.js) as `http://127.0.0.1:8000/api/v1` — the backend must be served on port 8000 for the SPA to work.

## Commands

Backend (`cd backend`):

```sh
composer setup                     # install, .env, key:generate, migrate, npm build
composer dev                       # serve + queue:listen + pail logs + vite, concurrently
php artisan serve                  # API only, on :8000 (what the SPA expects)
composer test                      # config:clear then artisan test
php artisan test --filter=NameOfTest   # single test or single method
php artisan migrate:fresh --seed   # reset db, seeds test@example.com / password
vendor/bin/pint                    # format PHP (Laravel Pint)
```

Frontend (`cd frontend`):

```sh
npm run dev        # Vite dev server
npm run build
npm run format     # prettier over src/ (no semicolons, single quotes, width 100)
```

There is no frontend test runner or linter configured — only Prettier.

## Backend architecture

**Route layering.** `bootstrap/app.php` → [backend/routes/api.php](backend/routes/api.php) applies the `v1` prefix → [backend/routes/api_v1.php](backend/routes/api_v1.php) `require`s one file per domain from `routes/api/v1/`. To add endpoints, create/extend the domain file and require it from `api_v1.php`. `investors.php`, `expenses.php`, `reports.php`, and `settings.php` exist but are empty placeholders.

**Request layering.** Route → `App\Http\Controllers\Api\V1\*` → `App\Http\Requests\*` (validation) → `App\Services\*` (business logic, `DB::transaction`) → Eloquent model → `App\Http\Resources\*`. Controllers stay thin; anything writing more than one table belongs in a Service. Services are injected either via constructor (`CustomerController`) or method injection (`TransportJobBudgetController::update`) — both patterns are in use.

**Auth** is Sanctum *personal access tokens*, not the stateful SPA cookie mode. `AuthController::login` returns `{user, token}`; every other route group is behind `auth:sanctum` and expects `Authorization: Bearer`. Exceptions render as JSON for `api/*` (configured in `bootstrap/app.php`).

**Human-readable codes** come from `NumberGenerator::generate($prefix, $modelClass)` — derives `PREFIX-000123` from the model's last id (`CUS`, `EST`, `JOB`). Not concurrency-safe by design.

## Domain model

**The one distinction the whole system rests on: selling price, expected cost, and actual cost are three separate things.**

| Concept | Question it answers | Where it lives | Customer sees it? |
|---|---|---|---|
| **Estimate** | what will we charge? | `estimates.total` | yes — it's the quote |
| **Budget** | what do we expect to spend? | `transport_jobs.planned_cost` | never |
| **Expenses** | what did we actually spend? | `transport_jobs.actual_cost` | never |

Profit is `quoted_amount − cost`. So an estimate holds **no cost and no margin** — its line items are what the customer pays for each item, and its total is simply their sum. Do not add markup, margin, or cost fields to `Estimate`; internal money belongs on the job.

```
Customer → Estimate (+ EstimateItem[])   total = sum of lines            e.g. 70,000
         → TransportJob     POST /estimates/{estimate}/convert
                            quoted_amount = estimate total, estimate marked 'accepted'
         → TransportJobBudget[]   PUT /jobs/{job}/budget (full replace)  e.g. 61,500
                            → planned_cost                               → profit 8,500
         → TransportJobExpense[]  POST /jobs/{job}/expenses              e.g. 66,500
                            → actual_cost                                → profit 3,500
```

The quote never changes once promised: if costs overrun, `planned_cost`/`actual_cost` rise and profit shrinks, but `quoted_amount` stays put. Converting copies the estimate's line titles into budget lines at **zero cost** — they're a checklist for operations to price, not the customer's prices.

**All three money columns are derived, and only `TransportJob::recalculate()` writes them.** It re-sums the budget lines and expenses and sets `profit = quoted_amount − cost`, where cost is the actual spend once any expense exists and the budget before that. Both `TransportJobBudgetService` and `TransportJobExpenseService` call it after changing their rows — never compute these totals anywhere else, or the two paths will drift.

`Customer`, `Estimate`, and `TransportJob` use `SoftDeletes`; the line-item tables do not.

**Table/model naming does not follow Eloquent conventions.** The migrations were written before the `Job*` → `TransportJob*` rename (the PHP classes have since been renamed to match their filenames; the tables were left alone), so:

| Model | Actual table | FK column |
|---|---|---|
| `TransportJob` | `transport_jobs` | — |
| `TransportJobBudget` | `job_budget_items` | `job_id` |
| `TransportJobExpense` | `job_expenses` | `job_id` |

Both models therefore declare `$table` explicitly, and `TransportJob::budgetItems()` / `expenses()` pass `'job_id'` explicitly — Eloquent's inferred `transport_job_budgets` / `transport_job_id` would not match the schema.

**When adding a child table of `transport_jobs`, never use a bare `->constrained()` on a `job_id` column.** Laravel infers the table from the column name (`job_id` → `jobs`) and Laravel's own **queue** `jobs` table exists, so the FK is created against the wrong table with no error. Write `->constrained('transport_jobs')`. Migration order matters too: `transport_jobs` is timestamped `2026_08_11_220900` so it precedes its children.

## Frontend architecture

- `pages/` are route targets, `components/layout/` holds shared chrome (`Navbar`, `Sidebar`) plus feature component folders (`estimate/`, `ui/`) — the `layout/` nesting is historical, not meaningful.
- `services/*.js` are thin per-domain wrappers over the shared axios instance and are the only place URLs are written. `stores/*.js` (Pinia, options-style) hold state and call the services; components call stores. Don't call axios directly from a component.
- The axios response interceptor clears the token and hard-redirects to `/login` on any 401, so stores don't handle auth failures themselves.
- `router/index.js` guards on `meta.requiresAuth` / `meta.guest` and hydrates the user from the token before resolving. Note only the `/` route currently sets `requiresAuth` — `/customers`, `/estimates`, etc. are unguarded.
- `@/` aliases `src/` (declared in both `vite.config.js` and `jsconfig.json`).
- Lazy-load page components with `() => import(...)` in the router, as the customer and estimate routes do.
- Several files are still **zero-byte** stubs: `components/layout/ui/Base{Button,Card,Input}.vue`, `estimate/EstimateHeader.vue`, `estimate/EstimateRemarks.vue`, `estimate/EstimateSummary.vue`, and the top-level `pages/{Estimates,Jobs,Investors,Reports,Settings}.vue`. An empty `.vue` file is **not** a valid SFC — importing one fails the build with "At least one `<template>` or `<script>` is required", so give a stub content before wiring it up. `stores/counter.js` and `services/systemService.js` are scaffolding leftovers.
- All styling is one global stylesheet, `src/style.css`, imported once in `main.js` — no CSS framework and no scoped component styles. It styles bare elements (`input`, `table`, `button`, `label`, `h1`–`h3`), so new pages generally need no classes beyond `.card`, `.page-head`, `.grid`, `.field` and `.actions`. Colours come from CSS variables on `:root`; change `--accent` to re-theme.
- Only `/` renders inside `AppLayout` (via `Home.vue`). `/customers`, `/estimates`, etc. are top-level routes, so they render without the sidebar/navbar chrome.

## Conventions

- Backend files use a loose, blank-line-heavy formatting style that Pint will collapse; run `vendor/bin/pint` on files you touch rather than matching the existing spacing by hand.
- Money columns are `decimal(15,2)`, quantities `decimal(12,2)`. Statuses are DB `enum`s — adding a value requires a migration (`estimates.status`: draft/sent/accepted/rejected/expired; `transport_jobs.status`: draft/ready/budgeted/funded/in_progress/completed/closed/cancelled).
- The local DB is MySQL (`finance_management`, XAMPP, root with no password) configured in `backend/.env`, which is gitignored. A stale `backend/database/database.sqlite` from earlier SQLite use is also gitignored and no longer used. Schema changes go through a migration, never a direct edit.

## Not yet implemented

The estimate → job → budget → expenses chain is complete end to end. Remaining gaps:

- **Editing an estimate.** `EstimateController@show/update/destroy` are still empty stubs, so `/estimates/:id/edit` is a placeholder page. Estimates can be created and listed but not changed.
- **Investors, reports, settings.** `routes/api/v1/{investors,reports,settings}.php` are empty files with no models behind them.
- `pages/{Customers,Estimates,Jobs,Investors,Reports,Settings}.vue` at the top level of `pages/` are unused leftovers — the live pages are the ones in `pages/customers/`, `pages/estimates/` and `pages/jobs/`.
