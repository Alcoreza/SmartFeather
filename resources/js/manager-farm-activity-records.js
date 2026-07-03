const FARM_ACTIVITY_ROWS_PER_PAGE = 8;
const FARM_ACTIVITY_DOT_LIMIT = 5;

const farmRecordState = {
    selectedRecord: 'Hatch and Mortality Check',
    records: {},
};

let farmActivityPagination = {};
let currentFarmActivityData = {};
let farmActivityLastPages = {};

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
const farmRecordContent = document.getElementById('farmRecordContent');

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
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

async function loadHatchMortalityRecords() {
    const result = await fetchJson('/api/manager/farm-activity/pens');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house_number),
        eggs_hatched: record.eggs_hatched ?? 0,
        mortality: record.mortality ?? 0,
        recorded_at: formatDate(record.recorded_at),
    }));
}

async function loadWeightMonitoringRecords() {
    const result = await fetchJson('/api/manager/farm-activity/weight-sampling-logs');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        date: formatDate(record.date),
    }));
}

async function loadFeedReplenishmentRecords() {
    const result = await fetchJson('/api/manager/farm-activity/feed-refill-records');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house_number),
        recorded_at: formatDate(record.recorded_at),
    }));
}

async function loadVitaminSupplementationRecords() {
    const result = await fetchJson('/api/manager/farm-activity/vitamin-refill-records');

    return (Array.isArray(result.records) ? result.records : []).map((record) => ({
        ...record,
        house_number: formatHouseNumber(record.house_number),
        recorded_at: formatDate(record.recorded_at),
    }));
}

async function loadSensorMaintenanceRecords() {
    const result = await fetchJson('/api/manager/farm-activity/sensor-inspection-logs');

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
    const result = await fetchJson('/api/manager/farm-activity/cleaning-logs');
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
    const result = await fetchJson('/api/manager/farm-activity/flock-batches');

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

function bindFarmRecordEvents() {
    if (!farmRecordFilterSelect) return;

    farmRecordFilterSelect.addEventListener('change', (event) => {
        farmRecordState.selectedRecord = event.target.value;
        farmActivityPagination = {};
        currentFarmActivityData = {};
        loadCurrentRecord();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindFarmRecordEvents();
    setupFarmActivityPagination();
    loadCurrentRecord();
});
