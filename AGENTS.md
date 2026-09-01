# Finance Management - Agent Guidelines

A full-stack financial management and transport operations system built with **Laravel 13 + Vue 3 + Pinia**, combining operations tracking, capital management, lending operations, and real-time financial dashboards.

## Quick Start Commands

**First Time Setup**
```bash
cd backend
composer install
php artisan migrate:fresh --seed
cd ../frontend
npm install
```

**Development (runs both servers concurrently)**
```bash
cd backend && composer run dev
# OR run in separate terminals:
# Terminal 1: cd backend && php artisan serve
# Terminal 2: cd frontend && npm run dev
```

**Testing & Quality**
```bash
cd backend && composer run test          # Run PHPUnit tests
cd backend && php artisan pint           # Fix PHP code style
cd frontend && npm run build              # Production build
```

---

## Architecture Overview

### **Backend: Laravel 13 RESTful API**
- **Location**: `backend/` directory
- **Authentication**: Sanctum token-based (JWT-like, stateless)
- **Pattern**: Service layer + Model-based architecture
- **Database**: Uses migrations in `database/migrations/` with soft deletes for data safety
- **Strong Typing**: Extensive use of Enums in `app/Enums/` (14 enums defining business states)

**Core Modules**:
- **Operations**: Customers → Estimates → Transport Jobs (with expenses, profit allocations)
- **Capital**: Investments → Allocations → Settlements + Investor profit distributions
- **Lending**: Loans → Repayments with balance tracking
- **Reporting**: Dashboard KPIs, financial metrics, alerts

### **Frontend: Vue 3 + Pinia SPA**
- **Location**: `frontend/` directory
- **Routing**: Vue Router with `requiresAuth` meta guards
- **State**: Pinia stores (one per feature: auth, customer, investor, investment, loan, asset, companyCapital, dashboard)
- **Styling**: Tailwind CSS via `@tailwindcss/vite`
- **Building**: Vite with dev/production modes

**Key Pages**:
- Dashboard (KPIs, financial charts, alerts)
- Operations (Customers, Estimates, Jobs, Assets)
- Capital (Investors, Investments, Loans)

---

## Development Conventions

### **Backend (PHP/Laravel)**

**Model & Database Patterns**
- All sensitive models use `SoftDeletes` — deleted records remain in DB but are hidden
- Models cast attributes: decimals → 2 places, dates → Carbon, enums → native PHP 8.1 enums
- Computed attributes for calculations: `Investment->estimated_return`, `Loan->outstanding_balance`
- One model per entity; use Services for complex logic

**Naming**
- Tables: `snake_case` (e.g., `transport_jobs`, `investment_allocations`)
- Models: `StudlyCase` (e.g., `TransportJob`, `InvestmentAllocation`)
- Enums: Located in `app/Enums/`, used for type safety across the app

**API Response Format**
- All endpoints return JSON via Resource classes in `app/Http/Resources/`
- Standard format: `{ "data": {...}, "meta": {...} }` or `{ "data": [...], "meta": {...} }`
- Always use appropriate HTTP status codes (201 for create, 204 for delete, 422 for validation errors)

**Authentication**
- Route protection: Use `auth:sanctum` middleware on protected routes
- Token in request headers: `Authorization: Bearer {token}`
- User context via `Auth::user()` in controllers/services

**Testing Location**: `tests/Feature/` and `tests/Unit/`

### **Frontend (JavaScript/Vue)**

**Component Structure**
- Pages in `src/pages/` (one page per feature/route)
- Reusable components in `src/components/` (UI widgets, feature-specific)
- Composition: Use `<script setup>` syntax with modern Vue 3 patterns
- Style: Scoped `<style scoped>` with Tailwind classes

**State Management (Pinia)**
- Define stores in `src/stores/` with `.js` extension
- Each store handles one domain: `authStore`, `customerStore`, `investmentStore`, etc.
- Actions fetch data via axios; mutations update state
- Always import and use stores via `const myStore = useMyStore()`

**API Communication**
- Use axios-based API client (service layer in `src/services/`)
- Pass auth token automatically via interceptors
- Handle errors: always catch and display toast notifications
- Store data in Pinia, not local component state (for consistency)

**Routing & Navigation**
- Routes defined in `src/router/index.js` with `requiresAuth` meta for protected pages
- Link format: `<router-link :to="{ name: 'route-name', params: { id: value } }">`
- Redirect unauthenticated users to `/login`

**UI Components**
- Consistent patterns: Pagination, SearchInput, FilterSelect, StatePanel (loading/error/empty)
- Use ConfirmDialog for destructive actions
- Toast notifications for user feedback

---

## Key Business Logic Patterns

**Estimates → Jobs Workflow**
1. Create Estimate with EstimateItems
2. EstimateItems may have EstimateItemVehicles (linked to asset/vehicle)
3. Convert Estimate → TransportJob (creates job with related items)
4. Add expenses to jobs, calculate profit (revenue - expenses)

**Investment Lifecycle**
- States: `draft` → `active` → `mature` → `withdrawn`/`settled` or `cancelled`
- Allocate investment to jobs (InvestmentAllocation ties investment to job revenue)
- Settlements calculate final returns based on job profits
- Investor profit distributions split allocations among investors

