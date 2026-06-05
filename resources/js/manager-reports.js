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
            { key: 'mortality', label: 'Mortality' },
            { key: 'recorded_at', label: 'Recorded At' },
        ],
    },
];

const reportContent = document.getElementById('reportContent');
const reportsFilterForm = document.getElementById('reportsFilterForm');
const reportsHouse = document.getElementById('reportsHouse');

const REPORTS_ROWS_PER_PAGE = 5;
const reportPagination = {};
let currentReportData = {};

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
            <div class="reports-pagination" data-reports-pagination="${card.key}">
                <button type="button" class="reports-page-btn" data-reports-prev="${card.key}">
                    Previous
                </button>
                <div class="reports-page-dots" data-reports-dots="${card.key}"></div>
                <button type="button" class="reports-page-btn" data-reports-next="${card.key}">
                    Next
                </button>
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

    // Initialize pagination for this card if not exists
    if (!reportPagination[card.key]) {
        reportPagination[card.key] = {
            currentPage: 0,
            totalRows: rows.length,
        };
    } else {
        reportPagination[card.key].totalRows = rows.length;
        reportPagination[card.key].currentPage = 0;
    }

    // Calculate pagination
    const totalPages = Math.max(1, Math.ceil(rows.length / REPORTS_ROWS_PER_PAGE));
    const currentPage = reportPagination[card.key].currentPage;
    const start = currentPage * REPORTS_ROWS_PER_PAGE;
    const pageRows = rows.slice(start, start + REPORTS_ROWS_PER_PAGE);

    body.innerHTML = renderReportTable(card.columns, pageRows);
    updateReportsPagination(card.key, rows.length);
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
        updateSummaryCard(result.summary);

        // Store report data for pagination
        currentReportData = result.reports || {};

        // Reset pagination for all cards
        reportCards.forEach((card) => {
            reportPagination[card.key] = {
                currentPage: 0,
                totalRows: 0,
            };
        });

        reportCards.forEach((card) => {
            const rows = Array.isArray(result.reports?.[card.key])
                ? result.reports[card.key]
                : [];
            updateReportCard(card, rows);
        });

        // Setup pagination event listeners
        setupReportsPagination();
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

function updateSummaryCard(summary) {
    if (!summary) return;

    const feedConsumedEl = document.getElementById('summaryFeedConsumed');
    const mortalitiesEl = document.getElementById('summaryMortalities');
    const weightStatusEl = document.getElementById('summaryWeightStatus');

    if (feedConsumedEl) {
        feedConsumedEl.textContent = summary.total_feed_consumed
            ? `${Number(summary.total_feed_consumed).toFixed(2)} kg`
            : '-- kg';
    }

    if (mortalitiesEl) {
        mortalitiesEl.textContent = summary.total_mortalities ?? '--';
    }

    if (weightStatusEl) {
        const weight = summary.weight_status || {};
        const breakdown = [
            `Overweight: ${weight.overweight || 0}`,
            `Normal: ${weight.normal || 0}`,
            `Underweight: ${weight.underweight || 0}`,
        ].join(' | ');
        weightStatusEl.textContent = breakdown || '--';
    }
}

function bindReportsFilter() {
    reportsFilterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        loadReportsData();
    });
}

function updateReportsPagination(cardKey, totalRows) {
    const pagination = document.querySelector(`[data-reports-pagination="${cardKey}"]`);
    const prevButton = document.querySelector(`[data-reports-prev="${cardKey}"]`);
    const nextButton = document.querySelector(`[data-reports-next="${cardKey}"]`);
    const dots = document.querySelector(`[data-reports-dots="${cardKey}"]`);
    const totalPages = Math.max(1, Math.ceil(totalRows / REPORTS_ROWS_PER_PAGE));
    const currentPage = reportPagination[cardKey]?.currentPage || 0;

    if (pagination) {
        pagination.classList.toggle('is-hidden', totalRows <= REPORTS_ROWS_PER_PAGE);
    }

    if (prevButton) {
        prevButton.disabled = currentPage === 0;
    }

    if (nextButton) {
        nextButton.disabled = currentPage >= totalPages - 1;
    }

    if (dots) {
        dots.innerHTML = Array.from({ length: totalPages }, (_, index) => `
            <button
                type="button"
                class="reports-page-dot ${index === currentPage ? 'active' : ''}"
                data-reports-page="${cardKey}-${index}"
                aria-label="Go to page ${index + 1}"
                aria-current="${index === currentPage ? 'page' : 'false'}"
            ></button>
        `).join('');
    }
}

function setupReportsPagination() {
    reportContent?.addEventListener('click', (event) => {
        // Previous button click
        const prevBtn = event.target.closest('[data-reports-prev]');
        if (prevBtn) {
            const cardKey = prevBtn.dataset.reportsPrev;
            if (reportPagination[cardKey]) {
                reportPagination[cardKey].currentPage = Math.max(0, reportPagination[cardKey].currentPage - 1);
                reRenderReportCard(cardKey);
            }
            return;
        }

        // Next button click
        const nextBtn = event.target.closest('[data-reports-next]');
        if (nextBtn) {
            const cardKey = nextBtn.dataset.reportsNext;
            if (reportPagination[cardKey]) {
                const totalPages = Math.max(1, Math.ceil(reportPagination[cardKey].totalRows / REPORTS_ROWS_PER_PAGE));
                reportPagination[cardKey].currentPage = Math.min(
                    reportPagination[cardKey].currentPage + 1,
                    totalPages - 1
                );
                reRenderReportCard(cardKey);
            }
            return;
        }

        // Page dot click
        const dot = event.target.closest('[data-reports-page]');
        if (dot) {
            const [cardKey, pageIndex] = dot.dataset.reportsPage.split('-');
            if (reportPagination[cardKey]) {
                reportPagination[cardKey].currentPage = Number(pageIndex);
                reRenderReportCard(cardKey);
            }
        }
    });
}

function reRenderReportCard(cardKey) {
    const card = reportCards.find(c => c.key === cardKey);
    if (!card) return;

    const cardElement = reportContent?.querySelector(`[data-report-card="${card.key}"]`);
    const body = cardElement?.querySelector('.reports-card-body');
    if (!body) return;

    // Re-fetch data and re-render (simplified: use stored data)
    const allRows = currentReportData?.[card.key] || [];
    const currentPage = reportPagination[card.key]?.currentPage || 0;
    const start = currentPage * REPORTS_ROWS_PER_PAGE;
    const pageRows = allRows.slice(start, start + REPORTS_ROWS_PER_PAGE);

    body.innerHTML = renderReportTable(card.columns, pageRows);
    updateReportsPagination(card.key, allRows.length);
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
