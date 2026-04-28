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
                    parts.push(encodeURIComponent('ability[]') + '=' + encodeURIComponent(item));
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
                title: '', field: 'id', sorter: false, width: 46, hozAlign: 'center',
                headerSort: false, resizable: false,
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
            { title: 'Name',      field: 'name',      sorter: 'string',  minWidth: 130,
                formatter: function (cell) {
                    const row = cell.getRow().getData();
                    return '<a href="/units/' + row.id + '">' + (cell.getValue() || '') + '</a>';
                }
            },
            { title: 'Class',     field: 'class',     sorter: 'string',  minWidth: 100 },
            { title: 'Variant',   field: 'variant',   sorter: 'string',  minWidth: 80 },
            { title: 'T',         field: 'tonnage',   sorter: 'number',  width: 55, hozAlign: 'right' },
            { title: 'PV',        field: 'pv',        sorter: 'number',  width: 55, hozAlign: 'right' },
            { title: 'Move',      field: 'move',      sorter: 'string',  width: 75, hozAlign: 'center' },
            { title: 'TMM',       field: 'tmm',       sorter: 'number',  width: 60, hozAlign: 'right' },
            { title: 'S',         field: 'dmg_s',     sorter: 'number',  width: 45, hozAlign: 'center' },
            { title: 'M',         field: 'dmg_m',     sorter: 'number',  width: 45, hozAlign: 'center' },
            { title: 'L',         field: 'dmg_l',     sorter: 'number',  width: 45, hozAlign: 'center' },
            { title: 'Armor',     field: 'armor',     sorter: 'number',  width: 62, hozAlign: 'right' },
            { title: 'Str',       field: 'structure', sorter: 'number',  width: 55, hozAlign: 'right' },
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

    // ── Filter form ───────────────────────────────────────────────────────────

    const filterForm = qs('#filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const params = collectFormParams();
            const qs2 = buildQueryString(params);
            history.replaceState(null, '', qs2 ? '?' + qs2 : '/');
            table.replaceData();
        });
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

    // Initialise chips from server-rendered hidden inputs (bookmarked URL)
    renderChips();

})();
