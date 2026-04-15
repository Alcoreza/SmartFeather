const reportState = {
    selectedReport: 'Population',
    reports: {},
};

const REPORT_CONFIG = {
    'Population': {
        mode: 'single',
        sections: [
            {
                title: '',
                columns: [
                    { key: 'batch_id', label: 'Batch ID' },
                    { key: 'start_date', label: 'Start Date' },
                    { key: 'end_date', label: 'End Date' },
                    { key: 'reporting_date', label: 'Reporting Date' },
                    { key: 'pen_no', label: 'Pen No.' },
                    { key: 'initial_population', label: 'Initial<br>Population' },
                    { key: 'running_population', label: 'Running<br>Population' },
                    { key: 'mortalities', label: 'Mortalities' },
                    { key: 'eggs_hatched', label: 'Eggs<br>Hatched' },
                ],
            },
        ],
    },

    'Environmental': {
        mode: 'multi',
        sections: [
            {
                title: 'Temperature',
                key: 'temperature',
                columns: [
                    { key: 'batch', label: 'Batch' },
                    { key: 'date', label: 'Date' },
                    { key: 'lowest_reading', label: 'Lowest<br>Reading' },
                    { key: 'highest_reading', label: 'Highest<br>Reading' },
                    { key: 'average_reading', label: 'Average<br>Reading' },
                    { key: 'threshold_violations', label: 'Threshold<br>Violations' },
                    { key: 'house', label: 'House' },
                ],
            },
            {
                title: 'Ammonia',
                key: 'ammonia',
                columns: [
                    { key: 'batch', label: 'Batch' },
                    { key: 'date', label: 'Date' },
                    { key: 'lowest_reading', label: 'Lowest<br>Reading' },
                    { key: 'highest_reading', label: 'Highest<br>Reading' },
                    { key: 'average_reading', label: 'Average<br>Reading' },
                    { key: 'threshold_violations', label: 'Threshold<br>Violations' },
                    { key: 'house', label: 'House' },
                ],
            },
            {
                title: 'Feeds',
                key: 'feeds',
                columns: [
                    { key: 'batch', label: 'Batch' },
                    { key: 'date', label: 'Date' },
                    { key: 'feeds_level', label: 'Feeds<br>Level' },
                    { key: 'house', label: 'House' },
                    { key: 'pen', label: 'Pen' },
                    { key: 'feeder_number', label: 'Feeder<br>Number' },
                ],
            },
            {
                title: 'Water',
                key: 'water',
                columns: [
                    { key: 'batch', label: 'Batch' },
                    { key: 'date', label: 'Date' },
                    { key: 'water_level', label: 'Water<br>Level' },
                    { key: 'house', label: 'House' },
                    { key: 'pen', label: 'Pen' },
                    { key: 'drinker_number', label: 'Drinker<br>Number' },
                ],
            },
        ],
    },

    'Inventory': {
        mode: 'multi',
        sections: [
            {
                title: 'Feeds',
                key: 'feeds',
                columns: [
                    { key: 'purchase_date', label: 'Purchase<br>Date' },
                    { key: 'date_of_monitoring', label: 'Date of<br>Monitoring' },
                    { key: 'initial_stock', label: 'Initial<br>Stock (kg)' },
                    { key: 'remaining_stock', label: 'Remaining<br>Stock (kg)' },
                ],
            },
            {
                title: 'Vitamins E',
                key: 'vitamins_e',
                columns: [
                    { key: 'purchase_date', label: 'Purchase<br>Date' },
                    { key: 'date_of_monitoring', label: 'Date of<br>Monitoring' },
                    { key: 'type', label: 'Type' },
                    { key: 'initial_stock', label: 'Initial Stock<br>(mL)' },
                    { key: 'remaining_stock', label: 'Remaining<br>Stock (mL)' },
                ],
            },
            {
                title: 'Vitamins D-3',
                key: 'vitamins_d3',
                columns: [
                    { key: 'purchase_date', label: 'Purchase<br>Date' },
                    { key: 'date_of_monitoring', label: 'Date of<br>Monitoring' },
                    { key: 'type', label: 'Type' },
                    { key: 'initial_stock', label: 'Initial Stock<br>(mL)' },
                    { key: 'remaining_stock', label: 'Remaining<br>Stock (mL)' },
                ],
            },
            {
                title: 'Vitamins B-Complex',
                key: 'vitamins_b_complex',
                columns: [
                    { key: 'purchase_date', label: 'Purchase<br>Date' },
                    { key: 'date_of_monitoring', label: 'Date of<br>Monitoring' },
                    { key: 'type', label: 'Type' },
                    { key: 'initial_stock', label: 'Initial Stock<br>(mL)' },
                    { key: 'remaining_stock', label: 'Remaining<br>Stock (mL)' },
                ],
            },
        ],
    },

    'Biosecurity': {
        mode: 'multi',
        sections: [
            {
                title: 'Cleaning',
                key: 'cleaning',
                columns: [
                    { key: 'house', label: 'House' },
                    { key: 'pen', label: 'Pen' },
                    { key: 'activity', label: 'Activity' },
                    { key: 'date', label: 'Date' },
                    { key: 'time', label: 'Time' },
                    { key: 'disinfectant_used', label: 'Disinfectant<br>Used' },
                    { key: 'performed_by', label: 'Performed<br>by:' },
                ],
            },
            {
                title: 'Personnel Biosecurity Logs',
                key: 'personnel_biosecurity_logs',
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
            },
            {
                title: 'Visitors',
                key: 'visitors',
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
            },
            {
                title: 'Personnel Entry Logs',
                key: 'personnel_entry_logs',
                columns: [
                    { key: 'name', label: 'Name' },
                    { key: 'role', label: 'Role' },
                    { key: 'house', label: 'House' },
                    { key: 'date', label: 'Date' },
                    { key: 'time', label: 'Time' },
                ],
            },
        ],
    },

    'Weight Sampling': {
        mode: 'single',
        sections: [
            {
                title: '',
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
            },
        ],
    },

    'Tasks': {
        mode: 'single',
        sections: [
            {
                title: '',
                columns: [
                    { key: 'name', label: 'Name' },
                    { key: 'task_assigned', label: 'Task<br>Assigned' },
                    { key: 'house_number', label: 'House<br>Number' },
                    { key: 'pen_number', label: 'Pen<br>Number' },
                    { key: 'detailed_task', label: 'Detailed<br>Task' },
                    { key: 'photo', label: 'Photo' },
                    { key: 'priority', label: 'Priority' },
                    { key: 'notes', label: 'Notes' },
                    { key: 'time_assigned', label: 'Time<br>Assigned' },
                    { key: 'finish_by', label: 'Finish By' },
                    { key: 'time_completed', label: 'Time<br>Completed' },
                ],
            },
        ],
    },
};

