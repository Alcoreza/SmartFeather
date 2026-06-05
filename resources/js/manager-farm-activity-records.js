const farmRecordState = {
    selectedRecord: 'Hatch and Mortality Check',
    records: {},
};

const FARM_RECORD_CONFIG = {
    'Hatch and Mortality Check': {
        mode: 'single',
        loader: loadHatchMortalityRecords,
        sections: [
            {
                columns: [
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
                    { key: 'house', label: 'House' },
                    { key: 'pen', label: 'Pen' },
                    { key: 'disinfectant_used', label: 'Disinfectant<br>Used' },
                    { key: 'performed_by', label: 'Performed By' },
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
                    { key: 'house', label: 'House' },
                    { key: 'pen', label: 'Pen' },
                    { key: 'performed_by', label: 'Performed By' },
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
                    { key: 'house_number', label: 'House' },
                    { key: 'pen_name', label: 'Pen' },
                    { key: 'sensor_present', label: 'Sensor<br>Present' },
                    { key: 'sensor_clean_unblocked', label: 'Sensor<br>Clean' },
                    { key: 'no_visible_damage_or_loose_wiring', label: 'No<br>Damage' },
                    { key: 'power_status_on', label: 'Power<br>Status' },
                    { key: 'placement_secure', label: 'Placement<br>Secure' },
                    { key: 'date', label: 'Date' },
                    { key: 'time', label: 'Time' },
                    { key: 'performed_by', label: 'Performed By' },
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

function renderSingleRecord(recordType, recordData) {
    const section = FARM_RECORD_CONFIG[recordType].sections[0];
    const rows = Array.isArray(recordData) ? recordData : [];

    farmRecordContent.innerHTML = `
        <section class="reports-card">
            ${renderRecordTable(section.columns, rows)}
        </section>
    `;
}

function renderMultiRecord(recordType, recordData) {
    const sectionsHtml = FARM_RECORD_CONFIG[recordType].sections.map((section) => {
        const rows = Array.isArray(recordData?.[section.key]) ? recordData[section.key] : [];

        return `
            <section class="reports-section-block">
                <h3 class="reports-section-title">${section.title}</h3>
                <div class="reports-card">
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
    return String(value).startsWith('House ') ? value : `House ${value}`;
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
        loadCurrentRecord();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindFarmRecordEvents();
    loadCurrentRecord();
});
