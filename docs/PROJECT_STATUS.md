# Project Status — Finance-Managment

This document describes the current repository state (as of 2026-08-13), what we implemented, how key features and flows work, important files/migrations/services to review, how to run the system, and next steps / known issues.

---

**Repository Overview**:
- **Backend:** Laravel-based JSON API in `backend/` (Laravel 13, PHP 8.3+). See [backend/README.md](backend/README.md#L1).
- **Frontend:** Vue 3 SPA in `frontend/` (Vite 8, Pinia, vue-router). Entrypoint: `frontend/src/main.js`.

**High-level goal**: manage estimates → convert estimate to transport job → create job budget → post expenses → calculate profit (quoted − cost).


## What we have implemented (features)
- Estimates: creation and listing (incomplete edit flow; see "gaps" below).
- Transport jobs: created by converting an accepted estimate. Jobs store `quoted_amount`, `planned_cost`, `actual_cost`, `profit`.
- Job budget items: `job_budget_items` table holds planned items (title/category/quantity/unit_cost/total/notes).
- Job expenses: `job_expenses` table holds actual expenses (title/category/amount/expense_date/notes).
- Money and totals are managed by `TransportJob::recalculate()` and services call it after modifications.
- Auth: Sanctum personal access tokens; `AuthController::login` returns `{user, token}`.

Recent bugfixes we applied during the investigation:
- Fixed failing convert endpoint (500) due to FK mismatch: created migration `2026_08_12_182500_fix_job_foreign_keys.php` that drops and re-creates `job_id` foreign keys to point to `transport_jobs` rather than `jobs`.
  - Migration: [backend/database/migrations/2026_08_12_182500_fix_job_foreign_keys.php](backend/database/migrations/2026_08_12_182500_fix_job_foreign_keys.php#L1)
  - Reason: existing FK in DB referenced a non-existent/wrong table `jobs` and caused Integrity Constraint violations when inserting budget items during the estimate→convert flow.
  - Status: migration applied locally.


## Architecture and important locations
- Route layering: [backend/bootstrap/app.php](backend/bootstrap/app.php#L1) → [backend/routes/api.php](backend/routes/api.php#L1) applies `v1` prefix → [backend/routes/api_v1.php](backend/routes/api_v1.php#L1) `require`s per-domain route files under `routes/api/v1/`.
- Controllers: `App\Http\Controllers\Api\V1\*` (thin controllers, validation via `App\Http\Requests`). Example: see [backend/app/Http/Controllers](backend/app/Http/Controllers#L1).
- Services (business logic): `backend/app/Services/` — these contain transactional logic called by controllers.
  - `EstimateService.php`: [backend/app/Services/EstimateService.php](backend/app/Services/EstimateService.php#L1)
  - `TransportJobService.php`: [backend/app/Services/TransportJobService.php](backend/app/Services/TransportJobService.php#L1)
  - `TransportJobBudgetService.php`: [backend/app/Services/TransportJobBudgetService.php](backend/app/Services/TransportJobBudgetService.php#L1)
  - `TransportJobExpenseService.php`: [backend/app/Services/TransportJobExpenseService.php](backend/app/Services/TransportJobExpenseService.php#L1)
- Models of interest:
  - `Estimate`: [backend/app/Models/Estimate.php](backend/app/Models/Estimate.php#L1)
  - `TransportJob`: [backend/app/Models/TransportJob.php](backend/app/Models/TransportJob.php#L1)
  - `TransportJobBudget`: [backend/app/Models/TransportJobBudget.php](backend/app/Models/TransportJobBudget.php#L1)
  - `TransportJobExpense`: [backend/app/Models/TransportJobExpense.php](backend/app/Models/TransportJobExpense.php#L1)
- Number generation helper: `backend/app/Helpers/NumberGenerator.php` — used to produce human-readable codes like `CUS-000123`.


## Database schema notes
- Key tables and mapping details:
  - `transport_jobs` — stores transport job records. Migration: [backend/database/migrations/2026_08_11_220900_create_transport_jobs_table.php](backend/database/migrations/2026_08_11_220900_create_transport_jobs_table.php#L1)
  - `job_budget_items` — planned budget items. Migration: [backend/database/migrations/2026_08_11_220948_create_job_budget_items_table.php](backend/database/migrations/2026_08_11_220948_create_job_budget_items_table.php#L1)
  - `job_expenses` — expense lines. Migration: [backend/database/migrations/2026_08_11_221024_create_job_expenses_table.php](backend/database/migrations/2026_08_11_221024_create_job_expenses_table.php#L1)
- Note: models explicitly set `$table` where convention doesn't match (the codebase uses `transport_jobs` but earlier names used `job_*` tables).
- Money columns use `decimal(15,2)`; quantities `decimal(12,2)`.


## Key flows (end-to-end)
Below are the main feature flows and where code lives for each step.

1) Estimate creation and acceptance
- Frontend: estimate form submits to API endpoint in `frontend/services/estimateService.js` (see `frontend/src/services/`).
- Backend: `EstimateController@store` → `App\Http\Requests\EstimateRequest` validates → `EstimateService` persists lines.
- Status: create/list works; edit/update endpoints are stubs (see Gaps).

2) Convert Estimate → TransportJob (the flow that previously failed)
- Trigger: frontend calls `POST /api/v1/estimates/{estimate}/convert` via `transportJobService.js`.
- Backend path:
  - `routes/api/v1/estimates.php` (or equivalent route file) defines `convert` route.
  - `EstimateController@convert` (or a `TransportJobController`) invokes `TransportJobService::createFromEstimate()`.
  - `TransportJobService` creates a `TransportJob` record and copies estimate line titles into `job_budget_items` (initially with zero cost) — this is intentional: budget items are operation checklist lines.
  - After inserting budget lines and job row, `TransportJob::recalculate()` is called to derive totals.
- Recent issue: insertion into `job_budget_items` failed when `job_id` FK referenced `jobs` table. We fixed this by ensuring FK points to `transport_jobs`.
- Files to inspect: [backend/app/Services/TransportJobService.php](backend/app/Services/TransportJobService.php#L1) and [backend/app/Models/TransportJob.php](backend/app/Models/TransportJob.php#L1).

3) Budget editing
- API: `TransportJobBudgetService` provides create/update/remove of `job_budget_items` and calls `TransportJob::recalculate()` after changes.
- Frontend: job budget UI calls budget service endpoints in `frontend/src/services` (see `frontend/src/services/transportJobService.js`).

4) Expense posting
- API: `TransportJobExpenseService` creates `job_expenses` rows and triggers `TransportJob::recalculate()` so `actual_cost` and `profit` update.
- Frontend: expense forms call the expenses endpoints in `frontend` services.


## Where to run and commands
Backend (from repo root):
```bash
cd backend
composer install
cp .env.example .env    # adjust local DB creds if needed
php artisan key:generate
php artisan migrate      # or run only specific migration file if needed
php artisan serve --port=8000
```
Frontend (separate shell):
```bash
cd frontend
npm install
npm run dev
```
Notes: the SPA expects backend API at `http://127.0.0.1:8000/api/v1` (hardcoded in `frontend/src/api/axios.js`).


## Tests & verification
- Backend tests exist under `backend/tests/`. Run:
```bash
cd backend
php artisan test
```
- After the FK migration fix we verified locally by triggering the convert endpoint — no Integrity Constraint error was reproduced.


## Conventions & development notes
- Controllers are thin; move multi-table or transactional logic into `app/Services`.
- Use `TransportJob::recalculate()` as the single source of truth for derived money columns (do NOT re-sum in services manually elsewhere).
- Migration order matters. `transport_jobs` migration precedes child migrations: `2026_08_11_220900_create_transport_jobs_table.php` (transport jobs) → child tables.
- When adding FKs that reference `transport_jobs`, use `->constrained('transport_jobs')` (do not rely on Laravel's `job_id` → `jobs` inference).


## What we changed during the support session
- Diagnosed and fixed a 500 caused by:
  - Error: Integrity constraint violation when inserting into `job_budget_items`:
    SQL showed the FK referenced `jobs` (`job_budget_items_job_id_foreign` → `jobs(id)`) causing failures.
  - Action taken: added migration `2026_08_12_182500_fix_job_foreign_keys.php` which drops and re-adds the `job_id` foreign keys on `job_budget_items` and `job_expenses` to reference `transport_jobs`. The migration was applied locally via `php artisan migrate --path=database/migrations/2026_08_12_182500_fix_job_foreign_keys.php`.
- Confirmed the convert POST now proceeds without the FK SQL error.


## Gaps / Not yet implemented
- Editing an existing estimate: `EstimateController@show/update/destroy` are stubs — editing flow is not implemented yet.
- Domain areas with empty route files: `routes/api/v1/{investors,reports,settings}.php` are placeholders.
- Several frontend page stubs exist as zero-byte or minimal components and need content (see CLAUDE.md top-level notes on which files are zero-byte).


## Recommendations / Next steps
- Implement `EstimateController@update` to allow editing estimates and wire the frontend edit page.
- Add API integration tests for the convert flow (create estimate → accept → convert → assert `transport_jobs`, `job_budget_items` created).
- Add schema verification CI step that ensures migrations are idempotent and won't error if tables already exist in non-fresh environments.
- Replace any hardcoded API origins in the SPA with an env-driven value (or keep note to run backend on :8000).


## Quick reference (important files)
- Backend migrations:
  - [backend/database/migrations/2026_08_11_220900_create_transport_jobs_table.php](backend/database/migrations/2026_08_11_220900_create_transport_jobs_table.php#L1)
  - [backend/database/migrations/2026_08_11_220948_create_job_budget_items_table.php](backend/database/migrations/2026_08_11_220948_create_job_budget_items_table.php#L1)
  - [backend/database/migrations/2026_08_11_221024_create_job_expenses_table.php](backend/database/migrations/2026_08_11_221024_create_job_expenses_table.php#L1)
  - FK fix migration: [backend/database/migrations/2026_08_12_182500_fix_job_foreign_keys.php](backend/database/migrations/2026_08_12_182500_fix_job_foreign_keys.php#L1)
- Backend services:
  - [backend/app/Services/EstimateService.php](backend/app/Services/EstimateService.php#L1)
  - [backend/app/Services/TransportJobService.php](backend/app/Services/TransportJobService.php#L1)
  - [backend/app/Services/TransportJobBudgetService.php](backend/app/Services/TransportJobBudgetService.php#L1)
  - [backend/app/Services/TransportJobExpenseService.php](backend/app/Services/TransportJobExpenseService.php#L1)
- Models: [backend/app/Models](backend/app/Models#L1)


---

If you want, I can:
- Expand this into a `README`-style quickstart and add example curl requests for the convert flow.
- Open a PR with the documentation and the migration already included.
- Add an API integration test that reproduces the original error and asserts the fixed behavior.

