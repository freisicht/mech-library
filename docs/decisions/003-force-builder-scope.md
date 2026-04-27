# ADR 003 — Force Builder: Scope and Architecture

**Status:** Accepted  
**Date:** 2026-04-27

## Context

Phase 2 is live: 4 034 units, filterable table at `mechlibrary.test/`. The user's
reference app is NullGops (iOS). Its core strengths are formation-type rule enforcement
and live PV tracking. Its main weakness: no help selecting units given a remaining PV
budget and formation constraints. That gap is the primary motivation for this phase.

## Decision

Build a force builder with the following shape:

### Data model

Two-level hierarchy: **Army** → **Formation**. Both are named and persisted in MySQL.

- `armies` — id, name, timestamps
- `formations` — id, army\_id (nullable — a formation can exist standalone), name,
  formation\_type (slug), pv\_budget, timestamps
- `formation_units` — formation\_id, unit\_id, sort\_order

### Formation types

Implement all ~35 Alpha Strike canonical formation types (Lance, Star, Level II, and
named variants such as Direct Fire Lance, Heavy Battle Star, etc.) as a **data-driven
PHP config** (array of rule descriptors, one file per type or a single registry).  
Each type encodes: allowed unit counts, required unit categories, role constraints, and
any special-ability requirements.

### Rule compliance engine

A `FormationRulesEngine` service reads the type config and the current unit list and
returns a structured result: compliant yes/no, plus a list of unmet requirements shown
in the UI. Runs server-side; result returned with every formation-state API response.

### Live summary panel

A persistent panel (visible while browsing the unit list) shows the active formation:
- Unit list with PV per unit
- PV sum and remaining budget (target − sum)
- S/M/L damage column totals
- Formation type label + compliance indicator (green / red + requirement list)

Panel is a Twig partial injected into the base layout; state kept in the DB (no
localStorage dependency).

### "Add to force" entry points

Button on every Tabulator table row **and** on the unit detail page. Both add to the
currently active formation. If no formation is active the user is prompted to create or
select one first.

### Unit suggestion helper

"Show matching units" button in the force panel pre-fills the existing filter form:
- PV max set to remaining budget
- Any unit-category or role constraint from the active formation type applied as
  additional filters

This reuses the Phase 2 filter infrastructure; no new search backend needed.

### Print view

A dedicated Twig template (`/armies/{id}/print` or `/formations/{id}/print`) renders
the formation with print-optimised CSS (no sidebar, no JS). Stats per unit, PV totals,
compliance summary. No PDF generation, no MUL card download.

## Explicit out-of-scope for Phase 3

| Item | Where it lives |
|---|---|
| PDF generation / MUL card PDF download | Backlog |
| BV (Classic BattleTech) support | Backlog |
| Hierarchy beyond Army → Formation | Backlog |
| Sharing forces between users / devices | Backlog — app is single-user |
| MegaMek `.mul` JSON export | Already in Backlog |
| Non-Mech unit types (vehicles, BA, …) | Blocked on Phase ? scraper extension |
| Probabilistic match-up calculator | Backlog |
