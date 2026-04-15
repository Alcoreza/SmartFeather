const state = {
    selectedCategory: 'Cleaning',
    logs: {},
    houses: [],
    workers: [],
    pens: [],
    allWorkers: [],
};

const TABLE_CONFIG = {
    'Cleaning': {
        title: 'Cleaning',
        columns: [
            { key: 'house', label: 'House' },
            { key: 'pen', label: 'Pen' },
            { key: 'activity', label: 'Activity' },
            { key: 'date', label: 'Date' },
            { key: 'time', label: 'Time' },
            { key: 'disinfectant_used', label: 'Disinfectant<br>Used' },
            { key: 'performed_by', label: 'Performed<br>by:' },
        ],
        form: [
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'house', label: 'House Number', type: 'select', options: 'houses' },
                    { key: 'pen', label: 'Pen Number', type: 'select', options: 'pens' },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    { key: 'activity', label: 'Activity', type: 'text', full: true },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'date', label: 'Date', type: 'date' },
                    { key: 'time', label: 'Time', type: 'time' },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    { key: 'disinfectant_used', label: 'Disinfectant Used', type: 'text', full: true },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    { key: 'performed_by', label: 'Performed by:', type: 'select', options: 'workers' },
                ],
            },
        ],
    },

    'Personnel Biosecurity Logs': {
        title: 'Personnel Biosecurity Logs',
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'role', label: 'Role' },
            { key: 'house', label: 'House' },
            { key: 'date', label: 'Date' },
            { key: 'time', label: 'Time' },
            { key: 'foot_bath', label: 'Foot Bath' },
            { key: 'boots_changed', label: 'Boots<br>Changed' },
            { key: 'protective_clothing', label: 'Protective<br>Clothing' },
        ],
        form: [
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'name', label: 'Name', type: 'text' },
                    { key: 'role', label: 'Role', type: 'text' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'house', label: 'House', type: 'select', options: 'houses' },
                    { key: 'date', label: 'Date', type: 'date' },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    { key: 'time', label: 'Time', type: 'time', full: true },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    {
                        key: 'foot_bath',
                        label: 'Foot Bath',
                        type: 'select',
                        options: ['Yes', 'No'],
                    },
                    {
                        key: 'boots_changed',
                        label: 'Boots Changed',
                        type: 'select',
                        options: ['Yes', 'No'],
                    },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    {
                        key: 'protective_clothing',
                        label: 'Protective Clothing',
                        type: 'select',
                        options: ['Yes', 'No'],
                        full: true,
                    },
                ],
            },
        ],
    },

    'Visitors': {
        title: 'Visitors',
        columns: [
            { key: 'date', label: 'Date' },
            { key: 'time_in', label: 'Time In' },
            { key: 'time_out', label: 'Time Out' },
            { key: 'name', label: 'Name' },
            { key: 'purpose', label: 'Purpose' },
            { key: 'foot_bath', label: 'Foot Bath' },
            { key: 'sanitation', label: 'Sanitation' },
            { key: 'ppe', label: 'PPE' },
            { key: 'monitored_by', label: 'Monitored<br>By:' },
        ],
        form: [
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'date', label: 'Date', type: 'date' },
                    { key: 'name', label: 'Name', type: 'text' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'time_in', label: 'Time In', type: 'time' },
                    { key: 'time_out', label: 'Time Out', type: 'time' },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    { key: 'purpose', label: 'Purpose', type: 'text', full: true },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    {
                        key: 'foot_bath',
                        label: 'Foot Bath',
                        type: 'select',
                        options: ['Yes', 'No'],
                    },
                    {
                        key: 'sanitation',
                        label: 'Sanitation',
                        type: 'select',
                        options: ['Yes', 'No'],
                    },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    {
                        key: 'ppe',
                        label: 'PPE',
                        type: 'select',
                        options: ['Yes', 'No'],
                    },
                    {
                        key: 'monitored_by',
                        label: 'Monitored By',
                        type: 'select',
                        options: 'allWorkers',
                    },
                ],
            },
        ],
    },

    'Personnel Entry Logs': {
        title: 'Personnel Entry Logs',
        columns: [
            { key: 'name', label: 'Name' },
            { key: 'role', label: 'Role' },
            { key: 'house', label: 'House' },
            { key: 'date', label: 'Date' },
            { key: 'time', label: 'Time' },
        ],
        form: [
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'name', label: 'Name', type: 'text' },
                    { key: 'role', label: 'Role', type: 'text' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'house', label: 'House', type: 'select', options: 'houses' },
                    { key: 'date', label: 'Date', type: 'date' },
                ],
            },
            {
                rowClass: 'bio-modal-row',
                fields: [
                    { key: 'time', label: 'Time', type: 'time', full: true },
                ],
            },
        ],
    },

    'Weight Sampling': {
        title: 'Weight Sampling',
        columns: [
            { key: 'date', label: 'Date' },
            { key: 'time', label: 'Time' },
            { key: 'house', label: 'House' },
            { key: 'pen', label: 'Pen' },
            { key: 'batch', label: 'Batch' },
            { key: 'flocks_with_cases', label: 'Flocks with<br>Cases' },
            { key: 'age', label: 'Age' },
            { key: 'average_weight', label: 'Average<br>Weight' },
            { key: 'target', label: 'Target' },
            { key: 'status', label: 'Status' },
        ],
        form: [
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'date', label: 'Date', type: 'date' },
                    { key: 'time', label: 'Time', type: 'time' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'house', label: 'House', type: 'select', options: 'houses' },
                    { key: 'pen', label: 'Pen', type: 'select', options: 'pens' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'batch', label: 'Batch', type: 'text' },
                    { key: 'flocks_with_cases', label: 'Flocks with Cases', type: 'text' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'age', label: 'Age', type: 'text' },
                    { key: 'average_weight', label: 'Average Weight', type: 'text' },
                ],
            },
            {
                rowClass: 'bio-modal-row two-cols',
                fields: [
                    { key: 'target', label: 'Target', type: 'text' },
                    {
                        key: 'status',
                        label: 'Status',
                        type: 'select',
                        options: ['Normal', 'Underweight', 'Overweight'],
                    },
                ],
            },
        ],
    },
};

