# Design & UI

- Prefers modern, clean, polished UI that looks like contemporary dashboards — flat surfaces, subtle spacing, proper visual hierarchy. Confidence: 0.85
- Prefers dashboard KPI cards and grids to be compact and scannable, with clear label/value/trend hierarchy, restrained accents, and responsive sizing that avoids wasted space or overflow. Confidence: 0.85 — flat surfaces, subtle spacing, proper visual hierarchy. Confidence: 0.85
- Cares about precise alignment in UI components (e.g., nav rows, icons, labels must line up on a consistent grid with uniform row heights and consistent gaps); explicitly flagged misaligned sidebar rows as a problem. Confidence: 0.8
- Prefers real SVG/iconography over cheap placeholders (e.g., letter tiles or text glyphs as icons), using consistent stroke-based icons that inherit text color. Confidence: 0.8
- Prefers small, purpose-built domain components (per-module folders) with pages that orchestrate them — explicitly no giant single-file pages. Confidence: 0.85
- Expects interactive form controls to be accessible, including semantic field grouping, readable validation feedback, and appropriate ARIA state/description wiring. Confidence: 0.85
- Prefers generic, reusable layout patterns (e.g., an entity detail layout with header + stat strip + tabs built once and reused across Customers/Jobs/Investors) over one-off, entity-specific implementations. Confidence: 0.8
- Prefers one shared set of standardized UI components across modules (search input, filter select, pagination, confirm dialog, loading/empty/error panel) rather than per-page reimplementations of the same pattern; wants KPI/chart/table/filter/loading/empty-state patterns "built as a system, not a one-off page." Confidence: 0.85
- Prefers friendly empty states with a message plus a primary action (e.g., "No customers yet — Create your first customer…" + button) over a bare "No data." Confidence: 0.75
- Mobile strategy: key record tables should transform into cards (identifier, customer, status, key figures, View action) on small screens rather than just shrinking; apply selectively where a table would be unreadable. Confidence: 0.7
- Wants confirmation dialogs for destructive/consequential actions (delete, convert-to-job) and explicit success states for workflow transitions (e.g., showing the new job code with a "View Job" action). Confidence: 0.75
- Avoids non-functional UI elements (e.g., buttons that do nothing because no endpoint exists); keeps action buttons honest and drops dead affordances. Confidence: 0.65
code with a "View Job" action). Confidence: 0.75
- Avoids non-functional UI elements (e.g., buttons that do nothing because no endpoint exists); keeps action buttons honest and drops dead affordances. Confidence: 0.65
