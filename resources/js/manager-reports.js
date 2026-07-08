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
const REPORTS_DOT_LIMIT = 5;
const reportPagination = {};
let currentReportData = {};
let reportsLastPages = {};
let reportsPaginationBound = false;
let currentUserProfile = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatUserFullName(user) {
    return [
        user?.FirstName,
        user?.MiddleName,
        user?.LastName,
        user?.Suffix,
    ].map((part) => String(part || '').trim()).filter(Boolean).join(' ');
}

async function getCurrentUserProfile() {
    if (currentUserProfile) {
        return currentUserProfile;
    }

    const response = await fetch('/api/user');
    if (!response.ok) {
        throw new Error('Failed to fetch user info');
    }

    currentUserProfile = await response.json();
    return currentUserProfile;
}

async function getExporterInfo() {
    try {
        const user = await getCurrentUserProfile();
        const name = formatUserFullName(user) || user.Username || '--';

        return {
            name,
            role: user.Role || '--',
        };
    } catch (error) {
        return {
            name: '--',
            role: '--',
        };
    }
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
                <div>
                    <h2>${escapeHtml(card.title)}</h2>
                </div>
                <span class="reports-row-count" data-report-count="${card.key}">0 records</span>
            </div>
            <div class="reports-card-body">
                <div class="reports-empty">Loading records...</div>
            </div>
            <div class="reports-pagination" data-reports-pagination="${card.key}">
                <button type="button" class="reports-page-btn" data-reports-prev="${card.key}">
                    Previous
                </button>
                <div class="reports-page-dots" data-page-direction="still" data-reports-dots="${card.key}"></div>
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
    const count = cardElement?.querySelector(`[data-report-count="${card.key}"]`);

    if (!body) return;

    if (count) {
        count.textContent = `${rows.length} ${rows.length === 1 ? 'record' : 'records'}`;
    }

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
        weightStatusEl.innerHTML = [
            ['Overweight', weight.overweight || 0, 'over'],
            ['Normal', weight.normal || 0, 'normal'],
            ['Underweight', weight.underweight || 0, 'under'],
        ].map(([label, value, tone]) => `
            <span class="reports-weight-chip ${tone}">
                <span>${label}</span>
                <strong>${value}</strong>
            </span>
        `).join('');
    }
}

function bindReportsFilter() {
    reportsFilterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        loadReportsData();
    });
}

function getVisibleReportPages(totalPages, currentPage) {
    if (totalPages <= REPORTS_DOT_LIMIT) {
        return Array.from({ length: totalPages }, (_, index) => index);
    }

    const centerOffset = Math.floor(REPORTS_DOT_LIMIT / 2);
    let start = Math.max(0, currentPage - centerOffset);
    let end = start + REPORTS_DOT_LIMIT;

    if (end > totalPages) {
        end = totalPages;
        start = Math.max(0, end - REPORTS_DOT_LIMIT);
    }

    return Array.from({ length: end - start }, (_, index) => start + index);
}

function updateReportsPagination(cardKey, totalRows) {
    const pagination = document.querySelector(`[data-reports-pagination="${cardKey}"]`);
    const prevButton = document.querySelector(`[data-reports-prev="${cardKey}"]`);
    const nextButton = document.querySelector(`[data-reports-next="${cardKey}"]`);
    const dots = document.querySelector(`[data-reports-dots="${cardKey}"]`);
    const totalPages = Math.max(1, Math.ceil(totalRows / REPORTS_ROWS_PER_PAGE));
    const currentPage = reportPagination[cardKey]?.currentPage || 0;
    const lastPage = reportsLastPages[cardKey] ?? currentPage;
    const direction = currentPage > lastPage ? 'next' : currentPage < lastPage ? 'prev' : 'still';
    const visiblePages = getVisibleReportPages(totalPages, currentPage);

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
        dots.dataset.pageDirection = direction;
        dots.innerHTML = visiblePages.map((index) => `
            <button
                type="button"
                class="reports-page-dot ${index === currentPage ? 'active' : ''}"
                data-reports-page="${cardKey}-${index}"
                aria-label="Go to page ${index + 1}"
                aria-current="${index === currentPage ? 'page' : 'false'}"
            ></button>
        `).join('');
    }

    reportsLastPages[cardKey] = currentPage;
}

