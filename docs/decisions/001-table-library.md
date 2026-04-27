# ADR 001 — Table library: Tabulator

**Status:** Accepted  
**Date:** 2026-04-27

## Context

Phase 2 needs a filterable, server-side-paginable data grid. Two candidates were
evaluated: **DataTables** (jQuery-based, v2) and **Tabulator** (jQuery-free, v6).

The dataset is a few thousand rows; the JS component must handle:
- AJAX fetching from `/api/units`
- Server-side pagination (page + size params)
- Column header sorting (triggers re-fetch)
- No build step (plain `<script src>`)

## Decision

**Tabulator 6.x.**

Reasons:
1. No jQuery dependency — keeps the frontend stack lean and free of a 87 KB
   transitive include that serves no other purpose in this app.
2. Native ES-module-friendly API; the UMD bundle also works fine via `<script>`.
3. `ajaxURL` + `ajaxParams` config directly supports our server-side pagination
   contract (`page`, `size`, arbitrary filter params).
4. `headerSort` triggers an AJAX re-fetch without extra code.
5. Column formatters are clean JS functions — easy to add clickable rows,
   badges for ability chips, etc. in Phase 3.

DataTables would work too, but jQuery is a notable extra weight and the DataTables
server-side protocol (`draw`, `recordsTotal`, `recordsFiltered`) is more
boilerplate to implement than Tabulator's simpler contract.

## Consequences

- Tabulator JS + CSS are vendored in `public/assets/` (no CDN dependency for
  offline use).
- The `/api/units` response envelope must match Tabulator's expected shape:
  `{ "last_page": N, "data": [...] }`.
- If the Force Builder (Phase 3) needs row-selection, Tabulator's
  `selectable` option covers it without additional libraries.
