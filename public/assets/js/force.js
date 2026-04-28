/* Mech Library — force builder panel */
'use strict';

(function () {

    // ── State ─────────────────────────────────────────────────────────────────

    let state = {
        formation:     null,  // active formation object or null
        allTypes:      [],    // [{slug, name, faction}, …]
        allFormations: [],    // [{id, name, army_id, army_name}, …]
        allArmies:     [],    // [{id, name, formations: []}, …]
    };

    // ── DOM refs ──────────────────────────────────────────────────────────────

    const panel           = document.getElementById('force-panel');
    const toggleBtn       = document.getElementById('fp-toggle');
    const selectEl        = document.getElementById('fp-formation-select');
    const newBtn          = document.getElementById('fp-new-btn');
    const newForm         = document.getElementById('fp-new-form');
    const newNameInput    = document.getElementById('fp-new-name');
    const newArmySelect   = document.getElementById('fp-new-army');
    const newTypeSelect   = document.getElementById('fp-new-type');
    const newBudget       = document.getElementById('fp-new-budget');
    const newSaveBtn      = document.getElementById('fp-new-save');
    const newCancelBtn    = document.getElementById('fp-new-cancel');
    const nameRowEl       = document.getElementById('fp-name-row');
    const titleEl         = document.getElementById('fp-title');
    const renameBtnEl     = document.getElementById('fp-rename-btn');
    const renameFormEl    = document.getElementById('fp-rename-form');
    const renameInputEl   = document.getElementById('fp-rename-input');
    const renameSaveEl    = document.getElementById('fp-rename-save');
    const renameCancelEl  = document.getElementById('fp-rename-cancel');
    const summaryEl       = document.getElementById('fp-summary');
    const typeLabel       = document.getElementById('fp-type-label');
    const complianceBadge = document.getElementById('fp-compliance-badge');
    const pvSum           = document.getElementById('fp-pv-sum');
    const pvBudgetEl      = document.getElementById('fp-pv-budget-display');
    const dmgS            = document.getElementById('fp-dmg-s');
    const dmgM            = document.getElementById('fp-dmg-m');
    const dmgL            = document.getElementById('fp-dmg-l');
    const reqList         = document.getElementById('fp-requirements');
    const unitList        = document.getElementById('fp-unit-list');
    const actionsEl       = document.getElementById('fp-actions');
    const suggestBtn      = document.getElementById('fp-suggest-btn');
    const printLink       = document.getElementById('fp-print-link');
    const armyListEl      = document.getElementById('fp-army-list');
    const newArmyNameEl   = document.getElementById('fp-new-army-name');
    const newArmySaveEl   = document.getElementById('fp-new-army-save');

    if (!panel) return; // panel not in DOM

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    Promise.all([
        apiFetch('/api/formation-types'),
        apiFetch('/api/armies'),
        apiFetch('/api/formations'),
        apiFetch('/api/formations/active'),
    ]).then(([types, armies, formations, activeData]) => {
        state.allTypes     = types;
        state.allArmies    = armies;
        state.allFormations = formations;

        populateTypeDropdown(newTypeSelect, types);
        populateArmyDropdown(newArmySelect, armies);
        populateFormationSelector(formations, activeData.formation);
        renderArmyList(armies);

        if (activeData.formation) {
            state.formation = activeData.formation;
            render();
        }
    }).catch(err => console.error('[force] bootstrap error', err));

    // ── Panel toggle ──────────────────────────────────────────────────────────

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const collapsed = panel.classList.toggle('collapsed');
            toggleBtn.textContent = collapsed ? '▶' : '▶';
            toggleBtn.setAttribute('aria-expanded', String(!collapsed));
            // On desktop the collapsed label (vertical strip) is shown;
            // on mobile CSS hides it, nothing extra needed.
            const collapsedLabel = panel.querySelector('.fp-collapsed-label');
            if (collapsedLabel && window.innerWidth > 700) {
                collapsedLabel.style.display = collapsed ? 'flex' : '';
            }
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
            const armyId = newArmySelect && newArmySelect.value ? parseInt(newArmySelect.value, 10) : null;
            if (!name) { if (newNameInput) newNameInput.focus(); return; }

            apiFetch('/api/formations', {
                method: 'POST',
                body: JSON.stringify({ name, formation_type: type, pv_budget: budget, army_id: armyId }),
            }).then(created => {
                const army     = armyId ? state.allArmies.find(a => Number(a.id) === armyId) : null;
                const armyName = army ? army.name : null;
                state.allFormations.push({
                    id: created.id, name: created.name,
                    army_id: armyId, army_name: armyName,
                });
                populateFormationSelector(state.allFormations, created);
                return apiFetch('/api/formations/' + created.id + '/activate', { method: 'POST' });
            }).then(f => {
                state.formation = f;
                render();
                newForm.hidden = true;
                newBtn.hidden  = false;
                if (newNameInput)  newNameInput.value  = '';
                if (newBudget)     newBudget.value     = '';
                if (newArmySelect) newArmySelect.value = '';
            }).catch(err => console.error('[force] create', err));
        });
    }

    // ── Rename formation ──────────────────────────────────────────────────────

    if (renameBtnEl) {
        renameBtnEl.addEventListener('click', () => {
            if (!state.formation) return;
            if (renameInputEl) renameInputEl.value = state.formation.name;
            if (renameFormEl)  renameFormEl.hidden  = false;
            if (nameRowEl)     nameRowEl.hidden     = true;
            if (renameInputEl) renameInputEl.focus();
        });
    }

    if (renameCancelEl) {
        renameCancelEl.addEventListener('click', () => {
            if (renameFormEl) renameFormEl.hidden = true;
            if (nameRowEl && state.formation) nameRowEl.hidden = false;
        });
    }

    if (renameSaveEl) {
        renameSaveEl.addEventListener('click', saveRename);
    }
    if (renameInputEl) {
        renameInputEl.addEventListener('keydown', e => {
            if (e.key === 'Enter') saveRename();
            if (e.key === 'Escape' && renameCancelEl) renameCancelEl.click();
        });
    }

    function saveRename() {
        if (!state.formation || !renameInputEl) return;
        const name = renameInputEl.value.trim();
        if (!name) { renameInputEl.focus(); return; }

        apiFetch('/api/formations/' + state.formation.id, {
            method: 'PUT',
            body: JSON.stringify({ name }),
        }).then(updated => {
            state.formation = updated;
            const idx = state.allFormations.findIndex(f => Number(f.id) === updated.id);
            if (idx >= 0) state.allFormations[idx].name = updated.name;
            populateFormationSelector(state.allFormations, updated);
            render();
            if (renameFormEl) renameFormEl.hidden = true;
            if (nameRowEl)    nameRowEl.hidden    = false;
        }).catch(err => console.error('[force] rename', err));
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
            if (window.location.pathname !== '/') {
                window.location.href = url.pathname === '/'
                    ? url.toString()
                    : '/?' + url.searchParams.toString();
            } else {
                const pvMaxEl = document.querySelector('input[name="pv_max"]');
                if (pvMaxEl && pv !== null && pv > 0) {
                    pvMaxEl.value = String(pv);
                }
                const filterForm = document.getElementById('filter-form');
                if (filterForm) filterForm.dispatchEvent(new Event('submit'));
            }
        });
    }

    // ── Army management ───────────────────────────────────────────────────────

    if (newArmySaveEl) {
        newArmySaveEl.addEventListener('click', () => {
            const name = newArmyNameEl ? newArmyNameEl.value.trim() : '';
            if (!name) { if (newArmyNameEl) newArmyNameEl.focus(); return; }

            apiFetch('/api/armies', {
                method: 'POST',
                body: JSON.stringify({ name }),
            }).then(created => {
                const army = { id: created.id, name: created.name, formations: [] };
                state.allArmies.push(army);
                renderArmyList(state.allArmies);
                populateArmyDropdown(newArmySelect, state.allArmies);
                if (newArmyNameEl) newArmyNameEl.value = '';
            }).catch(err => console.error('[force] create army', err));
        });
    }

    // ── Render ────────────────────────────────────────────────────────────────

    function render() {
        if (!state.formation) {
            if (nameRowEl)    nameRowEl.hidden    = true;
            if (renameFormEl) renameFormEl.hidden = true;
            if (summaryEl)    summaryEl.hidden    = true;
            if (actionsEl)    actionsEl.hidden    = true;
            if (unitList)     unitList.innerHTML  = '';
            return;
        }

        const f = state.formation;

        if (titleEl)   titleEl.textContent = f.name;
        if (nameRowEl) nameRowEl.hidden    = renameFormEl ? !renameFormEl.hidden : false;
        if (typeLabel) typeLabel.textContent = typeName(f.formation_type);
        if (printLink) printLink.href = '/formations/' + f.id + '/print';

        if (pvSum) pvSum.textContent = String(f.pv_sum ?? 0);
        if (pvBudgetEl) {
            pvBudgetEl.textContent = f.pv_budget
                ? ('/ ' + f.pv_budget + ' (' + (f.pv_remaining >= 0 ? '+' : '') + f.pv_remaining + ')')
                : '';
        }

        const pvBar = document.getElementById('fp-pv-bar');
        if (pvBar && f.pv_budget) {
            const pct = Math.min(100, ((f.pv_sum ?? 0) / f.pv_budget) * 100);
            pvBar.style.width = pct + '%';
            pvBar.classList.toggle('over', (f.pv_remaining < 0));
        }

        if (dmgS) dmgS.textContent = String(f.dmg_s_total ?? 0);
        if (dmgM) dmgM.textContent = String(f.dmg_m_total ?? 0);
        if (dmgL) dmgL.textContent = String(f.dmg_l_total ?? 0);

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

        syncAddButtons(f.units || []);

        if (summaryEl) summaryEl.hidden = false;
        if (actionsEl) actionsEl.hidden = false;
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

    // Make addUnit globally available so the Tabulator formatter and detail page can call it
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

    function populateArmyDropdown(sel, armies) {
        if (!sel) return;
        sel.innerHTML = '<option value="">— no army —</option>';
        (armies || []).forEach(a => {
            const opt = document.createElement('option');
            opt.value       = a.id;
            opt.textContent = a.name;
            sel.appendChild(opt);
        });
    }

    function populateFormationSelector(formations, active) {
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">— no active formation —</option>';

        const armyMap  = new Map();
        const standalone = [];
        (formations || []).forEach(f => {
            const aid = f.army_id ? Number(f.army_id) : null;
            if (aid) {
                if (!armyMap.has(aid)) {
                    armyMap.set(aid, { name: f.army_name || String(aid), items: [] });
                }
                armyMap.get(aid).items.push(f);
            } else {
                standalone.push(f);
            }
        });

        armyMap.forEach(army => {
            const group = document.createElement('optgroup');
            group.label = army.name;
            army.items.forEach(f => {
                const opt = document.createElement('option');
                opt.value       = f.id;
                opt.textContent = f.name;
                group.appendChild(opt);
            });
            selectEl.appendChild(group);
        });

        if (standalone.length > 0) {
            if (armyMap.size > 0) {
                const group = document.createElement('optgroup');
                group.label = '—';
                standalone.forEach(f => appendFormationOption(group, f));
                selectEl.appendChild(group);
            } else {
                standalone.forEach(f => appendFormationOption(selectEl, f));
            }
        }

        const activeId = active
            ? (active.id !== undefined ? active.id : active)
            : (state.formation ? state.formation.id : null);
        if (activeId) selectEl.value = String(activeId);
    }

    function appendFormationOption(parent, f) {
        const opt = document.createElement('option');
        opt.value       = f.id;
        opt.textContent = f.name;
        parent.appendChild(opt);
    }

    function renderArmyList(armies) {
        if (!armyListEl) return;
        armyListEl.innerHTML = '';
        if (!armies || armies.length === 0) {
            const li = document.createElement('li');
            li.className   = 'fp-army-item fp-army-empty';
            li.textContent = 'No armies yet.';
            armyListEl.appendChild(li);
            return;
        }
        armies.forEach(a => {
            const li = document.createElement('li');
            li.className   = 'fp-army-item';
            li.textContent = a.name;
            if (a.formations && a.formations.length > 0) {
                li.title = a.formations.length + ' formation(s)';
            }
            armyListEl.appendChild(li);
        });
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
