# Mechlibrary — Redesign Spec
## Cyberdeck / 90s Sci-Fi Dark Theme

---

## Visual Identity

### Concept
90s Cyberdeck / Terminal aesthetic. Think BattleTech lore meets dark terminal UI.
- Angular geometry, no rounded corners → beveled clip-paths instead
- Monospace data, Orbitron headings
- Subtle scanline overlay
- Glow on interactive elements
- Corner accent brackets on panels

### Color Tokens

```css
:root {
  --bg:             oklch(0.10 0.025 240);   /* deepest background */
  --bg2:            oklch(0.13 0.022 240);   /* panel background */
  --bg3:            oklch(0.16 0.02 235);    /* elevated surface, header rows */
  --border:         oklch(0.28 0.06 200);    /* default border */
  --border-bright:  oklch(0.45 0.12 195);    /* active/header border */
  --cyan:           oklch(0.72 0.15 195);    /* primary accent */
  --cyan-dim:       oklch(0.50 0.10 195);    /* muted accent */
  --cyan-glow:      oklch(0.72 0.15 195 / 0.3); /* glow shadow */
  --amber:          oklch(0.75 0.14 65);     /* PV, warnings */
  --amber-dim:      oklch(0.55 0.10 65);     /* muted amber */
  --red:            oklch(0.58 0.18 25);     /* invalid, errors */
  --green:          oklch(0.68 0.14 155);    /* valid, add action */
  --text:           oklch(0.88 0.02 220);    /* primary text */
  --text-dim:       oklch(0.55 0.03 220);    /* secondary text */
  --text-faint:     oklch(0.35 0.02 220);    /* disabled / metadata */
}
```

### Typography

```css
/* Headings, labels, buttons, logo */
font-family: 'Orbitron', monospace;

/* All data, inputs, table cells, body text */
font-family: 'JetBrains Mono', monospace;

/* Google Fonts import */
@import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=JetBrains+Mono:wght@300;400;500;600&display=swap');
```

| Use | Font | Size | Weight | Letter-spacing |
|-----|------|------|--------|----------------|
| Logo | Orbitron | 14px | 900 | 0.12em |
| Section labels | Orbitron | 8–9px | 700 | 0.15em |
| Column headers | Orbitron | 8px | 700 | 0.10em |
| Buttons | Orbitron | 10px | 700 | 0.10em |
| Body / table | JetBrains Mono | 11–13px | 400 | — |
| Data values | JetBrains Mono | 11–12px | 500–600 | — |

---

## Layout

```
┌─────────────────────────────────────────────────────┬───────────────┐
│  TOPBAR (42px)                                      │               │
├─────────────────────────────────────────────────────┤               │
│                                                     │  FORMATION    │
│  FILTER MATRIX  (collapsible panel)                 │  PANEL        │
│                                                     │  (240px)      │
├─────────────────────────────────────────────────────┤               │
│  UNIT TABLE  (flex 1, scrollable rows)              │               │
├─────────────────────────────────────────────────────┤               │
│  STATUS BAR (26px)                                  │               │
└─────────────────────────────────────────────────────┴───────────────┘
```

**Detail view** replaces the left panel (filter + table) with a unit detail card when a unit name is clicked.

---

## Geometry / Shape Language

### Beveled Corners (clip-path)
Instead of `border-radius`, use `clip-path` for angular cuts:

```css
/* Small bevel — inputs, small buttons */
.clip-sm { clip-path: polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px); }

/* Medium bevel — panels, cards */
.clip-md { clip-path: polygon(8px 0, 100% 0, 100% 100%, 0 100%, 0 8px); }

/* Full octagon bevel — featured panels */
.clip-oct { clip-path: polygon(8px 0, calc(100% - 8px) 0, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0 calc(100% - 8px), 0 8px); }

/* Button bevel */
.clip-btn { clip-path: polygon(5px 0, 100% 0, 100% calc(100% - 5px), calc(100% - 5px) 100%, 0 100%, 0 5px); }
```

### Corner Accent Brackets
Panels get small L-shaped corner accents in `--cyan`:
```css
/* Absolutely positioned pseudo-element or div in corner of panel */
/* top-left corner: 10×10px with 2px cyan border-top + border-left */
```

### Scanline Overlay
```css
body::before {
  content: '';
  position: fixed; inset: 0; z-index: 9999; pointer-events: none;
  background: repeating-linear-gradient(
    0deg,
    transparent, transparent 2px,
    oklch(0 0 0 / 0.03) 2px, oklch(0 0 0 / 0.03) 4px
  );
}
```

---

## Components

### Topbar
- Height: 42px, background `--bg3`, border-bottom `--border-bright`
- Logo: hexagon SVG icon + "MECHLIBRARY" in Orbitron 900
- Subtitle: "ALPHA STRIKE FORCE BUILDER" in Orbitron 8px `--text-faint`
- Right: "● SYS ONLINE" in `--green` + unit count
- Bottom-left corner accent in `--cyan`

