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

**Route layering.** `bootstrap/app.php` → [backend/routes/api.php](backend/routes/api.php) applies the `v1` prefix → [backend/routes/api_v1.php](backend/routes/api_v1.php) `require`s one file per domain from `routes/api/v1/`. To add a domain, create the file and require it from `api_v1.php`. Every route file that exists has routes in it — don't leave empty ones lying around, and only register the actions a controller actually implements (`estimates` is `->only(['index','store'])` because editing isn't built).

**Request layering.** Route → `App\Http\Controllers\Api\V1\*` → `App\Http\Requests\*` (validation) → `App\Services\*` (business logic, `DB::transaction`) → Eloquent model → `App\Http\Resources\*`. Controllers stay thin; anything writing more than one table belongs in a Service. Services are injected either via constructor (`CustomerController`) or method injection (`TransportJobBudgetController::update`) — both patterns are in use.

**Auth** is Sanctum *personal access tokens*, not the stateful SPA cookie mode. `AuthController::login` returns `{user, token}`; every other route group is behind `auth:sanctum` and expects `Authorization: Bearer`. Exceptions render as JSON for `api/*` (configured in `bootstrap/app.php`).

**Human-readable codes** come from `NumberGenerator::generate($prefix, $modelClass)` — derives `PREFIX-000123` from the model's last id (`CUS`, `EST`, `JOB`). Not concurrency-safe by design.

## Domain model

**Cost and sell are captured together on the estimate, so profit is known at quotation time. Expenses are only the unexpected costs found later, and they eat into that profit.**

```
Base Profit  = Sell Price − Cost Price          (fixed when the job is created)
Final Profit = Base Profit − Total Extra Costs  (moves as expenses are recorded)
```

| Concept | Question it answers | Where it lives | Customer sees it? |
|---|---|---|---|
| **Estimate line** | what does this cost us, and what do we charge? | `estimate_items.cost_price` / `.sell_price` | only the sell side |
| **Estimate** | what's the deal worth? | `estimates.estimated_cost` / `_sell` / `_profit` | only `estimated_sell` |
| **Job** | what did we sign up for? | `transport_jobs.sell_price` / `.cost_price` / `.base_profit` | no |
| **Expense** | what went wrong and what did it cost? | `job_expenses.amount` / `.category` → `transport_jobs.extra_costs` | no |
| **Activity** | what happened to this job, and who did it? | `transport_job_activities` | no |

```
Customer → Estimate (+ EstimateItem[])   each line has cost_price and sell_price
                            estimated_cost 61,500 / estimated_sell 70,000 / profit 8,500
         → TransportJob     POST /estimates/{estimate}/convert
                            copies all three, estimate marked 'accepted'  → base_profit 8,500
         → TransportJobExpense[]  POST /jobs/{job}/expenses   e.g. 5,000 truck repair
                            → extra_costs 5,000               → final_profit 3,500
```

There is **no separate budget step** — it was merged into the estimate, so `job_budget_items`, `TransportJobBudget` and `PUT /jobs/{job}/budget` no longer exist. Don't reintroduce a cost table on the job; cost lives on the estimate lines.

`sell_price`, `cost_price` and `base_profit` are **copied** onto the job rather than read through the estimate relation, so editing an estimate later cannot silently rewrite a job already under way.

**`extra_costs`, `base_profit` and `final_profit` are derived, and only `TransportJob::recalculate()` writes them.** It re-sums the expenses and applies the two formulas above. `TransportJobExpenseService` calls it after every add/remove — never compute these totals anywhere else. `final_profit` is deliberately allowed to go negative: that's a real loss on the job.

**Job status is a forward-only workflow**, cast to the `App\Enums\JobStatus` backed enum (so `$job->status` is an enum instance in PHP and a plain string over the wire). `TransportJobService::TRANSITIONS` is the single map of which stage may follow which — one step at a time, no skipping, no going back, `completed` terminal — and it is enforced server-side on `PATCH /jobs/{job}/status`, which 422s on an illegal move. `frontend/src/utils/jobStatus.js` mirrors the map only to populate the dropdown; change one and change the other.

**`transport_jobs.internal_notes` is the crew's working notes and must never reach a customer**, so it is listed in the model's `$hidden`. A job serialised incidentally — nested inside an estimate on `GET /estimates`, say — therefore drops it, and `TransportJobResource` adds it back by hand for the job page. Anything new that serialises a job inherits the safe default; don't take it out of `$hidden`. (`remarks` is the separate, quotable line that came off the estimate.)