const reportFilterSelect = document.getElementById('reportTypeFilter');
const reportContent = document.getElementById('reportContent');
const generateReportBtn = document.getElementById('openGenerateReportModal');

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
        return `
            <div class="reports-empty">No data available.</div>
        `;
    }

    const headHtml = columns.map((column) => `<th>${column.label}</th>`).join('');

    const bodyHtml = rows.map((row) => {
        const cells = columns.map((column) => {
            const value = row[column.key];

            if (column.key === 'photo' && value) {
                return `<td><a href="${escapeHtml(value)}" target="_blank" rel="noopener noreferrer">proof.jpg</a></td>`;
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
                <tbody>
                    ${bodyHtml}
                </tbody>
            </table>
        </div>
    `;
}

function renderSingleReport(reportType, reportData) {
    const config = REPORT_CONFIG[reportType];
    const section = config.sections[0];
    const rows = Array.isArray(reportData) ? reportData : [];

    reportContent.innerHTML = `
        <section class="reports-card">
            ${renderReportTable(section.columns, rows)}
        </section>
    `;
}

function renderMultiReport(reportType, reportData) {
    const config = REPORT_CONFIG[reportType];

    const sectionsHtml = config.sections.map((section) => {
        const rows = Array.isArray(reportData?.[section.key]) ? reportData[section.key] : [];

        return `
            <section class="reports-section-block">
                <h3 class="reports-section-title">${section.title}</h3>
                <div class="reports-card">
                    ${renderReportTable(section.columns, rows)}
                </div>
            </section>
        `;
    }).join('');

    reportContent.innerHTML = `
        <div class="reports-sections-scroll">
            ${sectionsHtml}
        </div>
    `;
}

function renderCurrentReport() {
    const reportType = reportState.selectedReport;
    const config = REPORT_CONFIG[reportType];
    const reportData = reportState.reports[reportType];

    if (!config || !reportContent) return;

    if (config.mode === 'multi') {
        renderMultiReport(reportType, reportData);
        return;
    }

    renderSingleReport(reportType, reportData);
}

function bindReportEvents() {
    if (!reportFilterSelect) return;

    reportFilterSelect.addEventListener('change', (event) => {
        reportState.selectedReport = event.target.value;
        renderCurrentReport();
    });
}

function setupGenerateReportModal() {
    const modal = document.getElementById('generateReportModal');
    const closeBtn = document.getElementById('closeGenerateReportModal');

    if (!modal || !generateReportBtn) return;

    generateReportBtn.addEventListener('click', () => {
        modal.classList.add('show');
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.classList.remove('show');
        });
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
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

async function loadReportsData() {
    try {
        const response = await fetch('/api/manager/reports', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const data = await response.json();
        reportState.reports = data.reports || {};

        renderCurrentReport();
    } catch (error) {
        console.error('Failed to load reports:', error);

        if (reportContent) {
            reportContent.innerHTML = `
                <section class="reports-card">
                    <div class="reports-empty">Unable to load reports.</div>
                </section>
            `;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    bindReportEvents();
    setupGenerateReportModal();
    setupProfileModal();
    loadReportsData();
});