### Filter Matrix Panel
- Collapsible (toggle via header click)
- Header: "▶ FILTER MATRIX" label + collapse chevron
- Sections laid out in CSS Grid rows
- All field labels: Orbitron 8–9px uppercase `--cyan-dim`
- Inputs: dark background `oklch(0.08 0.02 240)`, `--border`, cyan glow on focus
- Selects: same styling, custom appearance
- Apply / Reset buttons at bottom row
- Mode: AND/OR radio toggle

### Inputs & Selects
```css
input, select {
  background: oklch(0.08 0.02 240);
  border: 1px solid var(--border);
  color: var(--text);
  font-family: 'JetBrains Mono', monospace;
  font-size: 12px;
  padding: 4px 8px;
  clip-path: polygon(4px 0, 100% 0, 100% 100%, 0 100%, 0 4px);
  outline: none;
}
input:focus {
  border-color: var(--cyan);
  box-shadow: 0 0 8px var(--cyan-glow);
}
```

### Buttons

| Variant | Background | Border | Text |
|---------|-----------|--------|------|
| Primary | transparent → `--cyan` on hover | `--cyan` | `--cyan` → `--bg` on hover |
| Secondary | transparent → `--bg3` on hover | `--border` | `--text-dim` |
| Add (+) | `oklch(0.20 0.04 155)` → `--green` hover | `--green` | `--green` → `--bg` hover |
| Danger | transparent → `--red` hover | `--red` | `--red` → white hover |

All buttons: Orbitron 10px, 700, `clip-btn` clip-path, `box-shadow` glow on hover.

### Unit Table

**Column order:** [+] | Name | Class | T | PV | Move | TMM | S | M | L | ARM | STR | Abilities

- Header row: `--bg3`, Orbitron 8px `--cyan-dim`, border-bottom `--border-bright`
- Rows: alternating `--bg` / `--bg2`, height 27px (22px compact), border-bottom `--border`
- Hover row: `oklch(0.16 0.025 230)` background
- Name cell: `--text` → `--cyan` on hover, clickable → detail view
- PV cell: `--amber`, fontWeight 600
- Damage S/M/L: `--text`, fontWeight 500
- Abilities: `--text-faint`, 10px, truncated
- Sort indicator: ▲/▼ in `--cyan` on active column
- [+] button: 18×18px, `clip-all-sm`, `--green` border/text, fills green on row hover

### Formation Panel
- Width: 240px, right side, `--bg2`, border-left `--border`
- Collapsible (▶ toggles to vertical "FORMATION" label)
- **Formation selector**: dropdown + [+] new button
- **Filter formations** input
- **Formation card**:
  - Name (Orbitron 11px bold) + edit icon ✎
  - Formation type + INVALID/VALID badge
  - PV progress bar: thin 4px bar, green if under limit, red if over
  - Damage summary: S/M/L values
- **Validation errors**: `--red` ◆ bullet + red text, 10px
- **Unit list**: name truncated + PV in `--amber`, clickable rows
- **Action buttons**: "Show Matching Units", "Print" — full-width secondary style
- **Armies** section: chevron link at bottom

### Badges

| Variant | Background | Border | Text |
|---------|-----------|--------|------|
| INVALID | `oklch(0.15 0.04 25)` | `--red` | `--red` |
| VALID | `oklch(0.12 0.04 155)` | `--green` | `--green` |
| Clan | `oklch(0.12 0.04 195)` | `--cyan` | `--cyan` |
| IS | `oklch(0.14 0.04 65)` | `--amber` | `--amber` |

All badges: Orbitron 8px, 700, `clip-all-sm`, letter-spacing 0.12em.

### Unit Detail View
- Back arrow button + "Add to Force" button
- Mech image area: 180×180px panel with corner accents, striped placeholder if no image
- Unit name: Orbitron 20px 900
- Info grid: 2-column, labels Orbitron 8px `--cyan-dim`, values JetBrains Mono 12px
- **Alpha Strike block**: Panel with corner accents, 4-column stats grid, damage table (S/M/L)
- **Abilities block**: raw string + breakdown table (Tag | S | M | L | Value | Raw)

### Status Bar
- Height: 26px, `--bg3`, border-top `--border`
- "UNITS: {n}" with Orbitron labels
- Helper hint text in `--text-faint`

---

## Interaction States

| State | Visual |
|-------|--------|
| Input focus | `--cyan` border + glow shadow |
| Row hover | `oklch(0.16 0.025 230)` bg |
| Button hover | fill color + glow box-shadow |
| Sort active | `--cyan` header text + ▲/▼ |
| Collapsed panel | Shows only toggle control |

---

## Scrollbars

```css
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--bg); }
::-webkit-scrollbar-thumb { background: var(--border); }
::-webkit-scrollbar-thumb:hover { background: var(--cyan-dim); }
```

---

## Section / Heading Pattern

Section headings inside panels use:
```
◆◆◆  SECTION NAME
```
in Orbitron 13px, `--cyan`, letter-spacing 0.10em, with the `◆◆◆` decoration in 9px.

---

## Google Fonts

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=JetBrains+Mono:wght@300;400;500;600&display=swap" rel="stylesheet">
```
