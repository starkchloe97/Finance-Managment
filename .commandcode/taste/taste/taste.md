# Taste
- Wants the agent to survey the complete frontend/styling setup of the project before making UI changes, rather than jumping straight into edits. Confidence: 0.6
- For full-stack schema changes, prefers a backend/domain-first implementation sequence, followed by API/tests and then frontend wiring, rather than treating the work as a UI-only patch. Confidence: 0.95
- Prefers additive database migrations for schema evolution: preserve historical migrations, use new migrations for additions/removals, and explicitly document and implement legacy-data backfill before dropping columns. Confidence: 0.95
- Values repository-wide contract audits and explicit verification of downstream call sites when renaming or removing fields, with environment limitations reported separately from application failures. Confidence: 0.9
- Backend is the source of truth for business/financial calculations (e.g., job profit, cost, margin); the frontend renders backend-provided values only and never recomputes them client-side, and should not invent frontend-only statuses/enums that duplicate backend ones. Confidence: 0.85
- Keeps backend/API changes minimal in presentation/UX phases: preserve existing services, stores, and endpoints, and only add an endpoint/field where there is a genuine UX gap (don't build speculative endpoints). Confidence: 0.8
- Prefers one consolidated API endpoint over multiple round trips (e.g., a single `/dashboard` aggregate returning kpis, financial overview, job status, recent jobs, alerts) rather than many per-widget endpoints. Confidence: 0.8
