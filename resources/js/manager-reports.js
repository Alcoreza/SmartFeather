const reportCards = [
    {
        key: 'farm_status',
        title: 'Farm Status',
        columns: [
            { key: 'house', label: 'House' },
            { key: 'pen', label: 'Pen' },
            { key: 'batch', label: 'Batch' },
            { key: 'average_weight', label: 'Average<br>Weight' },
            { key: 'target', label: 'Target' },
            { key: 'status', label: 'Status' },
            { key: 'date', label: 'Date' },
        ],
    },
    {
        key: 'feed_consumption',
        title: 'Feed Consumption',
        columns: [
            { key: 'feed', label: 'Feed' },
            { key: 'house_number', label: 'House<br>Number' },
            { key: 'pen_name', label: 'Pen' },
            { key: 'feeder_number', label: 'Feeder<br>Number' },
            { key: 'kilograms_used', label: 'Kilograms<br>Used' },
            { key: 'recorded_at', label: 'Recorded At' },
        ],
    },
    {
        key: 'mortality',
        title: 'Mortality',
        columns: [
            { key: 'house_number', label: 'House' },
            { key: 'pen_name', label: 'Pen' },
            { key: 'eggs_hatched', label: 'Eggs<br>Hatched' },
            { key: 'mortality', label: 'Mortality' },
            { key: 'recorded_at', label: 'Recorded At' },
        ],
    },
];

const reportContent = document.getElementById('reportContent');
const reportsFilterForm = document.getElementById('reportsFilterForm');
const reportsHouse = document.getElementById('reportsHouse');

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderReportTable(columns, rows) {
    if (!rows || !rows.length) {
        return '<div class="reports-empty">No data available.</div>';
    }

    const headHtml = columns.map((column) => `<th>${column.label}</th>`).join('');
    const bodyHtml = rows.map((row) => {
        const cells = columns.map((column) => {
            return `<td>${escapeHtml(row[column.key])}</td>`;
        }).join('');

        return `<tr>${cells}</tr>`;
    }).join('');

    return `
        <div class="reports-table-scroll">
            <table class="reports-table">
                <thead>
                    <tr>${headHtml}</tr>
                </thead>
                <tbody>${bodyHtml}</tbody>
            </table>
        </div>
    `;
}

function renderReportCards() {
    if (!reportContent) return;

    reportContent.innerHTML = reportCards.map((card) => `
        <section class="reports-card reports-data-card" data-report-card="${card.key}">
            <div class="reports-card-header">
                <h2>${escapeHtml(card.title)}</h2>
            </div>
            <div class="reports-card-body">
                <div class="reports-empty">Loading records...</div>
            </div>
        </section>
    `).join('');
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }

    return response.json();
}

function updateReportCard(card, rows) {
    const cardElement = reportContent?.querySelector(`[data-report-card="${card.key}"]`);
    const body = cardElement?.querySelector('.reports-card-body');

    if (!body) return;

    body.innerHTML = renderReportTable(card.columns, rows);
}

function buildReportsUrl() {
    const params = new URLSearchParams();
    const fields = new FormData(reportsFilterForm);

    fields.forEach((value, key) => {
        if (String(value || '').trim()) {
            params.append(key, value);
        }
    });

    return params.toString()
        ? `/api/manager/reports?${params.toString()}`
        : '/api/manager/reports';
}

function fillSelect(select, options, placeholder) {
    if (!select) return;

    const currentValue = select.value;
    select.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>`;

    (options || []).forEach((option) => {
        const element = document.createElement('option');
        element.value = option.value;
        element.textContent = option.label;
        select.appendChild(element);
    });

    select.value = [...select.options].some((option) => option.value === currentValue)
        ? currentValue
        : '';
}

function applyFilterState(filters) {
    if (!filters) return;

    fillSelect(reportsHouse, filters.options?.houses || [], 'All Houses');
}

async function loadReportsData() {
    renderReportCards();

    try {
        const result = await fetchJson(buildReportsUrl());
        applyFilterState(result.filters);

        reportCards.forEach((card) => {
            const rows = Array.isArray(result.reports?.[card.key])
                ? result.reports[card.key]
                : [];
            updateReportCard(card, rows);
        });
    } catch (error) {
        console.error('Failed to load reports:', error);

        reportCards.forEach((card) => {
            const cardElement = reportContent?.querySelector(`[data-report-card="${card.key}"]`);
            const body = cardElement?.querySelector('.reports-card-body');
            if (body) {
                body.innerHTML = '<div class="reports-empty">Unable to load records.</div>';
            }
        });
    }
}

function bindReportsFilter() {
    reportsFilterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        loadReportsData();
    });
}

async function populateProfileModal() {
    try {
        const response = await fetch('/api/user');
        if (!response.ok) throw new Error('Failed to fetch user info');
        const user = await response.json();
        document.getElementById('profileFirstName').value = user.FirstName || '';
        document.getElementById('profileMiddleName').value = user.MiddleName || '';
        document.getElementById('profileLastName').value = user.LastName || '';
        document.getElementById('profileSuffix').value = user.Suffix || '';
        document.getElementById('profileRole').value = user.Role || '';
        document.getElementById('profilePhone').value = user.PhoneNumber || '';
        document.getElementById('profileId').value = user.EmployeeId || '';
        document.getElementById('profileBirthday').value = user.Birthday || '';
        document.getElementById('profileGender').value = user.Gender || '';
        document.getElementById('profileAddress').value = user.Address || '';
    } catch (e) {
        // Profile details are non-blocking for the reports page.
    }
}

function setupProfileModal() {
    const modal = document.getElementById('profileModal');
    const openBtn = document.getElementById('openProfileModal');
    const closeBtn = document.getElementById('closeProfileModal');

    if (!modal || !openBtn) return;

    openBtn.addEventListener('click', async () => {
        await populateProfileModal();
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

document.addEventListener('DOMContentLoaded', () => {
    setupProfileModal();
    bindReportsFilter();
    loadReportsData();
});