**Unexpected costs are full CRUD, nested under the job and scoped to it** — `POST/PATCH/DELETE /jobs/{job}/expenses[/{expense}]`, all on `->scopeBindings()`, so an expense id from another job is a 404 rather than a cross-job write. (The old flat `DELETE /expenses/{expense}` is gone.) `TransportJobExpenseService` owns all three, and each wraps the write, `recalculate()` and the timeline entry in one `DB::transaction` — a job whose expenses and totals disagree is worse than no change at all. Every action answers with the whole job, so the caller gets the recalculated `base_profit`/`extra_costs`/`final_profit` without computing or re-fetching them. `ExpenseRequest` serves create and edit alike; negative amounts are refused outright, since a negative cost would quietly hand profit back.

`job_expenses.category` is a plain string column cast to the `App\Enums\ExpenseCategory` backed enum — the list is enforced in PHP, so adding a category needs no migration, and promoting it to a table later stays a small change. `frontend/src/utils/expenseCategories.js` mirrors the list for the dropdown; change one and change the other. An unlisted value in the column would make the model throw on read, which is why `2026_08_17_100000_normalise_categories_on_job_expenses_table` exists.

**`transport_job_activities` is an append-only audit trail** — every change to a job writes one row and nothing ever updates or deletes them (the model sets `UPDATED_AT = null`, and there is no write endpoint). `ActivityService::log()` is the only writer, called once from each of the five places a job changes: creation and status in `TransportJobService`, notes in `TransportJobService`, add/remove in `TransportJobExpenseService`. Logging sits *after* the change, so a rejected transition leaves no trace. Read it at `GET /jobs/{job}/activities`, newest first, unpaginated — a job accrues a handful of events, so if that ever stops being true this is the endpoint that needs a limit. `created_by` is nullable on purpose: a seeder or console command has no signed-in user to credit.

`Customer`, `Estimate`, and `TransportJob` use `SoftDeletes`; the line-item tables do not.

**Table/model naming does not follow Eloquent conventions.** The migrations were written before the `Job*` → `TransportJob*` rename (the PHP classes have since been renamed to match their filenames; the tables were left alone), so:

| Model | Actual table | FK column |
|---|---|---|
| `TransportJob` | `transport_jobs` | — |
| `TransportJobExpense` | `job_expenses` | `job_id` |

`TransportJobExpense` therefore declares `$table` explicitly, and `TransportJob::expenses()` passes `'job_id'` explicitly — Eloquent's inferred `transport_job_expenses` / `transport_job_id` would not match the schema.

**When adding a child table of `transport_jobs`, never use a bare `->constrained()` on a `job_id` column.** Laravel infers the table from the column name (`job_id` → `jobs`) and Laravel's own **queue** `jobs` table exists, so the FK is created against the wrong table with no error. Write `->constrained('transport_jobs')`. Migration order matters too: `transport_jobs` is timestamped `2026_08_11_220900` so it precedes its children.

## Frontend architecture

- `pages/` are route targets, `components/layout/` holds shared chrome (`Navbar`, `Sidebar`) plus feature component folders (`estimate/`, `ui/`) — the `layout/` nesting is historical, not meaningful.
- `services/*.js` are thin per-domain wrappers over the shared axios instance and are the only place URLs are written. `stores/*.js` (Pinia, options-style) hold state and call the services; components call stores. Don't call axios directly from a component.
- The axios response interceptor clears the token and hard-redirects to `/login` on any 401, so stores don't handle auth failures themselves.
- `router/index.js` guards on `meta.requiresAuth` / `meta.guest` and hydrates the user from the token before resolving. Note only the `/` route currently sets `requiresAuth` — `/customers`, `/estimates`, etc. are unguarded.
- `@/` aliases `src/` (declared in both `vite.config.js` and `jsconfig.json`).
- Lazy-load page components with `() => import(...)` in the router, as the customer and estimate routes do.
- Every file under `src/` is reachable from `main.js` — there are no leftover stubs or scaffolding. Keep it that way: an empty `.vue` file is **not** a valid SFC and fails the build with "At least one `<template>` or `<script>` is required", so never commit a placeholder component.
- `money()` from `@/utils/money` formats every displayed figure; don't redefine it per component.
- All styling is one global stylesheet, `src/style.css`, imported once in `main.js` — no CSS framework and no scoped component styles. It styles bare elements (`input`, `table`, `button`, `label`, `h1`–`h3`), so new pages generally need no classes beyond `.card`, `.page-head`, `.grid`, `.field` and `.actions`. Colours come from CSS variables on `:root`; change `--accent` to re-theme.
- Every page renders inside `AppLayout` — they are children of the `/` route in `router/index.js`, so `meta.requiresAuth` on that parent covers all of them.
- The profit formula is shown, not just stored: the `.chain` block (dashboard and job detail) lays out `sell − cost = base profit − unexpected = final profit` as literal steps, so the numbers explain themselves. Reuse it rather than inventing another totals layout.

