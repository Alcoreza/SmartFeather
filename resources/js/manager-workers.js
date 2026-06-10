const BASE_URL = '/api/admin/workers';
const WORKERS_ROWS_PER_PAGE = 7;

let workersCache = [];
let workersCurrentPage = 0;
let workersCurrentRole = 'All';

// ================= PROFILE MODAL =================
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
        // Optionally show error
    }
}

function setupProfileModal() {
    const modal = document.getElementById('profileModal');
    const openBtn = document.getElementById('openProfileModal');
    const closeBtn = document.getElementById('closeProfileModal');

    if (openBtn && modal) {
        openBtn.addEventListener('click', async () => {
            await populateProfileModal();
            modal.classList.add('show');
        });
    }

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => {
            modal.classList.remove('show');
        });
    }

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
}

// ================= LOAD EMPLOYEES =================
async function loadEmployees() {
    try {
        const res = await fetch(BASE_URL);
        if (!res.ok) throw new Error('Failed to fetch');

        workersCache = await res.json();
        workersCurrentPage = 0;
        renderWorkersTable();

    } catch (err) {
        console.error(err);
        alert('Failed to load employees');
    }
}

function getFilteredWorkers() {
    return workersCache.filter(user => {
        return workersCurrentRole === 'All' || user.Role === workersCurrentRole;
    });
}

function renderWorkersTable() {
    const table = document.getElementById('workersTableBody');
    if (!table) return;

    const filteredWorkers = getFilteredWorkers();
    const totalPages = Math.max(1, Math.ceil(filteredWorkers.length / WORKERS_ROWS_PER_PAGE));
    workersCurrentPage = Math.min(workersCurrentPage, totalPages - 1);

    if (!filteredWorkers.length) {
        table.innerHTML = `
            <tr>
                <td colspan="4" class="workers-empty-row">No employees match the selected filter.</td>
            </tr>
            ${Array.from({ length: WORKERS_ROWS_PER_PAGE - 1 }, () => `
                <tr class="workers-placeholder-row" aria-hidden="true">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            `).join('')}
        `;
        updateWorkersPagination(0);
        return;
    }

    const start = workersCurrentPage * WORKERS_ROWS_PER_PAGE;
    const pageWorkers = filteredWorkers.slice(start, start + WORKERS_ROWS_PER_PAGE);
    const placeholderRows = WORKERS_ROWS_PER_PAGE - pageWorkers.length;

    table.innerHTML = pageWorkers.map(user => {
        const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();

        return `
            <tr data-id="${user.EmployeeId}">
                <td>${fullName}</td>
                <td>${user.EmployeeId}</td>
                <td>${user.Role}</td>
                <td class="text-center">
                    <button class="view-worker-btn icon-btn" type="button" data-id="${user.EmployeeId}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    }).join('') + Array.from({ length: placeholderRows }, () => `
        <tr class="workers-placeholder-row" aria-hidden="true">
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    `).join('');

    updateWorkersPagination(filteredWorkers.length);
}

function updateWorkersPagination(totalRows) {
    const pagination = document.querySelector('[data-workers-pagination]');
    const prevButton = document.querySelector('[data-workers-prev]');
    const nextButton = document.querySelector('[data-workers-next]');
    const dots = document.querySelector('[data-workers-dots]');
    const totalPages = Math.max(1, Math.ceil(totalRows / WORKERS_ROWS_PER_PAGE));

    if (pagination) {
        pagination.classList.toggle('is-hidden', totalRows <= WORKERS_ROWS_PER_PAGE);
    }

    if (prevButton) {
        prevButton.disabled = workersCurrentPage === 0;
    }

    if (nextButton) {
        nextButton.disabled = workersCurrentPage >= totalPages - 1;
    }

    if (dots) {
        dots.innerHTML = Array.from({ length: totalPages }, (_, index) => `
            <button
                type="button"
                class="workers-page-dot ${index === workersCurrentPage ? 'active' : ''}"
                data-workers-page="${index}"
                aria-label="Go to page ${index + 1}"
                aria-current="${index === workersCurrentPage ? 'page' : 'false'}"
            ></button>
        `).join('');
    }
}

function setupWorkersPagination() {
    document.querySelector('[data-workers-prev]')?.addEventListener('click', () => {
        workersCurrentPage = Math.max(0, workersCurrentPage - 1);
        renderWorkersTable();
    });

    document.querySelector('[data-workers-next]')?.addEventListener('click', () => {
        workersCurrentPage += 1;
        renderWorkersTable();
    });

    document.querySelector('[data-workers-dots]')?.addEventListener('click', event => {
        const dot = event.target.closest('[data-workers-page]');
        if (!dot) return;

        workersCurrentPage = Number(dot.dataset.workersPage || 0);
        renderWorkersTable();
    });
}

// ================= VIEW MODAL =================
async function openViewModal(id) {
    try {
        const res = await fetch(`${BASE_URL}/${id}`);
        const user = await res.json();

        document.getElementById('workerFirstName').value = user.FirstName;
        document.getElementById('workerMiddleName').value = user.MiddleName ?? '';
        document.getElementById('workerLastName').value = user.LastName;
        document.getElementById('workerSuffix').value = user.Suffix ?? '';
        document.getElementById('workerUsername').value = user.Username ?? '';
        document.getElementById('workerRole').value = user.Role;
        document.getElementById('workerPhone').value = user.PhoneNumber ?? '';
        document.getElementById('workerId').value = user.EmployeeId;
        document.getElementById('workerBirthday').value = user.Birthday ?? '';
        document.getElementById('workerGender').value = user.Gender ?? '';
        document.getElementById('workerAddress').value = user.Address ?? '';

        // ✅ FIXED: use class instead of display
        document.getElementById('workerModal').classList.add('active');

    } catch (err) {
        console.error(err);
        alert('Failed to load employee');
    }
}

// ================= EVENT LISTENER =================
document.addEventListener('click', e => {
    const btn = e.target.closest('.view-worker-btn');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    openViewModal(id);
});

// ================= CLOSE MODAL =================
document.getElementById('closeWorkerModal')?.addEventListener('click', () => {
    document.getElementById('workerModal').classList.remove('active');
});

// ================= CLICK OUTSIDE TO CLOSE =================
document.getElementById('workerModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'workerModal') {
        e.currentTarget.classList.remove('active');
    }
});

// ================= ESC KEY CLOSE =================
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.getElementById('workerModal')?.classList.remove('active');
    }
});

// ================= ROLE FILTER =================
document.getElementById('roleFilter')?.addEventListener('change', e => {
    workersCurrentRole = e.target.value;
    workersCurrentPage = 0;
    renderWorkersTable();
});

// ================= INITIAL LOAD =================
document.addEventListener('DOMContentLoaded', () => {
    setupWorkersPagination();
    loadEmployees();
    setupProfileModal();
});
