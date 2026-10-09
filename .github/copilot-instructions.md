# Copilot instructions for Finance Management

This repo is a Laravel 13 + Vue 3 financial management app for transport operations. The backend exposes a versioned API for customers, estimates, jobs, investments, loans, company capital, and dashboard reporting; the frontend is a Pinia-driven Vue SPA that consumes that API.

## Build, test, and lint

Backend setup and validation:

```bash
cd backend
composer install
php artisan migrate:fresh --seed
php artisan test
php artisan test --filter=TransportJobStatusTest
php artisan test tests/Feature/LoanManagementTest.php
php artisan pint
```

Frontend setup and validation:

```bash
cd frontend
npm install
npm run build
npx prettier --check "src/**/*.{js,vue}"
```

Typical local development flow from the repo root:

```bash
cd backend && composer install && php artisan migrate:fresh --seed
cd ../frontend && npm install
```

Run the app together:

```bash
cd backend && composer run dev
# or separate terminals:
# terminal 1: cd backend && php artisan serve
# terminal 2: cd frontend && npm run dev
```

## High-level architecture

### Backend

- `backend/app/Services/` holds the accounting and workflow logic; keep business rules here instead of in controllers.
- `backend/app/Http/Controllers/Api/V1/` contains the HTTP API surface, usually one feature area per controller set.
- `backend/app/Models/` defines the main domain models and soft-delete behavior.
- `backend/app/Enums/` holds status enums used across the finance lifecycle (`InvestmentStatus`, `JobStatus`, `LoanStatus`, etc.).
- `backend/routes/api_v1.php` and `backend/routes/api/*.php` register the API endpoints. The project is organized by feature domain, not by a single monolithic controller.
- `backend/app/Http/Resources/` serializes API responses, so keep payload shape and enum values consistent with the frontend.

### Frontend

- `frontend/src/stores/` is the main data layer; most feature state and mutations live here (auth, customer, investment, loan, dashboard, etc.).
- `frontend/src/services/` wraps Axios calls for each resource and must match the backend API names.
- `frontend/src/pages/` contains route-level pages; `src/router/index.js` defines protected routes and auth guards.
- `frontend/src/components/` contains shared UI and domain-specific widgets reused across pages.

### Core workflow

- Customers → Estimates → Transport jobs
- Investments → allocations → settlements/distributions
- Loans → repayments / outstanding balance tracking
- Company capital → drafts / transactions / availability checks

## Key repository conventions

- Keep business logic in the service layer; controllers should orchestrate requests and delegate to services/models.
- Financial data is money-aware: amounts are stored as decimals with 2-place precision and computed through domain logic rather than ad hoc arithmetic in views or controllers.
- Preserve the versioned API (`v1`) and Sanctum header pattern: `Authorization: Bearer <token>` on protected routes.
- Follow the model convention: use Eloquent queries and model relationships rather than bypassing soft-delete behavior with raw SQL in normal access paths.
- Status values and label names must stay aligned across backend enums, API resources, and frontend store logic.
- When editing a feature, update the corresponding backend service/model/controller and frontend store/service in sync; do not patch just one side.
- Use the existing Laravel feature tests under `backend/tests/Feature/` and filter to a single test when validating a focused fix.

## Practical edit guidance

- Before changing a financial rule, inspect the matching service and model together; do not patch only the controller response.
- When adding or changing an API endpoint, keep route registration, request validation, controller, resource, and frontend client/store usage consistent.
- For frontend changes, prefer existing stores and shared components over ad hoc local state.
- Keep calculations deterministic and auditable, especially for investments, settlements, loans, and profit allocation flows.

## Local repo-specific notes

- The project uses Laravel 13 with Sanctum, not a separate auth service.
- The repo is domain-heavy and status-driven; changes in enum values, API payload keys, or resource shapes will usually require frontend alignment.
- `php artisan migrate:fresh --seed` is the expected reset path during local setup.
