# Frontend Styling Guide

How styling works in the Transport ERP SPA (`frontend/`). The project uses **plain global CSS** — no Tailwind, no CSS modules, no preprocessor. One stylesheet drives the whole app, and Vue components reference shared class names from it.

## Where styles live

| Path | Purpose |
| --- | --- |
| `src/style.css` | The **only** stylesheet. Imported once in `src/main.js` (`import "./style.css"`) and available app-wide. |
| `src/components/layout/*.vue` | Shell components (Sidebar, Navbar). Use global classes from `style.css`; no scoped styles. |
| `src/pages/**/*.vue` | Page components. Also use global classes; only a couple pages add a local `<style scoped>` for page-specific tweaks. |

## Design tokens (the source of truth)

`style.css` opens with a `:root` block of CSS custom properties. **Never hardcode colors, sizes, or radii in components** — reference the tokens instead.

Common ones:

```css
:root {
    --accent: #2563eb;            /* primary blue — buttons, links, active states */
    --background: #f5f7fb;        /* page background */
    --surface: #fff;              /* cards, tables, header */
    --sidebar: #141b24;           /* dark sidebar background */
    --sidebar-text: #cbd5e1;      /* sidebar link color */
    --sidebar-hover: #253242;     /* sidebar link hover */
    --sidebar-active: #2563eb;    /* sidebar active link */
    --text-primary: #111827;
    --text-secondary: #4b5563;
    --text-muted: #6b7280;
    --border: #e5e7eb;
    --radius-md: 10px;
    --radius-lg: 14px;
    --space-1: 4px; ... --space-12: 48px;   /* spacing scale */
    --text-sm: 14px; --text-base: 16px;     /* type scale */
    --sidebar-width: 250px;
    --header-height: 64px;
    --control-height: 40px;        /* button/input/nav-row height */
    --transition-fast: 150ms ease;
    --transition-base: 200ms ease;
}
```

**Rules of thumb:**

- Use `var(--space-*)` for all spacing instead of raw `px`.
- Use `var(--text-*)` for font sizes, `var(--radius-*)` for corners.
- Semantic status colors have soft background pairs: `--success` / `--success-soft`, `--danger` / `--danger-soft`, `--warning` / `--warning-soft`, `--info` / `--info-soft`. Use them for badges and status pills.

## Reusable component classes

`style.css` defines app-wide building blocks that pages compose. Common ones:

- **Layout:** `.app`, `.content`, `.page-head`, `.head-title`, `.toolbar`, `.section-head`
- **Surfaces:** `.card` (white card with border + shadow)
- **Buttons:** `.btn`, `.btn-light`, `.btn-danger`, `.btn-sm`
- **Forms:** `.grid` (responsive field grid), `.field`, `.actions`, `.error`, `.notice`
- **Tables:** `.table-wrap` (scroll wrapper), plus `th`/`td` styling, `.money`, `.group-head`, `.grand-total`
- **Statuses:** `.badge`, `.status`, `.status-success`, `.status-warning`, `.status-danger`, `.status-info`
- **Dashboard:** `.stats`, `.stat`, `.kpi-grid`, `.kpi-card`, `.flow`, `.chain`
- **Misc:** `.timeline`, `.totals`, `.muted`, `.hint`, `.empty`, `.login`

To add a shared style, extend `style.css` and keep the class names generic (e.g. `.stat`, not `.dashboard-total-revenue`). Prefer composing existing classes over new page-specific CSS.

## How the sidebar works

The sidebar (`src/components/layout/Sidebar.vue`) is the main custom piece and demonstrates the conventions:

- **Navigation config:** an array of groups (`Overview`, `Operations`, `Finance`), each with links (`label`, `to`, and an `icon` — inline SVG markup for a lucide-style 24x24 stroke icon).
- **Icons:** inline `<svg>` with `stroke="currentColor"` so each icon inherits the link's text color. Sized via `.nav-item-icon` (20px), centered with flex.
- **Alignment:** every `.nav-item` row is a fixed-height flex row (`height: var(--control-height)` = 40px) with a consistent `gap` between icon and label, so rows line up perfectly.
- **Active state:** `.nav-item.router-link-exact-active` gets the blue `--sidebar-active` background. The brand link uses its own `.brand` class and is **never** highlighted — the active rules target `.nav-item` only.
- **Collapse:** toggling adds `.sidebar-collapsed` (64px rail) — labels and group headings hide, icons center. On mobile (<820px) the sidebar becomes a slide-in drawer (`transform` + `.sidebar-open`) with a backdrop, opened from the Navbar's ☰ button.
- **User row:** avatar initial + name, with a compact logout icon button at the bottom, separated by a top border.

Key selector notes: `letter-spacing: 0.08em` on `.nav-group-label` (not `var(--space-1)`), and the fixed `.control-height` for row heights keeps everything on the same baseline.

## Responsive behavior

Breakpoints in `style.css`:

- **≤1024px:** sidebar narrows (`--sidebar-width: 220px`), main padding reduces.
- **≤820px:** sidebar becomes a fixed drawer off-canvas; Navbar shows the ☰ `.mobile-menu` button.
- **≤560px:** single-column layouts, smaller heading, stacked page heads.

When you add a component, test at these widths — the app is mobile-aware.

## Workflow

- Run `npm run dev` for local development, `npm run build` to validate the production build.
- `npm run format` runs Prettier over `src/` (no semicolons, single quotes).
- No Tailwind/PostCSS: if you think you need a utility, add a token or a class to `style.css` first.
