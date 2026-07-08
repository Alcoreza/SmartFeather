const FARM_ACTIVITY_ROWS_PER_PAGE = 8;
const FARM_ACTIVITY_DOT_LIMIT = 5;

const farmRecordState = {
    selectedRecord: 'Hatch and Mortality Check',
    records: {},
};

let farmActivityPagination = {};
let currentFarmActivityData = {};
let farmActivityLastPages = {};
let currentUserProfile = null;

const FARM_RECORD_CONFIG = {
    'Hatch and Mortality Check': {
        mode: 'single',
        loader: loadHatchMortalityRecords,
        sections: [
            {
                columns: [
                    { key: 'performed_by', label: 'Performed By' },
                    { key: 'house_number', label: 'House' },
                    { key: 'pen_name', label: 'Pen' },
                    { key: 'eggs_hatched', label: 'Eggs<br>Hatched' },
                    { key: 'mortality', label: 'Mortality' },
                    { key: 'recorded_at', label: 'Recorded At' },
                ],
            },
        ],
    },
    'Weight Monitoring': {
        mode: 'single',
        loader: loadWeightMonitoringRecords,
        sections: [
            {
                columns: [
                    { key: 'performed_by', label: 'Performed By' },
                    { key: 'house', label: 'House' },
                    { key: 'pen', label: 'Pen' },
                    { key: 'batch', label: 'Batch' },
                    { key: 'average_weight', label: 'Average<br>Weight' },
                    { key: 'target', label: 'Target' },
                    { key: 'status', label: 'Status' },
                    { key: 'date', label: 'Date' },
                ],
            },
        ],
    },
    'Feed Replenishment': {
        mode: 'single',
        loader: loadFeedReplenishmentRecords,
        sections: [
            {
                columns: [
                    { key: 'performed_by', label: 'Performed By' },
                    { key: 'feed', label: 'Feed' },
                    { key: 'house_number', label: 'House<br>Number' },
                    { key: 'pen_name', label: 'Pen' },
                    { key: 'feeder_number', label: 'Feeder<br>Number' },
                    { key: 'kilograms_used', label: 'Kilograms<br>Used' },
                    { key: 'recorded_at', label: 'Recorded At' },
                ],
            },
        ],
    },
    'Vitamin Supplementation': {
        mode: 'single',
        loader: loadVitaminSupplementationRecords,
        sections: [
            {
                columns: [
                    { key: 'performed_by', label: 'Performed By' },
                    { key: 'vitamin', label: 'Vitamin' },
                    { key: 'house_number', label: 'House<br>Number' },
                    { key: 'pen_name', label: 'Pen' },
                    { key: 'bottles_used', label: 'Bottles<br>Used' },
                    { key: 'recorded_at', label: 'Recorded At' },
                ],
            },
        ],
    },
    'Pen Disinfection': {
        mode: 'single',
        loader: loadPenDisinfectionRecords,
        sections: [
            {
                columns: [
                    { key: 'performed_by', label: 'Performed By' },
                    { key: 'house', label: 'House' },
                    { key: 'pen', label: 'Pen' },
                    { key: 'disinfectant_used', label: 'Disinfectant<br>Used' },
                    { key: 'date', label: 'Date' },
                    { key: 'time', label: 'Time' },
                ],
            },
        ],
    },
    'Pen Cleaning': {
        mode: 'single',
        loader: loadPenCleaningRecords,
        sections: [
            {
                columns: [
                    { key: 'performed_by', label: 'Performed By' },
                    { key: 'house', label: 'House' },
                    { key: 'pen', label: 'Pen' },
                    { key: 'date', label: 'Date' },
                    { key: 'time', label: 'Time' },
                ],
            },
        ],
    },
    'Sensor Inspection': {
        mode: 'single',
        loader: loadSensorMaintenanceRecords,
        sections: [
            {
                columns: [
                    { key: 'performed_by', label: 'Performed By' },
                    { key: 'house_number', label: 'House' },
                    { key: 'pen_name', label: 'Pen' },
                    { key: 'sensor_present', label: 'Sensor<br>Present' },
                    { key: 'sensor_clean_unblocked', label: 'Sensor<br>Clean' },
                    { key: 'no_visible_damage_or_loose_wiring', label: 'No<br>Damage' },
                    { key: 'power_status_on', label: 'Power<br>Status' },
                    { key: 'placement_secure', label: 'Placement<br>Secure' },
                    { key: 'date', label: 'Date' },
                    { key: 'time', label: 'Time' },
                ],
            },
        ],
    },
    'Chick Placement': {
        mode: 'single',
        loader: loadChickPlacementRecords,
        sections: [
            {
                columns: [
                    { key: 'performed_by', label: 'Performed By' },
                    { key: 'batch_code', label: 'Batch' },
                    { key: 'house_number', label: 'House<br>Number' },
                    { key: 'pen_name', label: 'Pen' },
                    { key: 'initial_population', label: 'Initial<br>Population' },
                    { key: 'started_at', label: 'Started At' },
                    { key: 'status', label: 'Status' },
                ],
            },
        ],
    },
};

