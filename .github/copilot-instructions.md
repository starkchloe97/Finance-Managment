# Copilot instructions for Finance Management

This repo is a Laravel 13 + Vue 3 financial management app for transport operations, with a backend API and a Vue + Pinia frontend. The app manages customers, estimates, transport jobs, vehicle contracts, investments, loans, company capital, and dashboard reporting.

## Build, test, and lint commands

Backend:

```bash
cd backend
composer install
php artisan migrate:fresh --seed
php artisan test
php artisan test --filter=TransportJobStatusTest
php artisan test tests/Feature/LoanManagementTest.php
php artisan pint
```

Frontend:

```bash
cd frontend
npm install
npm run dev
npm run build
npx prettier --check "src/**/*.{js,vue}"
```

Typical local setup from repo root:

```bash
cd backend && composer install && php artisan migrate:fresh --seed
cd ../frontend && npm install
```

Run the app together in development:

```bash
cd backend && composer run dev
# or separate terminals:
# Terminal 1: cd backend && php artisan serve
# Terminal 2: cd frontend && npm run dev
```

## High-level architecture

### Backend

- `backend/app/Http/Controllers/Api/V1/` contains the API surface for customers, estimates, jobs, investors, investments, loans, company capital, and reports.
- `backend/app/Services/` contains business logic; prefer adding or updating service classes instead of embedding calculations in controllers.
- Models in `backend/app/Models/` map directly to accounting and operations domains (customers, estimates, jobs, investments, investors, loan borrowings, vehicles/contracts, company capital drafts).
- `backend/database/migrations/` defines the schema and includes domain-specific lifecycle changes, enums, and soft-delete-safe patterns.
- `backend/routes/api_v1.php` and `backend/routes/api/*.php` register the versioned API endpoints. The app is organized by feature rather than by a single monolithic controller.
- Financial flows depend on enum-driven status handling and resource serialization (`app/Http/Resources/`), so keep API payload keys and enum names consistent with the frontend.

### Frontend

- `frontend/src/pages/` holds the route pages; most domain pages live under feature folders such as `customers/`, `estimates/`, `jobs/`, `investments/`, `loans/`, and `assets/`.
- `frontend/src/stores/` is the source of truth for most data access and mutation state; each store owns one business domain (`authStore`, `customerStore`, `investmentStore`, `loanStore`, etc.).
- `frontend/src/services/` wraps axios access for each API resource.
- `frontend/src/router/index.js` defines authenticated routes with `requiresAuth` and route-specific breadcrumbs. The application expects a logged-in user to access protected pages.
- `frontend/src/components/` contains reusable UI plus domain forms, tables, and status panels used across pages.

## Key repository conventions

- Use the existing domain split: backend business logic in `app/Services`, API endpoints in `app/Http/Controllers/Api/V1`, responses in `app/Http/Resources`, and frontend state in `src/stores`.
- Preserve Laravel conventions: the API is versioned under `v1`, uses Sanctum auth, and expects `Authorization: Bearer <token>` on protected routes.
- Treat financial data as money-aware: decimals are stored with 2-place precision and calculated through domain services instead of ad hoc arithmetic in views or controllers.
- Soft deletes are part of the data model. Do not bypass model-level query behavior by writing raw SQL for normal record access.
- Keep status enums and UI labels aligned between backend and frontend. The repo is heavily enum-driven (`InvestmentStatus`, `JobStatus`, `LoanStatus`, `AssetStatus`, etc.).
- Follow the app’s workflow model:
  - Customers → Estimates → Transport jobs
  - Investments → allocations → settlements/distributions
  - Loans → repayments / outstanding balance tracking
  - Company capital → drafts / transactions / availability checks
- Prefer updating matching service/store files when a feature is touched; the frontend relies on Pinia stores and the backend relies on service classes to keep calculations centralized.
- For tests, use the existing Laravel feature tests under `backend/tests/Feature/` and filter to a single test case when validating a focused change.

## Practical guidance for edits

- Before changing a business rule, inspect the relevant service and model together; do not patch just the API controller.
- When adding or updating an endpoint, keep route registration, request validation, controller logic, and frontend service/store usage in sync.
- When changing a financial calculation, trace the matching `Services/*.php` code path and the relevant tests before editing.
- For frontend work, prefer existing stores, page patterns, and shared UI components over ad hoc local state.

## Security and framework notes

- Authenticated routes use the sessionless Sanctum pattern; do not separate the frontend from the backend in a way that drops auth tokens.
- Keep company-finance and lending calculations deterministic and auditable; this project is not a generic CRUD app, it includes lifecycle-driven accounting flows.