## Conventions

- Backend files use a loose, blank-line-heavy formatting style that Pint will collapse; run `vendor/bin/pint` on files you touch rather than matching the existing spacing by hand. The frontend's `.prettierrc` (no semicolons, single quotes) has never been applied — every file under `src/` fails `prettier --check`, so match the surrounding style rather than running `npm run format` on a file and reformatting it alone.
- **Failing a business rule is a 422, not an exception.** `ValidationException::withMessages()` is how a refused write answers — an illegal status move, converting an estimate twice. A bare `throw new \Exception` renders as a 500 and reads as a server fault.
- **Searches with `orWhere` must be grouped in a closure.** Every searchable model here is soft-deleted, and the scope is a separate `where`; ungrouped alternatives bind as `(deleted_at is null and a) or b or c` and hand back deleted rows. See `CustomerController::index`.
- There is no server-rendered login page, so `bootstrap/app.php` sets `redirectGuestsTo(fn () => null)`. Without it the guest redirect resolves `route('login')`, which does not exist, and every unauthenticated non-JSON request 500s with a stack trace instead of returning 401.
- Money columns are `decimal(15,2)`, quantities `decimal(12,2)`. Statuses are DB `enum`s — adding a value requires a migration (`estimates.status`: draft/sent/accepted/rejected/expired; `transport_jobs.status`: draft/confirmed/assigned/in_transit/delivered/completed).
- The local DB is MySQL (`finance_management`, XAMPP, root with no password) configured in `backend/.env`, which is gitignored. A stale `backend/database/database.sqlite` from earlier SQLite use is also gitignored and no longer used. Schema changes go through a migration, never a direct edit.
- Money columns are **not** cast on `TransportJob`, so they come back as strings (`"5000.00"`) on MySQL and as numbers (`5000`) on the sqlite the tests run against. Compare them numerically in assertions — `assertJsonPath('data.extra_costs', '5000.00')` passes locally and fails in the suite. `TransportJobExpense::$amount` *is* `decimal:2` cast, so that one is a string either way.
- **The app runs on MySQL but the test suite runs on sqlite `:memory:`** (`phpunit.xml`), so a migration written in raw MySQL fails every test at boot rather than failing one assertion. Prefer the schema builder; where a driver genuinely differs — MySQL has an `enum` type, sqlite fakes one with a varchar plus a check constraint — branch on `DB::connection()->getDriverName()`, as `2026_08_17_090000_change_status_enum_on_transport_jobs_table` does.

## Not yet implemented

The estimate → job → budget → expenses chain is complete end to end. Remaining gaps:

- **Editing an estimate.** Estimates can be created and listed but not changed — there is no `show`/`update`/`destroy` on `EstimateController` and no edit page. Since cost and sell now live on the estimate lines, this is the biggest remaining gap: a mispriced quote can only be fixed by creating a new one.
- **Investors and settings.** Neither exists — no routes, no models. `reports.php` has only `GET /reports/summary`, which feeds the dashboard.
- **Nothing reads job status yet.** Jobs can now be advanced through the workflow, but no query filters on it — the dashboard's totals still cover every job ever created rather than only live or completed ones. There is also no way to cancel or abandon a job: the enum has no such value, so the migration off the old list mapped `cancelled` to `draft`.
- **There are no roles or policies.** Every route is gated by `auth:sanctum` alone, so any signed-in user can advance a job, edit its notes, or record a cost. The activity timeline records *who* did each of those, which is what makes the absence survivable for now.