const farmRecordFilterSelect = document.getElementById('farmRecordTypeFilter');
const farmRecordHouseFilter = document.getElementById('farmRecordHouseFilter');
const farmRecordFlockmanFilter = document.getElementById('farmRecordFlockmanFilter');
const farmRecordsFilterForm = document.getElementById('farmRecordsFilterForm');
const farmRecordContent = document.getElementById('farmRecordContent');

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

function formatDate(value) {
    if (!value) return '--';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleDateString();
}

function formatTime(value) {
    if (!value) return '--';
    
    // If it's already a time string (HH:MM:SS), return as-is
    if (typeof value === 'string' && /^\d{2}:\d{2}/.test(value)) {
        return value.slice(0, 5); // Return HH:MM format
    }
    
    // If it's a Date object
    if (value instanceof Date) {
        return value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
    }
    
    return String(value);
}

function formatBoolean(value) {
    if (value === true || value === 1 || value === '1' || value === 'true') {
        return 'Yes';
    }
    if (value === false || value === 0 || value === '0' || value === 'false') {
        return 'No';
    }
    return '--';
}

function formatNumber(value) {
    const number = Number(value ?? 0);

    if (Number.isNaN(number)) return '0';

    return Number.isInteger(number) ? String(number) : number.toFixed(2);
}

function formatRecordCount(count) {
    return `${count} ${count === 1 ? 'record' : 'records'}`;
}

function getInventoryMovement(row) {
    const added = Number(row.added ?? 0);
    const deducted = Number(row.deducted ?? 0);

    if (added > 0) {
        return {
            movement: 'Added Stock',
            quantity: `+${formatNumber(added)}`,
            transaction_date: formatDate(row.recent_purchase_date),
        };
    }

    if (deducted > 0) {
        return {
            movement: 'Reduced Stock',
            quantity: `-${formatNumber(deducted)}`,
            transaction_date: formatDate(row.reduced_date),
        };
    }

    return {
        movement: 'Initial Stock',
        quantity: formatNumber(row.initial_stock),
        transaction_date: formatDate(row.initial_purchase_date || row.monitoring_date),
    };
}