const tableHead = document.getElementById('bioTableHead');
const tableBody = document.getElementById('bioLogsTableBody');
const filterSelect = document.getElementById('bioCategoryFilter');

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setOverview(overview) {
    const violations = document.getElementById('bioViolations');
    const visitors = document.getElementById('bioVisitors');
    const mortalities = document.getElementById('bioMortalities');
    const disinfectionDate = document.getElementById('bioDisinfectionDate');
    const disinfectionTime = document.getElementById('bioDisinfectionTime');

    if (violations) violations.textContent = overview?.violations ?? 0;
    if (visitors) visitors.textContent = overview?.visitors ?? 0;
    if (mortalities) mortalities.textContent = overview?.mortalities ?? 0;
    if (disinfectionDate) disinfectionDate.textContent = overview?.last_disinfection?.date ?? '--';
    if (disinfectionTime) disinfectionTime.textContent = overview?.last_disinfection?.time ?? '--';
}

function renderTableHead(type) {
    const config = TABLE_CONFIG[type];
    if (!config || !tableHead) return;

    const headers = config.columns.map((col) => `<th>${col.label}</th>`).join('');

    tableHead.innerHTML = `
        <tr>
            ${headers}
            <th></th>
        </tr>
    `;
}

function renderTableRows(type, rows) {
    const config = TABLE_CONFIG[type];
    if (!config || !tableBody) return;

    if (!rows.length) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="${config.columns.length + 1}" class="bio-empty">
                    No logs found for this category.
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = rows.map((row) => {
        const cells = config.columns.map((col) => `
            <td>${escapeHtml(row[col.key])}</td>
        `).join('');

        const encodedRow = encodeURIComponent(JSON.stringify(row));

        return `
            <tr>
                ${cells}
                <td>
                    <button
                        type="button"
                        class="bio-action-btn edit-btn"
                        data-mode="${escapeHtml(type)}"
                        data-log="${encodedRow}"
                        aria-label="Edit log"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-4-4L4 16v4z"></path>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function renderCurrentTable() {
    const type = state.selectedCategory;
    const rows = state.logs[type] || [];

    renderTableHead(type);
    renderTableRows(type, rows);
}

function createFieldHtml(prefix, field, value = '') {
    const fieldId = `${prefix}_${field.key}`;
    const wrapperClass = field.full ? 'bio-field full' : 'bio-field';

    if (field.type === 'select') {
        let options = field.options || [];
        
        // Handle dynamic options from state
        if (field.options === 'houses' && state.houses) {
            options = state.houses.map(h => ({ value: h.id, label: h.number }));
        } else if (field.options === 'workers' && state.workers) {
            options = state.workers.map(w => ({ value: w.id, label: w.name }));
        } else if (field.options === 'allWorkers' && state.allWorkers) {
            options = state.allWorkers.map(w => ({ value: w.id, label: w.name }));
        } else if (field.options === 'pens' && state.pens) {
            options = state.pens.map(p => ({ value: p.number, label: p.label }));
        }

        const optionsHtml = options.map((option) => {
            const optValue = option.value ?? option;
            const optLabel = option.label ?? option;
            return `<option value="${escapeHtml(optValue)}" ${String(value) === String(optValue) ? 'selected' : ''}>
                ${escapeHtml(optLabel)}
            </option>`;
        }).join('');

        return `
            <div class="${wrapperClass}">
                <label for="${fieldId}">${field.label}</label>
                <select id="${fieldId}" name="${field.key}" class="bio-select-placeholder">
                    <option value="">- - -</option>
                    ${optionsHtml}
                </select>
            </div>
        `;
    }

    // For date/time fields, set max to today
    let extraAttrs = '';
    if (field.type === 'date') {
        extraAttrs = `max="${new Date().toISOString().split('T')[0]}"`;
    }

    return `
        <div class="${wrapperClass}">
            <label for="${fieldId}">${field.label}</label>
            <input
                type="${field.type || 'text'}"
                id="${fieldId}"
                name="${field.key}"
                value="${escapeHtml(value)}"
                ${extraAttrs}
            >
        </div>
    `;
}

function buildModalFields(prefix, type, values = {}) {
    const config = TABLE_CONFIG[type];
    if (!config) return '';

    return config.form.map((row) => {
        const fieldsHtml = row.fields
            .map((field) => createFieldHtml(prefix, field, values[field.key] ?? ''))
            .join('');

        return `<div class="${row.rowClass}">${fieldsHtml}</div>`;
    }).join('');
}

function decodeRowData(encodedData) {
    if (!encodedData) return {};

    try {
        return JSON.parse(decodeURIComponent(encodedData));
    } catch (error) {
        console.error('Invalid row data:', error);
        return {};
    }
}

function setupEditModal() {
    const modal = document.getElementById('editBioModal');
    const closeBtn = document.getElementById('closeBioModal');
    const title = document.getElementById('editBioModalTitle');
    const fieldsWrap = document.getElementById('editBioModalFields');
    const logIdInput = document.getElementById('editLogId');
    const logTypeInput = document.getElementById('editLogType');

    if (!modal || !closeBtn || !title || !fieldsWrap) return;

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('.edit-btn');
        if (!btn) return;

        const type = btn.dataset.mode || 'Cleaning';
        const rowData = decodeRowData(btn.dataset.log);

        // Load form options if not already loaded
        if (!state.houses || !state.workers) {
            await loadBioFormOptions();
        }

        title.textContent = `Edit ${type}`;
        fieldsWrap.innerHTML = buildModalFields('edit', type, rowData);

        if (logIdInput) logIdInput.value = rowData.id ?? '';
        if (logTypeInput) logTypeInput.value = type;

        // Set up house change event to load pens for edit modal
        const houseSelect = document.getElementById('edit_house');
        const penSelect = document.getElementById('edit_pen');
        
        if (houseSelect) {
            houseSelect.addEventListener('change', async () => {
                const houseId = houseSelect.value;
                if (houseId && penSelect) {
                    await loadBioPensForHouse(houseId, 'edit_pen');
                }
            });

            // If there's a house value, load pens for it
            if (rowData.house) {
                await loadBioPensForHouse(rowData.house, 'edit_pen');
            }
        }

        modal.classList.add('show');
    });

    // Handle form submission for edit
    const form = document.getElementById('editBioForm');
    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(form);
            const logId = logIdInput?.value;

            if (!logId) {
                alert('Invalid log ID');
                return;
            }

            try {
                const response = await fetch(`/api/manager/biosecurity-logs/${logId}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PUT',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Server error:', errorData);
                    throw new Error(`Failed to update: ${response.status}`);
                }

                const result = await response.json();
                console.log('Log updated:', result);

                modal.classList.remove('show');

                // Reload logs to show the updated entry
                loadBiosecurityLogs();
            } catch (error) {
                console.error('Error updating log:', error);
                alert('Failed to update log. Please try again.');
            }
        });
    }

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('show');
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
}