**Loan Management**
- Create loan with BorrowerType (personal/corporate)
- Track repayments as separate transactions
- Calculate outstanding balance via model attribute
- Support early repayment and cancellation

**Profit Allocation**
- Jobs generate profit (revenue - expenses)
- Profit splits via Investment Allocations (investor gets % share)
- Settlements finalize amounts and trigger distributions
- All amounts stored as decimals to 2 places

---

## Common Pitfalls & Solutions

| Issue | Solution |
|-------|----------|
| **Soft-deleted records returning from queries** | Always use `->get()` not raw SQL; models handle soft deletes automatically |
| **API returning stale data** | Invalidate Pinia store on mutations; call `store.fetchSomething()` after POST/PUT/DELETE |
| **Decimal precision errors in financial data** | Always cast to 2 places in model: `->decimal('amount', 10, 2)` in migrations |
| **Frontend showing 404 on authenticated routes** | Verify token is sent in `Authorization` header; check `auth:sanctum` middleware on routes |
| **Enum values not matching frontend** | Match enum names exactly in API responses; Resource classes transform enums to strings |
| **Circular dependencies in components** | Use Pinia stores as single source of truth; avoid prop drilling |
| **Asset/Vehicle duplicate confusion** | Asset = inventory item (vehicles, equipment); EstimateItemVehicle = specific asset linked to estimate |

---

## File Structure Guide

```
backend/
├── app/
│   ├── Enums/              # 14 business state enums (AssetStatus, JobStatus, etc.)
│   ├── Models/             # 30+ Eloquent models (all with SoftDeletes where appropriate)
│   ├── Services/           # Business logic layer (TransportJobService, InvestmentService, etc.)
│   ├── Http/
│   │   ├── Controllers/    # RESTful controllers (one per entity)
│   │   ├── Requests/       # Form request validation
│   │   └── Resources/      # JSON transformation classes
│   └── Providers/          # Service providers & bootstrapping
├── database/
│   ├── migrations/         # Schema changes (run with php artisan migrate)
│   ├── factories/          # Model factories for seeding
│   └── seeders/            # Database seed data
├── routes/
│   ├── api.php             # Auth routes
│   ├── api_v1.php          # v1 API routes (customers, jobs, investments, etc.)
│   └── api/                # Versioned endpoint files
├── tests/                  # Feature & Unit tests
├── artisan                 # Artisan CLI tool
└── composer.json           # PHP dependencies & scripts

frontend/
├── src/
│   ├── pages/              # Route pages (Customers, Estimates, Jobs, Investments, Loans, Assets)
│   ├── components/         # Reusable Vue components
│   ├── composables/        # Composition utilities (useToast, etc.)
│   ├── stores/             # Pinia stores (auth, customer, investor, investment, loan, asset, etc.)
│   ├── router/             # Vue Router configuration
│   ├── services/           # API client services
│   ├── layouts/            # App layout (sidebar navigation)
│   ├── css/                # Global styles & Tailwind setup
│   └── utils/              # Helpers (money formatting, avatar styles)
├── vite.config.js          # Vite bundler config
└── package.json            # JavaScript dependencies & scripts
```

---

## API Endpoints Reference

**Authentication**
- `POST /auth/login` - Login with email/password
- `GET /auth/me` - Get current user
- `POST /auth/logout` - Logout

**Operations**
- `GET|POST /customers` - List/Create customers
- `GET|PUT|DELETE /customers/{id}` - Detail/Update/Delete
- `GET|POST /estimates` - List/Create estimates
- `POST /estimates/{id}/convert` - Convert estimate to job
- `GET|POST /jobs` - List/Create jobs
- `GET|PUT|DELETE /jobs/{id}` - Detail/Update/Delete
- `GET|POST /assets` - List/Create assets
- `GET|PUT|DELETE /assets/{id}` - Detail/Update/Delete

**Capital & Lending**
- `GET|POST /investors` - List/Create investors
- `GET|PUT|DELETE /investors/{id}` - Detail/Update/Delete
- `GET|POST /investments` - List/Create investments
- `POST /investments/{id}/transition` - Change investment status
- `GET|POST /loans` - List/Create loans
- `POST /loans/{id}/repay` - Record repayment
- `POST /investment-allocations` - Allocate investment to job

**Reporting**
- `GET /reports` - Dashboard metrics (revenue, profit, alerts)

---

## Development Tips for Agents

1. **Before modifying a model**: Check `app/Models/` to understand relationships and attributes
2. **Before adding an API endpoint**: Check `routes/api_v1.php` and `app/Http/Controllers/` for similar patterns
3. **When adding a feature**: Create Service first, then Controller, then Frontend Store, then Pages/Components
4. **For financial calculations**: Always use services, never in controllers; test with PHPUnit
5. **For UI state**: Use Pinia stores, not local Vue state
6. **Database changes**: Create migrations with `php artisan make:migration` and check existing migrations for patterns
7. **Frontend routing**: Check `src/router/index.js` for existing route structure before adding new pages
8. **Styling**: Use Tailwind classes; check existing components for consistent patterns (spacing, colors, shadows)

---

## Links to Key Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Guide](https://vuejs.org/guide/introduction.html)
- [Pinia State Management](https://pinia.vuejs.org/)
- [Tailwind CSS](https://tailwindcss.com/)