function renderRecordTable(columns, rows) {
    if (!rows || !rows.length) {
        return '<div class="reports-empty">No data available.</div>';
    }

    const headHtml = columns.map((column) => `<th>${column.label}</th>`).join('');
    const bodyHtml = rows.map((row) => {
        const cells = columns.map((column) => {
            const value = row[column.key];

            if (column.key === 'photo_url' && value) {
                return `<td><a href="${escapeHtml(value)}" target="_blank" rel="noopener noreferrer">photo.jpg</a></td>`;
            }

            return `<td>${escapeHtml(value)}</td>`;
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

function renderFarmActivityPaginationControls(cardKey, totalRows) {
    const totalPages = Math.max(1, Math.ceil(totalRows / FARM_ACTIVITY_ROWS_PER_PAGE));
    const shouldHide = totalRows <= FARM_ACTIVITY_ROWS_PER_PAGE;
    const currentPage = farmActivityPagination[cardKey]?.currentPage || 0;
    const visiblePages = getVisibleFarmActivityPages(totalPages, currentPage);

    const dotsHtml = visiblePages.map((index) => `
        <button
            type="button"
            class="reports-page-dot ${index === currentPage ? 'active' : ''}"
            data-farm-activity-page="${index}"
            data-card-key="${cardKey}"
            aria-label="Go to page ${index + 1}"
        ></button>
    `).join('');

    return `
        <div class="reports-pagination ${shouldHide ? 'is-hidden' : ''}" data-farm-activity-pagination="${cardKey}">
            <button type="button" class="reports-page-btn" data-farm-activity-prev="${cardKey}">
                Previous
            </button>
            <div class="reports-page-dots" data-page-direction="still" data-farm-activity-dots="${cardKey}">
                ${dotsHtml}
            </div>
            <button type="button" class="reports-page-btn" data-farm-activity-next="${cardKey}">
                Next
            </button>
        </div>
    `;
}

function getVisibleFarmActivityPages(totalPages, currentPage) {
    if (totalPages <= FARM_ACTIVITY_DOT_LIMIT) {
        return Array.from({ length: totalPages }, (_, index) => index);
    }

    const centerOffset = Math.floor(FARM_ACTIVITY_DOT_LIMIT / 2);
    let start = Math.max(0, currentPage - centerOffset);
    let end = start + FARM_ACTIVITY_DOT_LIMIT;

    if (end > totalPages) {
        end = totalPages;
        start = Math.max(0, end - FARM_ACTIVITY_DOT_LIMIT);
    }

    return Array.from({ length: end - start }, (_, index) => start + index);
}

function updateFarmActivityCard(cardKey, rows) {
    const totalRows = rows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / FARM_ACTIVITY_ROWS_PER_PAGE));

    if (!farmActivityPagination[cardKey]) {
        farmActivityPagination[cardKey] = { currentPage: 0 };
    }

    farmActivityPagination[cardKey].totalRows = totalRows;
    currentFarmActivityData[cardKey] = rows;

    const start = farmActivityPagination[cardKey].currentPage * FARM_ACTIVITY_ROWS_PER_PAGE;
    const paginatedRows = rows.slice(start, start + FARM_ACTIVITY_ROWS_PER_PAGE);

    return paginatedRows;
}

function updateFarmActivityPagination(cardKey, totalRows) {
    const pagination = document.querySelector(`[data-farm-activity-pagination="${cardKey}"]`);
    const prevButton = document.querySelector(`[data-farm-activity-prev="${cardKey}"]`);
    const nextButton = document.querySelector(`[data-farm-activity-next="${cardKey}"]`);
    const dotsContainer = document.querySelector(`[data-farm-activity-dots="${cardKey}"]`);
    const totalPages = Math.max(1, Math.ceil(totalRows / FARM_ACTIVITY_ROWS_PER_PAGE));
    const currentPage = farmActivityPagination[cardKey]?.currentPage || 0;
    const lastPage = farmActivityLastPages[cardKey] ?? currentPage;
    const direction = currentPage > lastPage ? 'next' : currentPage < lastPage ? 'prev' : 'still';
    const visiblePages = getVisibleFarmActivityPages(totalPages, currentPage);

    if (pagination) {
        pagination.classList.toggle('is-hidden', totalRows <= FARM_ACTIVITY_ROWS_PER_PAGE);
    }

    if (prevButton) {
        prevButton.disabled = currentPage === 0;
    }

    if (nextButton) {
        nextButton.disabled = currentPage >= totalPages - 1;
    }

    if (dotsContainer) {
        dotsContainer.dataset.pageDirection = direction;
        dotsContainer.innerHTML = visiblePages.map((index) => `
            <button
                type="button"
                class="reports-page-dot ${index === currentPage ? 'active' : ''}"
                data-farm-activity-page="${index}"
                data-card-key="${cardKey}"
                aria-label="Go to page ${index + 1}"
            ></button>
        `).join('');
    }

    farmActivityLastPages[cardKey] = currentPage;
}

function reRenderFarmActivityCard(cardKey) {
    const recordType = farmRecordState.selectedRecord;
    const config = FARM_RECORD_CONFIG[recordType];
    const section = config.sections[0];
    const allRows = currentFarmActivityData[cardKey] || [];

    const start = farmActivityPagination[cardKey].currentPage * FARM_ACTIVITY_ROWS_PER_PAGE;
    const paginatedRows = allRows.slice(start, start + FARM_ACTIVITY_ROWS_PER_PAGE);

    const tableHtml = renderRecordTable(section.columns, paginatedRows);
    const paginationHtml = renderFarmActivityPaginationControls(cardKey, allRows.length);

    farmRecordContent.innerHTML = renderFarmActivityCardShell(
        recordType,
        formatRecordCount(allRows.length),
        `
            ${tableHtml}
            ${paginationHtml}
        `
    );

    updateFarmActivityPagination(cardKey, allRows.length);
}

function setupFarmActivityPagination() {
    farmRecordContent.addEventListener('click', (event) => {
        const prevBtn = event.target.closest('[data-farm-activity-prev]');
        const nextBtn = event.target.closest('[data-farm-activity-next]');
        const dotBtn = event.target.closest('[data-farm-activity-page]');

        if (prevBtn) {
            const cardKey = prevBtn.getAttribute('data-farm-activity-prev');
            const totalPages = Math.max(1, Math.ceil((farmActivityPagination[cardKey]?.totalRows || 0) / FARM_ACTIVITY_ROWS_PER_PAGE));
            if (farmActivityPagination[cardKey].currentPage > 0) {
                farmActivityPagination[cardKey].currentPage--;
                reRenderFarmActivityCard(cardKey);
            }
        }

        if (nextBtn) {
            const cardKey = nextBtn.getAttribute('data-farm-activity-next');
            const totalPages = Math.max(1, Math.ceil((farmActivityPagination[cardKey]?.totalRows || 0) / FARM_ACTIVITY_ROWS_PER_PAGE));
            if (farmActivityPagination[cardKey].currentPage < totalPages - 1) {
                farmActivityPagination[cardKey].currentPage++;
                reRenderFarmActivityCard(cardKey);
            }
        }

        if (dotBtn) {
            const cardKey = dotBtn.getAttribute('data-card-key');
            const pageNum = parseInt(dotBtn.getAttribute('data-farm-activity-page'), 10);
            farmActivityPagination[cardKey].currentPage = pageNum;
            reRenderFarmActivityCard(cardKey);
        }
    });
}

function renderSingleRecord(recordType, recordData) {
    const section = FARM_RECORD_CONFIG[recordType].sections[0];
    const rows = Array.isArray(recordData) ? recordData : [];
    const cardKey = 'farm-activity-main';

    const paginatedRows = updateFarmActivityCard(cardKey, rows);
    const tableHtml = renderRecordTable(section.columns, paginatedRows);
    const paginationHtml = renderFarmActivityPaginationControls(cardKey, rows.length);

    farmRecordContent.innerHTML = renderFarmActivityCardShell(
        recordType,
        formatRecordCount(rows.length),
        `
            ${tableHtml}
            ${paginationHtml}
        `
    );

    updateFarmActivityPagination(cardKey, rows.length);
}

function renderFarmActivityCardShell(title, countText, contentHtml) {
    return `
        <section class="reports-card farm-record-card">
            <div class="farm-record-card-header">
                <div>
                    <h2>${escapeHtml(title)}</h2>
                </div>
                <div class="farm-record-card-badges">
                    <span class="farm-record-count">${escapeHtml(countText)}</span>
                </div>
            </div>
            <div class="farm-record-card-body">
                ${contentHtml}
            </div>
        </section>
    `;
}

function renderMultiRecord(recordType, recordData) {
    const sectionsHtml = FARM_RECORD_CONFIG[recordType].sections.map((section) => {
        const rows = Array.isArray(recordData?.[section.key]) ? recordData[section.key] : [];

        return `
            <section class="reports-section-block">
                <h3 class="reports-section-title">${section.title}</h3>
                <div class="reports-card farm-record-card">
                    <div class="farm-record-card-header">
                        <div>
                            <h2>${escapeHtml(section.title)}</h2>
                        </div>
                        <div class="farm-record-card-badges">
                            <span class="farm-record-count">${escapeHtml(formatRecordCount(rows.length))}</span>
                        </div>
                    </div>
                    ${renderRecordTable(section.columns, rows)}
                </div>
            </section>
        `;
    }).join('');

    farmRecordContent.innerHTML = `<div class="reports-sections-scroll">${sectionsHtml}</div>`;
}

function renderCurrentRecord() {
    const recordType = farmRecordState.selectedRecord;
    const config = FARM_RECORD_CONFIG[recordType];
    const recordData = farmRecordState.records[recordType];

    if (!config || !farmRecordContent) return;

    if (config.mode === 'multi') {
        renderMultiRecord(recordType, recordData);
        return;
    }

    renderSingleRecord(recordType, recordData);
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

function populateFarmActivitySelect(selectElement, options, placeholder) {
    if (!selectElement) return;

    const currentValue = selectElement.value;
    const optionHtml = (Array.isArray(options) ? options : []).map((option) => {
        const value = option.id ?? '';
        const label = option.name ?? option.number ?? value;

        return `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`;
    }).join('');

    selectElement.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>${optionHtml}`;

    if ([...selectElement.options].some((option) => option.value === currentValue)) {
        selectElement.value = currentValue;
    }
}

async function loadFarmActivityFilterOptions() {
    try {
        const result = await fetchJson('/api/manager/farm-activity/filter-options');

        populateFarmActivitySelect(farmRecordHouseFilter, result.houses, 'All Houses');
        populateFarmActivitySelect(farmRecordFlockmanFilter, result.flockmen, 'All Flockmen');
    } catch (error) {
        console.error('Failed to load farm activity filter options:', error);
    }
}

function buildFarmActivityUrl(path) {
    const params = new URLSearchParams();

    if (farmRecordsFilterForm) {
        const fields = new FormData(farmRecordsFilterForm);

        fields.forEach((value, key) => {
            if (key === 'activity_type') return;

            if (String(value || '').trim()) {
                params.append(key, value);
            }
        });
    }

    return params.toString() ? `${path}?${params.toString()}` : path;
}

async function loadHatchMortalityRecords() {
    const result = await fetchJson(buildFarmActivityUrl('/api/manager/farm-activity/pens'));

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house_number),
        eggs_hatched: record.eggs_hatched ?? 0,
        mortality: record.mortality ?? 0,
        recorded_at: formatDate(record.recorded_at),
    }));
}

async function loadWeightMonitoringRecords() {
    const result = await fetchJson(buildFarmActivityUrl('/api/manager/farm-activity/weight-sampling-logs'));

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        date: formatDate(record.date),
    }));
}

async function loadFeedReplenishmentRecords() {
    const result = await fetchJson(buildFarmActivityUrl('/api/manager/farm-activity/feed-refill-records'));

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house_number),
        recorded_at: formatDate(record.recorded_at),
    }));
}

async function loadVitaminSupplementationRecords() {
    const result = await fetchJson(buildFarmActivityUrl('/api/manager/farm-activity/vitamin-refill-records'));

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house_number),
        recorded_at: formatDate(record.recorded_at),
    }));
}

async function loadSensorMaintenanceRecords() {
    const result = await fetchJson(buildFarmActivityUrl('/api/manager/farm-activity/sensor-inspection-logs'));

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house_number),
        pen_name: record.pen_name || '--',
        sensor_present: formatBoolean(record.sensor_present),
        sensor_clean_unblocked: formatBoolean(record.sensor_clean_unblocked),
        no_visible_damage_or_loose_wiring: formatBoolean(record.no_visible_damage_or_loose_wiring),
        power_status_on: formatBoolean(record.power_status_on),
        placement_secure: formatBoolean(record.placement_secure),
        date: formatDate(record.date),
        time: formatTime(record.time),
        performed_by: record.performed_by || '--',
    }));
}

async function loadBiosecurityRecords() {
    const result = await fetchJson(buildFarmActivityUrl('/api/manager/farm-activity/cleaning-logs'));
    return Array.isArray(result.records) ? result.records : [];
}

async function loadPenDisinfectionRecords() {
    const cleaningLogs = await loadBiosecurityRecords();

    return cleaningLogs.filter((record) => {
        const activity = String(record.activity || '').trim();
        return activity === 'Pen Disinfection';
    }).map((record) => ({
        ...record,
        house: record.house || '--',
        pen: record.pen || '--',
        performed_by: record.performed_by || '--',
        date: formatDate(record.date),
        time: formatTime(record.time),
    }));
}

async function loadPenCleaningRecords() {
    const cleaningLogs = await loadBiosecurityRecords();

    return cleaningLogs.filter((record) => {
        const activity = String(record.activity || '').trim();
        return activity === 'Pen Cleaning';
    }).map((record) => ({
        ...record,
        house: record.house || '--',
        pen: record.pen || '--',
        performed_by: record.performed_by || '--',
        date: formatDate(record.date),
        time: formatTime(record.time),
    }));
}

async function loadChickPlacementRecords() {
    const result = await fetchJson(buildFarmActivityUrl('/api/manager/farm-activity/flock-batches'));

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house_number),
        initial_population: formatNumber(record.initial_population),
        started_at: formatDate(record.started_at),
    }));
}

function formatHouseNumber(value) {
    if (!value) return '--';
    return String(value);
}

function renderLoading() {
    if (!farmRecordContent) return;

    farmRecordContent.innerHTML = `
        <section class="reports-card">
            <div class="reports-empty">Loading records...</div>
        </section>
    `;
}

async function loadCurrentRecord() {
    const recordType = farmRecordState.selectedRecord;
    const config = FARM_RECORD_CONFIG[recordType];

    if (!config) return;

    renderLoading();

    try {
        farmRecordState.records[recordType] = await config.loader();
        renderCurrentRecord();
    } catch (error) {
        console.error('Failed to load farm activity records:', error);

        farmRecordContent.innerHTML = `
            <section class="reports-card">
                <div class="reports-empty">Unable to load records.</div>
            </section>
        `;
    }
}

function getFarmActivityExportFilters() {
    const recordType = farmRecordState.selectedRecord;
    const fromDate = document.getElementById('farmRecordsFromDate')?.value || 'All Dates';
    const toDate = document.getElementById('farmRecordsToDate')?.value || 'All Dates';
    const house = document.getElementById('farmRecordHouseFilter')?.selectedOptions?.[0]?.textContent || 'All Houses';
    const flockman = document.getElementById('farmRecordFlockmanFilter')?.selectedOptions?.[0]?.textContent || 'All Flockmen';

    return {
        recordType,
        fromDate,
        toDate,
        house,
        flockman,
    };
}

function generateFarmActivityCsvExport() {
    const recordType = farmRecordState.selectedRecord;
    const rows = currentFarmActivityData['farm-activity-main'] || [];
    const config = FARM_RECORD_CONFIG[recordType];
    const columns = config?.sections?.[0]?.columns || [];
    const filters = getFarmActivityExportFilters();

    let csvContent = 'data:text/csv;charset=utf-8,';
    csvContent += 'Farm Activity Records\n';
    csvContent += `Generated: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}\n`;
    csvContent += `Activity Type: ${filters.recordType}\n`;
    csvContent += `From Date: ${filters.fromDate}\n`;
    csvContent += `To Date: ${filters.toDate}\n`;
    csvContent += `House: ${filters.house}\n`;
    csvContent += `Flockman: ${filters.flockman}\n\n`;

    const headers = columns.map((column) => `"${String(column.label).replace(/<br>/g, ' ')}"`).join(',');
    csvContent += headers + '\n';

    rows.forEach((row) => {
        const values = columns.map((column) => {
            const value = row[column.key] ?? '';
            const stringValue = String(value).replace(/"/g, '""');
            return `"${stringValue}"`;
        }).join(',');
        csvContent += values + '\n';
    });

    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', `farm-activity-${recordType.toLowerCase().replace(/[^a-z0-9]+/g, '-')}-${new Date().getTime()}.csv`);
    link.click();
}

async function generateFarmActivityPdfExport() {
    const recordType = farmRecordState.selectedRecord;
    const rows = currentFarmActivityData['farm-activity-main'] || [];
    const config = FARM_RECORD_CONFIG[recordType];
    const columns = config?.sections?.[0]?.columns || [];
    const filters = getFarmActivityExportFilters();
    const exporter = await getExporterInfo();
    const printWindow = window.open('', '', 'width=900,height=700');

    if (!printWindow) return;

    let htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Farm Activity Records</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
                .header { margin-bottom: 30px; border-bottom: 2px solid #2f7446; padding-bottom: 20px; }
                .header h1 { font-size: 24px; color: #000; margin-bottom: 10px; }
                .header-info { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px; color: #666; }
                .section { margin-bottom: 30px; page-break-inside: avoid; }
                .section-title { font-size: 16px; font-weight: bold; color: #2f7446; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 1px solid #ddd; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
                th { background: #f0f0f0; padding: 10px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
                td { padding: 8px; border-bottom: 1px solid #eee; }
                tr:nth-child(even) { background: #f9f9f9; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 11px; color: #999; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📋 Farm Activity Records</h1>
                <div class="header-info">
                    <div><strong>Generated:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</div>
                    <div><strong>Exported By:</strong> ${escapeHtml(exporter.name)}</div>
                    <div><strong>Role:</strong> ${escapeHtml(exporter.role)}</div>
                    <div><strong>Activity Type:</strong> ${escapeHtml(filters.recordType)}</div>
                    <div><strong>From Date:</strong> ${escapeHtml(filters.fromDate)}</div>
                    <div><strong>To Date:</strong> ${escapeHtml(filters.toDate)}</div>
                    <div><strong>House:</strong> ${escapeHtml(filters.house)}</div>
                    <div><strong>Flockman:</strong> ${escapeHtml(filters.flockman)}</div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">${escapeHtml(recordType)}</div>
                <table>
                    <thead>
                        <tr>
                            ${columns.map((column) => `<th>${String(column.label).replace(/<br>/g, ' ')}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${rows.map((row) => `
                            <tr>
                                ${columns.map((column) => `<td>${escapeHtml(row[column.key] ?? '')}</td>`).join('')}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>

            <div class="footer">
                <p>This export was automatically generated by the Farm Management System.</p>
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(htmlContent);
    printWindow.document.close();
    printWindow.print();
}

function setupFarmActivityExportModal() {
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

    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);

    pdfBtn?.addEventListener('click', () => {
        pdfBtn.classList.add('active');
        csvBtn?.classList.remove('active');

        setTimeout(async () => {
            await generateFarmActivityPdfExport();
            closeModal();
        }, 140);
    });

    csvBtn?.addEventListener('click', () => {
        csvBtn.classList.add('active');
        pdfBtn?.classList.remove('active');

        setTimeout(() => {
            generateFarmActivityCsvExport();
            closeModal();
        }, 140);
    });

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

function bindFarmRecordEvents() {
    farmRecordFilterSelect?.addEventListener('change', (event) => {
        farmRecordState.selectedRecord = event.target.value;
        farmActivityPagination = {};
        currentFarmActivityData = {};
        loadCurrentRecord();
    });

    farmRecordsFilterForm?.querySelectorAll('select, input').forEach((field) => {
        field.addEventListener('change', () => {
            farmActivityPagination = {};
            currentFarmActivityData = {};
            loadCurrentRecord();
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindFarmRecordEvents();
    setupFarmActivityPagination();
    setupFarmActivityExportModal();
    loadFarmActivityFilterOptions();
    loadCurrentRecord();
});