function setupAddModal() {
    const modal = document.getElementById('addBioModal');
    const openBtn = document.getElementById('openAddBioModal');
    const closeBtn = document.getElementById('closeAddBioModal');
    const title = document.getElementById('addBioModalTitle');
    const fieldsWrap = document.getElementById('addBioModalFields');
    const logTypeInput = document.getElementById('addLogType');

    if (!modal || !openBtn || !closeBtn || !title || !fieldsWrap) return;

    openBtn.addEventListener('click', async () => {
        const type = state.selectedCategory;

        // Always reload form options for Cleaning, Personnel Biosecurity Logs, Visitors, Personnel Entry Logs, and Weight Sampling
        if (type === 'Cleaning' || type === 'Personnel Biosecurity Logs' || type === 'Visitors' || type === 'Personnel Entry Logs' || type === 'Weight Sampling') {
            await loadBioFormOptions();
        }
        
        // Also load all workers for Visitors modal
        if (type === 'Visitors' && (!state.allWorkers || state.allWorkers.length === 0)) {
            await loadAllWorkers();
        } else if (!state.houses || !state.workers) {
            await loadBioFormOptions();
        }

        title.textContent = `Add ${type}`;
        fieldsWrap.innerHTML = buildModalFields('add', type, {});

        if (logTypeInput) logTypeInput.value = type;

        // Set up house change event to load pens (for Cleaning and Weight Sampling)
        const houseSelect = document.getElementById('add_house');
        const penSelect = document.getElementById('add_pen');
        
        if (houseSelect) {
            houseSelect.addEventListener('change', async () => {
                const houseId = houseSelect.value;
                if (houseId && penSelect) {
                    await loadBioPensForHouse(houseId, 'add_pen');
                }
            });
            
            // For Weight Sampling, also load pens on modal open if house is pre-selected
            if (type === 'Weight Sampling') {
                const initialHouseId = houseSelect.value;
                if (initialHouseId) {
                    await loadBioPensForHouse(initialHouseId, 'add_pen');
                }
            }
        }

        modal.classList.add('show');
    });

    // Handle form submission
    const form = document.getElementById('addBioForm');
    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(form);

            try {
                const response = await fetch('/api/manager/biosecurity-logs', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    console.error('Server error:', errorData);
                    throw new Error(`Failed to save: ${response.status}`);
                }

                const result = await response.json();
                console.log('Log saved:', result);

                modal.classList.remove('show');

                // Reload logs to show the new entry
                loadBiosecurityLogs();
            } catch (error) {
                console.error('Error saving log:', error);
                alert('Failed to save log. Please try again.');
            }
        });
    }

    closeBtn.addEventListener('click', () => {
        modal.classList.remove('show');
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
}

function bindEvents() {
    if (!filterSelect) return;

    filterSelect.addEventListener('change', (event) => {
        state.selectedCategory = event.target.value;
        renderCurrentTable();
    });
}

async function loadBiosecurityLogs() {
    try {
        const response = await fetch('/api/manager/biosecurity-logs', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const data = await response.json();

        setOverview(data.overview || {});
        state.logs = data.logs || {};

        renderCurrentTable();
    } catch (error) {
        console.error('Failed to load biosecurity logs:', error);

        if (tableHead) {
            tableHead.innerHTML = `
                <tr>
                    <th>Logs</th>
                </tr>
            `;
        }

        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td class="bio-empty">Unable to load biosecurity logs.</td>
                </tr>
            `;
        }
    }
}

function setupProfileModal() {
    const modal = document.getElementById('profileModal');
    const openBtn = document.getElementById('openProfileModal');
    const closeBtn = document.getElementById('closeProfileModal');

    if (!modal || !openBtn) return;

    openBtn.addEventListener('click', () => {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        });
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
}

async function loadBioFormOptions() {
    try {
        const response = await fetch("/api/manager/tasks/form-options");
        const data = await response.json();

        // Store for reuse
        state.houses = data.houses || [];
        state.workers = data.workers || [];
        state.pens = [];

        return { houses: state.houses, workers: state.workers };
    } catch (error) {
        console.error("Failed to load form options:", error);
        return { houses: [], workers: [], pens: [] };
    }
}

async function loadAllWorkers() {
    try {
        const response = await fetch("/api/manager/tasks/all-workers");
        const data = await response.json();

        // Store all workers for Visitors modal
        state.allWorkers = data.workers || [];

        return state.allWorkers;
    } catch (error) {
        console.error("Failed to load all workers:", error);
        return [];
    }
}

async function loadBioPensForHouse(houseId, selectId) {
    const penSelect = document.getElementById(selectId);
    if (!penSelect) return;

    if (!houseId) {
        penSelect.innerHTML = '<option value="">- - -</option>';
        penSelect.disabled = true;
        return;
    }

    try {
        const response = await fetch(`/api/manager/tasks/houses/${houseId}/pens`);
        const data = await response.json();

        state.pens = data.pens || [];

        penSelect.innerHTML = '<option value="">- - -</option>' +
            state.pens.map(p => `<option value="${p.number}">${p.label}</option>`).join('');
        
        penSelect.disabled = false;
    } catch (error) {
        console.error("Failed to load pens:", error);
        penSelect.innerHTML = '<option value="">Error</option>';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    bindEvents();
    loadBiosecurityLogs();
    setupEditModal();
    setupAddModal();
    setupProfileModal();
});