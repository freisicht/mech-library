/* Mech Library — list-page script */
'use strict';

(function () {

    // ── Helpers ──────────────────────────────────────────────────────────────

    function qs(sel) { return document.querySelector(sel); }

    function collectFormParams() {
        const form = qs('#filter-form');
        if (!form) return {};
        const data = new FormData(form);
        const out = {};
        for (const [k, v] of data.entries()) {
            if (v === '') continue;
            if (k.endsWith('[]')) {
                const key = k.slice(0, -2);
                if (!out[key]) out[key] = [];
                out[key].push(v);
            } else {
                out[k] = v;
            }
        }
        return out;
    }

    function buildQueryString(params) {
        const parts = [];
        for (const [k, v] of Object.entries(params)) {
            if (Array.isArray(v)) {
                v.forEach(function (item) {
                    parts.push(encodeURIComponent(k + '[]') + '=' + encodeURIComponent(item));
                });
            } else {
                parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
            }
        }
        return parts.join('&');
    }

    // ── Tabulator ─────────────────────────────────────────────────────────────

    const apiUrl = (window.ML && window.ML.apiUrl) ? window.ML.apiUrl : '/api/units';

    const isMobile = window.matchMedia('(max-width: 700px)').matches;

    // ── Column visibility persistence ────────────────────────────────────────
    const COL_VIS_KEY = 'ml.unitTable.colVisibility';
    function loadColVis() {
        try {
            const raw = localStorage.getItem(COL_VIS_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) { return {}; }
    }
    function saveColVis(map) {
        try { localStorage.setItem(COL_VIS_KEY, JSON.stringify(map)); } catch (e) {}
    }
    const colVis = loadColVis();

    function buildColMenu() {
        const menu = [];
        table.getColumns().forEach(function (col) {
            const def = col.getDefinition();
            if (!def.field || def.field === 'id') return;
            const visible = col.isVisible();
            const label = document.createElement('span');
            label.style.display = 'inline-flex';
            label.style.alignItems = 'center';
            label.style.gap = '6px';
            const mark = document.createElement('span');
            mark.textContent = visible ? '☑' : '☐';
            mark.style.width = '14px';
            const text = document.createElement('span');
            text.textContent = def.title || def.field;
            label.appendChild(mark);
            label.appendChild(text);
            menu.push({
                label: label,
                action: function (e) {
                    e.stopPropagation();
                    col.toggle();
                    colVis[def.field] = col.isVisible();
                    saveColVis(colVis);
                },
            });
        });
        return menu;
    }

    const table = new Tabulator('#unit-table', {
        layout: 'fitColumns',
        height: isMobile ? '70vh' : '100%',
        progressiveLoad: 'scroll',
        progressiveLoadScrollMargin: 200,
        paginationSize: 50,
        sortMode: 'remote',
        ajaxURL: apiUrl,
        ajaxConfig: 'GET',
        ajaxParams: function () {
            const fp = collectFormParams();
            delete fp.page;
            delete fp.size;
            delete fp.sort;
            delete fp.dir;
            return fp;
        },
        ajaxResponse: function (_url, _params, response) {
            const el = document.getElementById('result-count');
            if (el) {
                const n = (response && typeof response.total === 'number') ? response.total : 0;
                el.textContent = n.toLocaleString() + ' unit' + (n === 1 ? '' : 's');
            }
            return response;
        },
        headerSort: true,
        initialSort: [{ column: 'name', dir: 'asc' }],
        columns: [
            {
                title: '<span class="col-menu-icon" title="Spalten anzeigen/verbergen">⋮</span>',
                titleFormatter: 'html',
                field: 'id', sorter: false, width: 46, hozAlign: 'center',
                headerSort: false, resizable: false,
                headerMenu: buildColMenu,
                formatter: function (cell) {
                    const id  = cell.getValue();
                    const btn = document.createElement('button');
                    btn.className        = 'add-to-force-btn';
                    btn.dataset.unitId   = String(id);
                    btn.textContent      = '+';
                    btn.title            = 'Add to active formation';
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        if (window.ML && window.ML.addToForce) {
                            window.ML.addToForce(id, btn);
                        }
                    });
                    return btn;
                },
            },
            { title: 'Name',      field: 'name',      sorter: 'string',  minWidth: 180,
                formatter: function (cell) {
                    const row = cell.getRow().getData();
                    return '<a href="/units/' + row.id + '">' + (cell.getValue() || '') + '</a>';
                }
            },
            { title: 'T',         field: 'tonnage',   sorter: 'number',  width: 55, visible: false, hozAlign: 'right' },
            { title: 'PV',        field: 'pv',        sorter: 'number',  width: 55, hozAlign: 'right' },
            { title: 'Move',      field: 'move',      sorter: 'string',  width: 75, hozAlign: 'center' },
            { title: 'TMM',       field: 'tmm',       sorter: 'number',  width: 60, visible: false, hozAlign: 'right' },
            { title: 'S',         field: 'dmg_s',     sorter: 'number',  width: 45, hozAlign: 'center' },
            { title: 'M',         field: 'dmg_m',     sorter: 'number',  width: 45, hozAlign: 'center' },
            { title: 'L',         field: 'dmg_l',     sorter: 'number',  width: 45, hozAlign: 'center' },
            { title: 'Armor',     field: 'armor',     sorter: 'number',  width: 62, hozAlign: 'right' },
            { title: 'Str',       field: 'structure', sorter: 'number',  width: 55, visible: false, hozAlign: 'right' },
            { title: 'Abilities', field: 'abilities', sorter: false,     minWidth: 180, formatter: 'plaintext' },
        ],
        rowFormatter: function (row) {
            row.getElement().addEventListener('click', function (e) {
                if (e.target.tagName === 'A') return;
                window.location.href = '/units/' + row.getData().id;
            });
        },
    });

    // Pass Tabulator's pagination/sort params to our API
    table.on('dataSorting', function (sorters) {
        // Tabulator handles this internally via ajaxURL rebuild
        void sorters;
    });

    function highlightSortedColumn() {
        const sorters = table.getSorters ? table.getSorters() : [];
        const field = (sorters && sorters.length) ? sorters[0].field : null;
        const root = document.getElementById('unit-table');
        if (!root) return;
        root.querySelectorAll('.ml-sorted-col').forEach(function (el) {
            el.classList.remove('ml-sorted-col');
        });
        if (!field) return;
        root.querySelectorAll('[tabulator-field="' + field + '"]').forEach(function (el) {
            el.classList.add('ml-sorted-col');
        });
    }
    table.on('dataSorted', highlightSortedColumn);
    table.on('renderComplete', highlightSortedColumn);
    table.on('tableBuilt', highlightSortedColumn);
    table.on('tableBuilt', function () {
        Object.entries(colVis).forEach(function (entry) {
            const field = entry[0], visible = entry[1];
            const col = table.getColumn(field);
            if (!col) return;
            if (visible && !col.isVisible()) col.show();
            if (!visible && col.isVisible()) col.hide();
        });
    });

    // ── Filter form ───────────────────────────────────────────────────────────

    const filterForm = qs('#filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const params = collectFormParams();
            const qs2 = buildQueryString(params);
            history.replaceState(null, '', qs2 ? '?' + qs2 : '/');
            table.replaceData();
            setMoreOpen(false);
            renderActiveFilters();
        });
    }

    // ── "More filters" panel and active-filter chips ─────────────────────────

    const moreBtn   = qs('#filter-more-btn');
    const morePanel = qs('#filter-more');
    const moreCount = qs('#filter-more-count');
    const activeBox = qs('#filter-chips');
    const activeEl  = qs('#filter-chips-list');

    function setMoreOpen(open) {
        if (!moreBtn || !morePanel) return;
        morePanel.hidden = !open;
        moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (moreBtn && morePanel) {
        moreBtn.addEventListener('click', function () {
            setMoreOpen(morePanel.hidden);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !morePanel.hidden) {
                setMoreOpen(false);
                moreBtn.focus();
            }
        });
        document.addEventListener('click', function (e) {
            if (morePanel.hidden || !filterForm) return;
            // Chip remove buttons are detached by the time this runs.
            if (!e.target.isConnected) return;
            if (!filterForm.contains(e.target)) setMoreOpen(false);
        });
    }

    function describeGroup(el) {
        const checked = el.querySelectorAll('input[type="checkbox"]:checked');
        if (checked.length) {
            return Array.from(checked, function (c) {
                return c.parentElement.textContent.trim();
            }).join(', ');
        }
        const nums = el.querySelectorAll('input[type="number"]');
        if (nums.length === 1) {
            return nums[0].value !== '' ? '≥ ' + nums[0].value : '';
        }
        if (nums.length === 2) {
            const lo = nums[0].value, hi = nums[1].value;
            if (lo !== '' && hi !== '') return lo + '–' + hi;
            if (lo !== '') return '≥ ' + lo;
            if (hi !== '') return '≤ ' + hi;
        }
        return '';
    }

    function makeChip(label, text, onRemove) {
        const chip = document.createElement('span');
        chip.className = 'ability-chip';
        const b = document.createElement('b');
        b.textContent = label;
        chip.appendChild(b);
        chip.appendChild(document.createTextNode(' ' + text));
        const rm = document.createElement('button');
        rm.type = 'button';
        rm.textContent = '✕';
        rm.setAttribute('aria-label', 'Remove ' + label + ' filter');
        rm.addEventListener('click', onRemove);
        chip.appendChild(rm);
        return chip;
    }

    function resubmit() {
        if (filterForm) filterForm.requestSubmit();
    }

    function renderActiveFilters() {
        if (!morePanel || !activeEl || !activeBox) return;
        activeEl.innerHTML = '';
        let n = 0;
        morePanel.querySelectorAll('[data-chip-label]').forEach(function (el) {
            const text = describeGroup(el);
            if (!text) return;
            n++;
            activeEl.appendChild(makeChip(el.dataset.chipLabel, text, function () {
                el.querySelectorAll('input[type="checkbox"]').forEach(function (c) { c.checked = false; });
                el.querySelectorAll('input[type="number"]').forEach(function (i) { i.value = ''; });
                resubmit();
            }));
        });
        const abilities = inputsEl ? inputsEl.querySelectorAll('input[type="hidden"]') : [];
        if (abilities.length) {
            n++;
            const mode = qs('input[name="ability_mode"]:checked');
            const joiner = mode && mode.value === 'or' ? ' or ' : ' and ';
            const text = Array.from(abilities, function (i) {
                const [tag, threshold] = i.value.split(':');
                return threshold ? tag + ' ≥' + threshold : tag;
            }).join(joiner);
            activeEl.appendChild(makeChip('Tags', text, function () {
                abilities.forEach(function (i) { i.remove(); });
                renderChips();
                resubmit();
            }));
        }
        activeBox.hidden = n === 0;
        if (moreCount) {
            moreCount.hidden = n === 0;
            moreCount.textContent = String(n);
        }
    }

    // ── Ability multi-select builder ──────────────────────────────────────────

    const tagSelect   = qs('#ability-tag-select');
    const valInput    = qs('#ability-val-input');
    const addBtn      = qs('#ability-add-btn');
    const chipsEl     = qs('#ability-chips');
    const inputsEl    = qs('#ability-inputs');

    function renderChips() {
        if (!chipsEl || !inputsEl) return;
        chipsEl.innerHTML = '';
        const hidden = inputsEl.querySelectorAll('input[type="hidden"]');
        hidden.forEach(function (inp) {
            const val = inp.value;           // e.g. "LRM:2" or "CASE"
            const [tag, threshold] = val.split(':');
            const chip = document.createElement('span');
            chip.className = 'ability-chip';
            chip.textContent = threshold ? tag + ' ≥' + threshold : tag;
            const rm = document.createElement('button');
            rm.type = 'button';
            rm.textContent = '✕';
            rm.setAttribute('aria-label', 'Remove ' + tag);
            rm.addEventListener('click', function () {
                inp.remove();
                renderChips();
            });
            chip.appendChild(rm);
            chipsEl.appendChild(chip);
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const tag = tagSelect ? tagSelect.value.trim() : '';
            if (!tag) return;
            const v = valInput ? valInput.value.trim() : '';
            const encoded = v !== '' ? tag + ':' + parseInt(v, 10) : tag;

            // Deduplicate: remove existing entry for same tag before adding
            if (inputsEl) {
                inputsEl.querySelectorAll('input[type="hidden"]').forEach(function (inp) {
                    if (inp.value === tag || inp.value.startsWith(tag + ':')) {
                        inp.remove();
                    }
                });

                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ability[]';
                inp.value = encoded;
                inputsEl.appendChild(inp);
            }

            if (tagSelect) tagSelect.value = '';
            if (valInput)  valInput.value  = '';
            renderChips();
        });
    }

    // Initialise chips from server-rendered state (bookmarked URL)
    renderChips();
    renderActiveFilters();

})();