function setupReportsPagination() {
    if (reportsPaginationBound) return;
    reportsPaginationBound = true;

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
        const user = await getCurrentUserProfile();
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

function generateCsvExport() {
    const fromDate = document.getElementById('reportsFromDate')?.value || 'All Dates';
    const toDate = document.getElementById('reportsToDate')?.value || 'All Dates';
    const house = document.getElementById('reportsHouse')?.value || 'All Houses';
    const feedConsumed = document.getElementById('summaryFeedConsumed')?.textContent || '--';
    const mortalities = document.getElementById('summaryMortalities')?.textContent || '--';
    const weightStatus = document.getElementById('summaryWeightStatus')?.textContent || '--';

    let csvContent = 'data:text/csv;charset=utf-8,';
    csvContent += 'Farm Status Report\n';
    csvContent += `Generated: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}\n`;
    csvContent += `Date Range: ${fromDate} to ${toDate}\n`;
    csvContent += `House: ${house}\n\n`;

    // Add summary statistics
    csvContent += 'SUMMARY STATISTICS\n';
    csvContent += '"Metric","Value"\n';
    csvContent += `"Total Feed Consumed","${feedConsumed}"\n`;
    csvContent += `"Total Mortalities","${mortalities}"\n`;
    csvContent += `"Overall Farm Weight Status","${weightStatus}"\n\n`;

    reportCards.forEach((card) => {
        const rows = currentReportData[card.key] || [];
        csvContent += `${card.title}\n`;

        const headers = card.columns.map((col) => `"${col.label.replace(/<br>/g, ' ')}"`).join(',');
        csvContent += headers + '\n';

        rows.forEach((row) => {
            const values = card.columns.map((col) => {
                const value = row[col.key] ?? '';
                const stringValue = String(value).replace(/"/g, '""');
                return `"${stringValue}"`;
            }).join(',');
            csvContent += values + '\n';
        });

        csvContent += '\n';
    });

    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', `farm-report-${new Date().getTime()}.csv`);
    link.click();
}

async function generatePdfExport() {
    const fromDate = document.getElementById('reportsFromDate')?.value || 'All Dates';
    const toDate = document.getElementById('reportsToDate')?.value || 'All Dates';
    const house = document.getElementById('reportsHouse')?.value || 'All Houses';
    const feedConsumed = document.getElementById('summaryFeedConsumed')?.textContent || '--';
    const mortalities = document.getElementById('summaryMortalities')?.textContent || '--';
    const weightStatus = document.getElementById('summaryWeightStatus')?.textContent || '--';
    const printWindow = window.open('', '', 'width=900,height=700');
    if (!printWindow) return;

    const exporter = await getExporterInfo();

    let htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Farm Status Report</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
                .header { margin-bottom: 30px; border-bottom: 2px solid #2f7446; padding-bottom: 20px; }
                .header h1 { font-size: 24px; color: #000; margin-bottom: 10px; }
                .header-info { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 13px; color: #666; }
                .section { margin-bottom: 30px; page-break-inside: avoid; }
                .section-title { font-size: 16px; font-weight: bold; color: #2f7446; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 1px solid #ddd; }
                .summary-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 30px; }
                .summary-item { padding: 15px; background: #f5f5f5; border-left: 4px solid #2f7446; border-radius: 4px; }
                .summary-item-label { font-size: 12px; font-weight: 600; color: #666; margin-bottom: 8px; }
                .summary-item-value { font-size: 16px; font-weight: bold; color: #1d6f24; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
                th { background: #f0f0f0; padding: 10px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
                td { padding: 8px; border-bottom: 1px solid #eee; }
                tr:nth-child(even) { background: #f9f9f9; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 11px; color: #999; }
                @media print {
                    body { padding: 0; }
                    .section { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📊 Farm Status Report</h1>
                <div class="header-info">
                    <div><strong>Generated:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</div>
                    <div><strong>Exported By:</strong> ${escapeHtml(exporter.name)}</div>
                    <div><strong>Role:</strong> ${escapeHtml(exporter.role)}</div>
                    <div><strong>Date Range:</strong> ${escapeHtml(fromDate)} to ${escapeHtml(toDate)}</div>
                    <div><strong>House:</strong> ${escapeHtml(house)}</div>
                    <div><strong>Report Type:</strong> Comprehensive</div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Summary Statistics</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-item-label">Total Feed Consumed</div>
                        <div class="summary-item-value">${escapeHtml(feedConsumed)}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-item-label">Total Mortalities</div>
                        <div class="summary-item-value">${escapeHtml(mortalities)}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-item-label">Overall Farm Weight Status</div>
                        <div class="summary-item-value">${escapeHtml(weightStatus)}</div>
                    </div>
                </div>
            </div>
    `;

    reportCards.forEach((card) => {
        const rows = currentReportData[card.key] || [];
        if (!rows.length) return;

        htmlContent += `
            <div class="section">
                <div class="section-title">${card.title}</div>
                <table>
                    <thead>
                        <tr>
                            ${card.columns.map((col) => `<th>${col.label.replace(/<br>/g, ' ')}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map((row) => `
                            <tr>
                                ${card.columns.map((col) => `<td>${escapeHtml(row[col.key] ?? '')}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    });

    htmlContent += `
            <div class="footer">
                <p>This report was automatically generated by the Farm Management System.</p>
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(htmlContent);
    printWindow.document.close();
    printWindow.print();
}

function setupExportModal() {
    const modal = document.getElementById('exportModal');
    const openBtn = document.getElementById('openExportModal');
    const closeBtn = document.getElementById('closeExportModal');
    const cancelBtn = document.getElementById('cancelExportModal');
    const pdfBtn = document.getElementById('exportPdfBtn');
    const csvBtn = document.getElementById('exportCsvBtn');

    if (!modal || !openBtn) return;

    function resetSelectedExportFormat() {
        [pdfBtn, csvBtn].forEach((button) => button?.classList.remove('active'));
    }

    function closeModal() {
        modal.classList.remove('show');
        resetSelectedExportFormat();
        document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', () => {
        resetSelectedExportFormat();
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    if (pdfBtn) {
        pdfBtn.addEventListener('click', () => {
            setSelectedExportFormat(pdfBtn, [csvBtn]);

            setTimeout(async () => {
                await generatePdfExport();
                closeModal();
            }, 140);
        });
    }

    if (csvBtn) {
        csvBtn.addEventListener('click', () => {
            setSelectedExportFormat(csvBtn, [pdfBtn]);

            setTimeout(() => {
                generateCsvExport();
                closeModal();
            }, 140);
        });
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
}

function setSelectedExportFormat(selectedButton, otherButtons = []) {
    otherButtons.forEach((button) => button?.classList.remove('active'));
    selectedButton?.classList.add('active');
}

document.addEventListener('DOMContentLoaded', () => {
    setupProfileModal();
    setupExportModal();
    bindReportsFilter();
    loadReportsData();
});
