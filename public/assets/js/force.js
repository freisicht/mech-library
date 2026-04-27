/* Mech Library — force builder panel */
'use strict';

(function () {

    // ── State ─────────────────────────────────────────────────────────────────

    let state = {
        formation: null,    // active formation object or null
        allTypes:  [],      // [{slug, name, faction}, …]
        allFormations: [],  // flat list from /api/armies for the selector
    };

    // ── DOM refs ──────────────────────────────────────────────────────────────

    const panel         = document.getElementById('force-panel');
    const toggleBtn     = document.getElementById('fp-toggle');
    const titleEl       = document.getElementById('fp-title');
    const selectEl      = document.getElementById('fp-formation-select');
    const newBtn        = document.getElementById('fp-new-btn');
    const newForm       = document.getElementById('fp-new-form');
    const newNameInput  = document.getElementById('fp-new-name');
    const newTypeSelect = document.getElementById('fp-new-type');
    const newBudget     = document.getElementById('fp-new-budget');
    const newSaveBtn    = document.getElementById('fp-new-save');
    const newCancelBtn  = document.getElementById('fp-new-cancel');
    const summaryEl     = document.getElementById('fp-summary');
    const typeLabel     = document.getElementById('fp-type-label');
    const complianceBadge = document.getElementById('fp-compliance-badge');
    const pvSum         = document.getElementById('fp-pv-sum');
    const pvBudgetEl    = document.getElementById('fp-pv-budget-display');
    const dmgS          = document.getElementById('fp-dmg-s');
    const dmgM          = document.getElementById('fp-dmg-m');
    const dmgL          = document.getElementById('fp-dmg-l');
    const reqList       = document.getElementById('fp-requirements');
    const unitList      = document.getElementById('fp-unit-list');
    const actionsEl     = document.getElementById('fp-actions');
    const suggestBtn    = document.getElementById('fp-suggest-btn');
    const printLink     = document.getElementById('fp-print-link');

    if (!panel) return; // panel not in DOM

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    Promise.all([
        apiFetch('/api/formation-types'),
        apiFetch('/api/armies'),
        apiFetch('/api/formations/active'),
    ]).then(([types, armies, activeData]) => {
        state.allTypes = types;
        populateTypeDropdown(newTypeSelect, types);

        state.allFormations = flattenFormations(armies);
        populateFormationSelector(state.allFormations, activeData.formation);

        if (activeData.formation) {
            state.formation = activeData.formation;
            render();
        }
    }).catch(err => console.error('[force] bootstrap error', err));

    // ── Panel toggle ──────────────────────────────────────────────────────────

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const collapsed = panel.classList.toggle('collapsed');
            toggleBtn.textContent = collapsed ? '▶' : '◀';
            toggleBtn.setAttribute('aria-expanded', String(!collapsed));
        });
    }

    // ── Formation selector ────────────────────────────────────────────────────

    if (selectEl) {
        selectEl.addEventListener('change', () => {
            const id = parseInt(selectEl.value, 10);
            if (!id) {
                apiFetch('/api/formations/active', { method: 'DELETE' }).then(() => {
                    state.formation = null;
                    render();
                });
                return;
            }
            apiFetch('/api/formations/' + id + '/activate', { method: 'POST' })
                .then(f => { state.formation = f; render(); })
                .catch(err => console.error('[force] activate', err));
        });
    }

    // ── New-formation form ────────────────────────────────────────────────────

    if (newBtn) {
        newBtn.addEventListener('click', () => {
            newForm.hidden = false;
            newBtn.hidden  = true;
            if (newNameInput) newNameInput.focus();
        });
    }

    if (newCancelBtn) {
        newCancelBtn.addEventListener('click', () => {
            newForm.hidden = true;
            newBtn.hidden  = false;
        });
    }

    if (newSaveBtn) {
        newSaveBtn.addEventListener('click', () => {
            const name   = newNameInput ? newNameInput.value.trim() : '';
            const type   = newTypeSelect ? newTypeSelect.value : 'custom';
            const budget = newBudget && newBudget.value !== '' ? parseInt(newBudget.value, 10) : null;
            if (!name) { if (newNameInput) newNameInput.focus(); return; }

            apiFetch('/api/formations', {
                method: 'POST',
                body: JSON.stringify({ name, formation_type: type, pv_budget: budget }),
            }).then(created => {
                // Add to selector and activate
                state.allFormations.push({ id: created.id, name: created.name });
                rebuildSelectorOptions(state.allFormations, created.id);
                return apiFetch('/api/formations/' + created.id + '/activate', { method: 'POST' });
            }).then(f => {
                state.formation = f;
                render();
                newForm.hidden = true;
                newBtn.hidden  = false;
                if (newNameInput) newNameInput.value = '';
                if (newBudget)    newBudget.value    = '';
            }).catch(err => console.error('[force] create', err));
        });
    }

    // ── Suggestion helper ─────────────────────────────────────────────────────

    if (suggestBtn) {
        suggestBtn.addEventListener('click', () => {
            if (!state.formation) return;
            const pv  = state.formation.pv_remaining;
            const url = new URL(window.location.href);

            if (pv !== null && pv > 0) {
                url.searchParams.set('pv_max', String(pv));
            }
            // Navigate to root list page with the filter pre-applied
            if (window.location.pathname !== '/') {
                window.location.href = url.pathname === '/'
                    ? url.toString()
                    : '/?' + url.searchParams.toString();
            } else {
                // Already on list page — update filters and reload table
                const pvMaxEl = document.querySelector('input[name="pv_max"]');
                if (pvMaxEl && pv !== null && pv > 0) {
                    pvMaxEl.value = String(pv);
                }
                const filterForm = document.getElementById('filter-form');
                if (filterForm) filterForm.dispatchEvent(new Event('submit'));
            }
        });
    }

    // ── Render ────────────────────────────────────────────────────────────────

    function render() {
        if (!state.formation) {
            if (summaryEl)  summaryEl.hidden  = true;
            if (actionsEl)  actionsEl.hidden  = true;
            if (unitList)   unitList.innerHTML = '';
            if (titleEl)    titleEl.textContent = 'Force Builder';
            return;
        }

        const f = state.formation;

        if (titleEl)    titleEl.textContent = f.name;
        if (typeLabel)  typeLabel.textContent = typeName(f.formation_type);
        if (printLink)  printLink.href = '/formations/' + f.id + '/print';

        // PV
        if (pvSum) pvSum.textContent = String(f.pv_sum ?? 0);
        if (pvBudgetEl) {
            pvBudgetEl.textContent = f.pv_budget
                ? ('/ ' + f.pv_budget + ' (' + (f.pv_remaining >= 0 ? '+' : '') + f.pv_remaining + ')')
                : '';
        }

        // Damage totals
        if (dmgS) dmgS.textContent = String(f.dmg_s_total ?? 0);
        if (dmgM) dmgM.textContent = String(f.dmg_m_total ?? 0);
        if (dmgL) dmgL.textContent = String(f.dmg_l_total ?? 0);

        // Compliance
        const comp = f.compliance || { compliant: true, unmet_requirements: [] };
        if (complianceBadge) {
            complianceBadge.textContent = comp.compliant ? 'OK' : 'INVALID';
            complianceBadge.className   = 'fp-compliance-badge ' + (comp.compliant ? 'ok' : 'fail');
        }
        if (reqList) {
            reqList.innerHTML = '';
            if (comp.unmet_requirements && comp.unmet_requirements.length > 0) {
                comp.unmet_requirements.forEach(req => {
                    const li = document.createElement('li');
                    li.textContent = req;
                    reqList.appendChild(li);
                });
                reqList.hidden = false;
            } else {
                reqList.hidden = true;
            }
        }

        // Unit list
        if (unitList) {
            unitList.innerHTML = '';
            (f.units || []).forEach(u => {
                const li = document.createElement('li');
                li.className = 'fp-unit-item';
                li.dataset.unitId = String(u.id);

                const nameSpan = document.createElement('span');
                nameSpan.className   = 'fp-unit-name';
                nameSpan.textContent = u.name + (u.variant ? ' ' + u.variant : '');
                nameSpan.title       = nameSpan.textContent;

                const pvSpan = document.createElement('span');
                pvSpan.className   = 'fp-unit-pv';
                pvSpan.textContent = 'PV ' + (u.pv ?? '?');

                const rmBtn = document.createElement('button');
                rmBtn.className   = 'fp-unit-remove';
                rmBtn.textContent = '✕';
                rmBtn.title       = 'Remove from formation';
                rmBtn.addEventListener('click', e => {
                    e.stopPropagation();
                    removeUnit(f.id, u.id);
                });

                li.appendChild(nameSpan);
                li.appendChild(pvSpan);
                li.appendChild(rmBtn);
                unitList.appendChild(li);
            });
        }

        // Refresh add-to-force button states in the Tabulator table
        syncAddButtons(f.units || []);

        if (summaryEl)  summaryEl.hidden  = false;
        if (actionsEl)  actionsEl.hidden  = false;
    }

    // ── Add / remove unit ─────────────────────────────────────────────────────

    function addUnit(unitId, btn) {
        if (!state.formation) {
            alert('No active formation. Create or select one first.');
            return;
        }
        if (btn) btn.disabled = true;

        apiFetch('/api/formations/' + state.formation.id + '/units', {
            method: 'POST',
            body: JSON.stringify({ unit_id: unitId }),
        }).then(updated => {
            state.formation = updated;
            render();
            if (btn) {
                btn.classList.add('added');
                btn.disabled = false;
            }
        }).catch(err => {
            console.error('[force] addUnit', err);
            if (btn) btn.disabled = false;
        });
    }

    function removeUnit(formationId, unitId) {
        apiFetch('/api/formations/' + formationId + '/units/' + unitId, { method: 'DELETE' })
            .then(updated => { state.formation = updated; render(); })
            .catch(err => console.error('[force] removeUnit', err));
    }

    // Make addUnit available globally so the Tabulator formatter and detail page can call it
    window.ML = window.ML || {};
    window.ML.addToForce = addUnit;

    // ── Keep add-buttons in sync with current formation unit list ─────────────

    function syncAddButtons(units) {
        const inForce = new Set(units.map(u => String(u.id)));
        document.querySelectorAll('.add-to-force-btn').forEach(btn => {
            const uid = btn.dataset.unitId;
            if (uid) {
                btn.classList.toggle('added', inForce.has(uid));
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    function typeName(slug) {
        const t = state.allTypes.find(x => x.slug === slug);
        return t ? t.name : slug;
    }

    function populateTypeDropdown(sel, types) {
        if (!sel) return;
        sel.innerHTML = '';
        types.forEach(t => {
            const opt = document.createElement('option');
            opt.value       = t.slug;
            opt.textContent = t.name;
            sel.appendChild(opt);
        });
    }

    /** Flatten armies + standalone formations into a single list for the selector. */
    function flattenFormations(armies) {
        const list = [];
        (armies || []).forEach(army => {
            (army.formations || []).forEach(f => {
                list.push({ id: f.id, name: army.name + ' / ' + f.name });
            });
        });
        return list;
    }

    function populateFormationSelector(formations, active) {
        if (!selectEl) return;
        rebuildSelectorOptions(formations, active ? active.id : null);
    }

    function rebuildSelectorOptions(formations, activeId) {
        if (!selectEl) return;
        const prev = selectEl.value;
        selectEl.innerHTML = '<option value="">— no active formation —</option>';
        formations.forEach(f => {
            const opt = document.createElement('option');
            opt.value       = f.id;
            opt.textContent = f.name;
            selectEl.appendChild(opt);
        });
        if (activeId) {
            selectEl.value = String(activeId);
        } else {
            selectEl.value = prev || '';
        }
    }

    function apiFetch(url, opts) {
        const defaults = {
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        };
        return fetch(url, Object.assign({}, defaults, opts)).then(r => {
            if (!r.ok) throw new Error(url + ' → ' + r.status);
            if (r.status === 204) return null;
            return r.json();
        });
    }

})();
