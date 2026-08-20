# Taste

## Workflow
- Wants the agent to survey the complete frontend/styling setup of the project before making UI changes, rather than jumping straight into edits. Confidence: 0.6

## Architecture
- Backend is the source of truth for business/financial calculations (e.g., job profit, cost, margin); the frontend renders backend-provided values only and never recomputes them client-side, and should not invent frontend-only statuses/enums that duplicate backend ones. Confidence: 0.85
- Keeps backend/API changes minimal in presentation/UX phases: preserve existing services, stores, and endpoints, and only add an endpoint/field where there is a genuine UX gap. Confidence: 0.7
