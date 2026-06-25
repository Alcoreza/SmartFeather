const BASE_URL = '/api/admin/workers';
const ADMIN_WORKERS_ROWS_PER_PAGE = 7;
const ADMIN_WORKERS_DOT_LIMIT = 5;

let deleteId = null;
const employeesCache = new Map();
let adminWorkersCurrentPage = 0;
let adminWorkersLastPage = 0;
let adminWorkersCurrentRole = 'All';

// ================= MODAL HELPERS =================
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function generateUsername(firstName, lastName) {
    const initial = (firstName || '').trim().charAt(0);
    const surname = (lastName || '').trim();

    return `${initial}${surname}`.toUpperCase();
}

function syncUsernamePreview() {
    const isEditMode = !!document.getElementById('EmployeeId').value;
    const usernameInput = document.getElementById('Username');
    const usernameHint = document.getElementById('usernameHint');

    if (isEditMode) {
        usernameInput.readOnly = false;
        usernameHint.style.display = 'none';
        return;
    }

    usernameInput.readOnly = true;
    usernameHint.style.display = 'block';

    const firstName = document.getElementById('FirstName').value || '';
    const lastName = document.getElementById('LastName').value || '';
    usernameInput.value = generateUsername(firstName, lastName);
}

const passwordInput = document.getElementById('passwordModalPassword');
const confirmPasswordInput = document.getElementById('passwordModalConfirm');
const oldPasswordInput = document.getElementById('passwordModalOldPassword');
const confirmPasswordMessage = document.getElementById('confirmPasswordMessage');
const oldPasswordMessage = document.getElementById('oldPasswordMessage');
const phoneInput = document.getElementById('PhoneNumber');
const birthdayInput = document.getElementById('Birthday');
let pendingPassword = null;

const workerRequiredFields = [
    { id: 'FirstName', label: 'First Name' },
    { id: 'LastName', label: 'Last Name' },
    { id: 'Role', label: 'Role' },
    { id: 'PhoneNumber', label: 'Phone Number' },
    { id: 'Birthday', label: 'Birthday' },
    { id: 'Gender', label: 'Gender' }
];

let shouldTrackRequiredHighlights = false;

function clearConfirmPasswordMessage() {
    confirmPasswordMessage.textContent = '';
}

function clearOldPasswordMessage() {
    oldPasswordMessage.textContent = '';
}

function validateConfirmPassword() {
    const password = passwordInput?.value || '';
    const confirmPassword = confirmPasswordInput?.value || '';

    if (!password && !confirmPassword) {
        clearConfirmPasswordMessage();
        return true;
    }

    if (password !== confirmPassword) {
        confirmPasswordMessage.textContent = 'Passwords do not match.';
        return false;
    }

    clearConfirmPasswordMessage();
    return true;
}

function resetConfirmPasswordState() {
    if (passwordInput) passwordInput.value = '';
    if (confirmPasswordInput) confirmPasswordInput.value = '';
    if (oldPasswordInput) oldPasswordInput.value = '';
    clearConfirmPasswordMessage();
    clearOldPasswordMessage();
}

function isAddMode() {
    return !document.getElementById('EmployeeId').value;
}

function setPasswordModalMode(mode) {
    const editPasswordBtn = document.getElementById('editPasswordBtn');
    const oldPasswordField = document.getElementById('passwordModalOldPasswordField');

    const isEditMode = mode === 'edit';

    if (oldPasswordField) {
        oldPasswordField.style.display = isEditMode ? 'block' : 'none';
    }

    document.getElementById('passwordModalTitle').textContent = isEditMode ? 'Change Password' : 'Set Password';

    if (editPasswordBtn) {
        editPasswordBtn.textContent = isEditMode ? 'Change Password' : 'Set Password';
    }
}

function setAddModeRequirements(isAddMode) {
    if (!isAddMode) {
        resetConfirmPasswordState();
    }
}

function isRequiredFieldEmpty(field) {
    return !(document.getElementById(field.id)?.value || '').trim();
}

function clearRequiredFieldHighlight(input) {
    input?.closest('.admin-worker-field')?.classList.remove('has-error');
}

function syncRequiredFieldHighlight(field) {
    const input = document.getElementById(field.id);

    if (!input) return;

    if (isAddMode() && shouldTrackRequiredHighlights && isRequiredFieldEmpty(field)) {
        input.closest('.admin-worker-field')?.classList.add('has-error');
        return;
    }

    clearRequiredFieldHighlight(input);

    const hasErrors = document.querySelector('#workerForm .admin-worker-field.has-error');
    if (!hasErrors) {
        shouldTrackRequiredHighlights = false;
        clearWorkerFormError();
    }
}

function clearRequiredFieldHighlights() {
    shouldTrackRequiredHighlights = false;

    document
        .querySelectorAll('#workerForm .admin-worker-field.has-error')
        .forEach(field => field.classList.remove('has-error'));
}

function markMissingRequiredFields(missingFields) {
    clearRequiredFieldHighlights();
    shouldTrackRequiredHighlights = true;

    missingFields.forEach(field => {
        document.getElementById(field.id)?.closest('.admin-worker-field')?.classList.add('has-error');
    });
}

function getMissingRequiredFields() {
    return isAddMode()
        ? workerRequiredFields.filter(isRequiredFieldEmpty)
        : [];
}

function showWorkerFormError(message = 'Please fill in the required fields.') {
    const formError = document.getElementById('workerFormError');
    if (!formError) return;

    formError.textContent = message;
    formError.classList.add('show');
    formError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearWorkerFormError() {
    const formError = document.getElementById('workerFormError');

    formError?.classList.remove('show');
    if (formError) {
        formError.textContent = '';
    }
}

function showRequiredFieldsError() {
    const missingFields = getMissingRequiredFields();
    if (!missingFields.length) {
        clearRequiredFieldHighlights();
        clearWorkerFormError();
        return false;
    }

    markMissingRequiredFields(missingFields);

    showWorkerFormError();
    const firstMissingField = document.getElementById(missingFields[0].id);
    setTimeout(() => firstMissingField?.focus(), 350);
    return true;
}

[passwordInput, confirmPasswordInput, oldPasswordInput].forEach(input => {
    input?.addEventListener('input', () => {
        validateConfirmPassword();
        clearOldPasswordMessage();
    });
});

// ================= PROFILE MODAL =================
async function populateAdminProfileModal() {
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

function setupAdminProfileModal() {
    const modal = document.getElementById('adminProfileModal');
    const openBtn = document.getElementById('openAdminProfileModal');
    const closeBtn = document.getElementById('closeAdminProfileModal');

    if (openBtn && modal) {
        openBtn.addEventListener('click', async () => {
            await populateAdminProfileModal();
            modal.style.display = 'flex';
        });
    }

    if (closeBtn && modal) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// ================= LOAD EMPLOYEES =================
async function loadEmployees() {
    try {
        const res = await fetch(BASE_URL, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });

        const data = await res.json();

        const sortedUsers = [...data].sort((a, b) => Number(a.EmployeeId) - Number(b.EmployeeId));

        employeesCache.clear();

        sortedUsers.forEach(user => {
            employeesCache.set(String(user.EmployeeId), user);
        });

        adminWorkersCurrentPage = 0;
        renderWorkersTable();

    } catch (err) {
        console.error(err);
        alert('Failed to load employees');
    }
}

function getFilteredAdminWorkers() {
    const workers = Array.from(employeesCache.values());
    return workers.filter(user => {
        return adminWorkersCurrentRole === 'All' || user.Role === adminWorkersCurrentRole;
    });
}

function renderWorkersTable() {
    const table = document.getElementById('workersTableBody');
    if (!table) return;

    const filteredWorkers = getFilteredAdminWorkers();
    const totalPages = Math.max(1, Math.ceil(filteredWorkers.length / ADMIN_WORKERS_ROWS_PER_PAGE));
    adminWorkersCurrentPage = Math.min(adminWorkersCurrentPage, totalPages - 1);

    if (!filteredWorkers.length) {
        table.innerHTML = `
            <tr>
                <td colspan="4" class="admin-workers-empty-row">No employees match the selected filter.</td>
            </tr>
            ${Array.from({ length: ADMIN_WORKERS_ROWS_PER_PAGE - 1 }, () => `
                <tr class="admin-workers-placeholder-row" aria-hidden="true">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            `).join('')}
        `;
        updateAdminWorkersPagination(0);
        return;
    }

    const start = adminWorkersCurrentPage * ADMIN_WORKERS_ROWS_PER_PAGE;
    const pageWorkers = filteredWorkers.slice(start, start + ADMIN_WORKERS_ROWS_PER_PAGE);
    const placeholderRows = ADMIN_WORKERS_ROWS_PER_PAGE - pageWorkers.length;

    table.innerHTML = pageWorkers.map((user, index) => {
        const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();

        return `
            <tr data-id="${user.EmployeeId}" style="--row-delay: ${Math.min(index * 0.055, 0.55)}s;">
                <td>${fullName}</td>
                <td>${user.EmployeeId}</td>
                <td>${user.Role}</td>
                <td class="text-center">
                    <div class="admin-worker-action-group">
                        
                        <!-- VIEW -->
                        <button class="admin-worker-icon-btn admin-view-btn view-btn" type="button" data-id="${user.EmployeeId}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                        <!-- EDIT -->
                        <button class="admin-worker-icon-btn admin-edit-btn edit-btn" type="button" data-id="${user.EmployeeId}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </button>

                        <!-- DELETE -->
                        <button class="admin-worker-icon-btn admin-delete-btn delete-btn" type="button" data-id="${user.EmployeeId}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"></path>
                                <path d="M8 6V4h8v2"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                                <path d="M5 6l1 14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-14"></path>
                            </svg>
                        </button>

                    </div>
                </td>
            </tr>
        `;
    }).join('') + Array.from({ length: placeholderRows }, () => `
        <tr class="admin-workers-placeholder-row" aria-hidden="true">
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    `).join('');

    updateAdminWorkersPagination(filteredWorkers.length);
}

function updateAdminWorkersPagination(totalRows) {
    const pagination = document.querySelector('[data-admin-workers-pagination]');
    const prevButton = document.querySelector('[data-admin-workers-prev]');
    const nextButton = document.querySelector('[data-admin-workers-next]');
    const dots = document.querySelector('[data-admin-workers-dots]');
    const totalPages = Math.max(1, Math.ceil(totalRows / ADMIN_WORKERS_ROWS_PER_PAGE));

    if (pagination) {
        pagination.classList.toggle('is-hidden', totalRows <= ADMIN_WORKERS_ROWS_PER_PAGE);
    }

    if (prevButton) {
        prevButton.disabled = adminWorkersCurrentPage === 0;
    }

    if (nextButton) {
        nextButton.disabled = adminWorkersCurrentPage >= totalPages - 1;
    }

    if (dots) {
        const visiblePages = getVisibleAdminWorkerPages(totalPages, adminWorkersCurrentPage);
        const activeDotIndex = Math.max(0, visiblePages.indexOf(adminWorkersCurrentPage));
        const direction = adminWorkersCurrentPage > adminWorkersLastPage
            ? 'next'
            : adminWorkersCurrentPage < adminWorkersLastPage
                ? 'prev'
                : 'still';

        dots.dataset.pageDirection = direction;
        dots.style.setProperty('--active-dot-index', activeDotIndex);

        dots.innerHTML = visiblePages.map((index) => `
            <button
                type="button"
                class="admin-workers-page-dot ${index === adminWorkersCurrentPage ? 'active' : ''}"
                data-admin-workers-page="${index}"
                aria-label="Go to page ${index + 1}"
                aria-current="${index === adminWorkersCurrentPage ? 'page' : 'false'}"
            ></button>
        `).join('');
        adminWorkersLastPage = adminWorkersCurrentPage;
    }
}

function getVisibleAdminWorkerPages(totalPages, currentPage) {
    if (totalPages <= ADMIN_WORKERS_DOT_LIMIT) {
        return Array.from({ length: totalPages }, (_, index) => index);
    }

    const centerOffset = Math.floor(ADMIN_WORKERS_DOT_LIMIT / 2);
    let start = Math.max(0, currentPage - centerOffset);
    let end = start + ADMIN_WORKERS_DOT_LIMIT;

    if (end > totalPages) {
        end = totalPages;
        start = Math.max(0, end - ADMIN_WORKERS_DOT_LIMIT);
    }

    return Array.from({ length: end - start }, (_, index) => start + index);
}

function setupAdminWorkersPagination() {
    document.querySelector('[data-admin-workers-prev]')?.addEventListener('click', () => {
        adminWorkersCurrentPage = Math.max(0, adminWorkersCurrentPage - 1);
        renderWorkersTable();
    });

    document.querySelector('[data-admin-workers-next]')?.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(getFilteredAdminWorkers().length / ADMIN_WORKERS_ROWS_PER_PAGE));
        adminWorkersCurrentPage = Math.min(totalPages - 1, adminWorkersCurrentPage + 1);
        renderWorkersTable();
    });

    document.querySelector('[data-admin-workers-dots]')?.addEventListener('click', event => {
        const dot = event.target.closest('[data-admin-workers-page]');
        if (!dot) return;

        adminWorkersCurrentPage = Number(dot.dataset.adminWorkersPage || 0);
        renderWorkersTable();
    });
}

// ================= OPEN ADD/EDIT =================
document.getElementById('openAddWorkerModal')?.addEventListener('click', () => {
    document.getElementById('workerModalTitle').innerText = 'Add Employee';
    document.getElementById('workerForm').reset();
    document.getElementById('EmployeeId').value = '';
    pendingPassword = null;
    resetConfirmPasswordState();
    clearRequiredFieldHighlights();
    clearWorkerFormError();
    setAddModeRequirements(true);
    setPasswordModalMode('add');
    syncUsernamePreview();
    openModal('workerModal');
});

document.getElementById('FirstName')?.addEventListener('input', syncUsernamePreview);
document.getElementById('LastName')?.addEventListener('input', syncUsernamePreview);

async function openEditModal(id) {
    const cachedUser = employeesCache.get(String(id));

    if (cachedUser) {
        populateEditModal(cachedUser);
        openModal('workerModal');
        return;
    }

    try {
        const res = await fetch(`${BASE_URL}/${id}`);
        const user = await res.json();
        populateEditModal(user);
        openModal('workerModal');
    } catch (err) { console.error(err); alert('Failed to fetch employee data'); }
}

function populateEditModal(user) {
    document.getElementById('workerModalTitle').innerText = 'Edit Employee';
    document.getElementById('EmployeeId').value = user.EmployeeId;
    document.getElementById('FirstName').value = user.FirstName;
    document.getElementById('MiddleName').value = user.MiddleName ?? '';
    document.getElementById('LastName').value = user.LastName;
    document.getElementById('Suffix').value = user.Suffix ?? '';
    document.getElementById('Username').value = user.Username ?? '';
    document.getElementById('Username').readOnly = false;
    document.getElementById('usernameHint').style.display = 'none';
    document.getElementById('Role').value = user.Role;
    document.getElementById('PhoneNumber').value = user.PhoneNumber ?? '';
    document.getElementById('Birthday').value = user.Birthday ?? '';
    document.getElementById('Gender').value = user.Gender ?? '';
    document.getElementById('Address').value = user.Address ?? '';
    pendingPassword = null;
    resetConfirmPasswordState();
    clearRequiredFieldHighlights();
    clearWorkerFormError();
    setPasswordModalMode('edit');
    setAddModeRequirements(false);
}

function openPasswordModal() {
    resetConfirmPasswordState();
    setPasswordModalMode(isAddMode() ? 'add' : 'edit');
    openModal('passwordModal');
}

// ================= SAVE EMPLOYEE (ADD/EDIT) =================
document.getElementById('workerForm')?.addEventListener('submit', async e => {
    e.preventDefault();

    if (showRequiredFieldsError()) {
        return;
    }

    if (isAddMode() && !pendingPassword) {
        showWorkerFormError('Please set a password for the new employee before saving.');
        return;
    }

    const id = document.getElementById('EmployeeId').value;
    const data = {
        FirstName: document.getElementById('FirstName').value,
        MiddleName: document.getElementById('MiddleName').value || null,
        LastName: document.getElementById('LastName').value,
        Suffix: document.getElementById('Suffix').value || null,
        Username: document.getElementById('Username').value,
        Role: document.getElementById('Role').value,
        PhoneNumber: document.getElementById('PhoneNumber').value || null,
        Birthday: document.getElementById('Birthday').value || null,
        Gender: document.getElementById('Gender').value || null,
        Address: document.getElementById('Address').value || null,
        Password: pendingPassword || null
    };

    if (!data.Password) delete data.Password;

    try {
        const method = id ? 'PUT' : 'POST';
        const url = id ? `${BASE_URL}/${id}` : BASE_URL;

        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        if (!res.ok) { const err = await res.json(); alert('Error: ' + JSON.stringify(err.errors ?? err)); return; }

        closeModal('workerModal');
        pendingPassword = null;
        await loadEmployees();
    } catch (err) { console.error(err); alert('Network error'); }
});

document.getElementById('editPasswordBtn')?.addEventListener('click', () => {
    openPasswordModal();
});

workerRequiredFields.forEach(field => {
    const input = document.getElementById(field.id);

    input?.addEventListener('input', () => syncRequiredFieldHighlight(field));
    input?.addEventListener('change', () => syncRequiredFieldHighlight(field));
});

document.getElementById('passwordForm')?.addEventListener('submit', async e => {
    e.preventDefault();

    if (!validateConfirmPassword()) {
        return;
    }

    const password = passwordInput?.value || '';
    const employeeId = document.getElementById('EmployeeId').value;
    const oldPassword = oldPasswordInput?.value || '';

    if (!password) {
        clearConfirmPasswordMessage();
        return;
    }

    if (!employeeId) {
        pendingPassword = password;
        clearWorkerFormError();
        closeModal('passwordModal');
        return;
    }

    if (!oldPassword) {
        oldPasswordMessage.textContent = 'Please enter your current password.';
        return;
    }

    try {
        const res = await fetch(`${BASE_URL}/${employeeId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ Password: password, OldPassword: oldPassword })
        });

        if (!res.ok) {
            const err = await res.json();

            if (err.errors?.old_password) {
                oldPasswordMessage.textContent = err.errors.old_password[0];
                return;
            }

            alert('Error: ' + JSON.stringify(err.errors ?? err));
            return;
        }

        closeModal('passwordModal');
        await loadEmployees();
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});

// ================= DELETE EMPLOYEE =================
function openDeleteModal(id) {
    deleteId = id;
    const row = document.querySelector(`tr[data-id="${id}"]`);
    const name = row ? row.children[0].innerText : '';
    document.getElementById('deleteWorkerName').innerText = name;
    openModal('deleteWorkerModal');
}

document.getElementById('confirmDeleteWorkerBtn')?.addEventListener('click', async () => {
    try {
        const res = await fetch(`${BASE_URL}/${deleteId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });

        if (!res.ok) { alert('Error deleting employee'); return; }

        closeModal('deleteWorkerModal');
        await loadEmployees();
    } catch (err) { console.error(err); alert('Network error'); }
});

// ================= VIEW EMPLOYEE =================
async function openViewModal(id) {
    const cachedUser = employeesCache.get(String(id));

    if (cachedUser) {
        populateViewModal(cachedUser);
        openModal('viewModal');
        return;
    }

    try {
        const res = await fetch(`${BASE_URL}/${id}`);
        const user = await res.json();
        employeesCache.set(String(user.EmployeeId), user);
        populateViewModal(user);
        openModal('viewModal');
    } catch (err) { console.error(err); alert('Failed to fetch employee data'); }
}

function populateViewModal(user) {
    const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();

    document.getElementById('view_name').innerText = fullName;
    document.getElementById('view_username').innerText = user.Username ?? '';
    document.getElementById('view_role').innerText = user.Role;
    document.getElementById('view_phone_number').innerText = user.PhoneNumber ?? '';
    document.getElementById('view_birthday').innerText = user.Birthday ?? '';
    document.getElementById('view_gender').innerText = user.Gender ?? '';
    document.getElementById('view_address').innerText = user.Address ?? '';
}

// ================= EVENT DELEGATION =================
document.addEventListener('click', e => {
    const viewBtn = e.target.closest('.view-btn');
    if (viewBtn) {
        openViewModal(viewBtn.dataset.id);
        return;
    }

    const editBtn = e.target.closest('.edit-btn');
    if (editBtn) {
        openEditModal(editBtn.dataset.id);
        return;
    }

    const deleteBtn = e.target.closest('.delete-btn');
    if (deleteBtn) {
        openDeleteModal(deleteBtn.dataset.id);
    }
});

// ================= CLOSE MODALS =================
document.querySelectorAll('[data-close-admin-modal]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close-admin-modal')));
});

// ================= INITIAL LOAD =================
document.addEventListener('DOMContentLoaded', () => {
    setupAdminWorkersPagination();
    loadEmployees();
    setupAdminProfileModal();
});

// ================= ROLE FILTER =================
document.getElementById('roleFilter')?.addEventListener('change', e => {
    adminWorkersCurrentRole = e.target.value;
    adminWorkersCurrentPage = 0;
    renderWorkersTable();
});

// =============== REMOVE NATIVE REQUIRED ATTRIBUTES ===============
function removeNativeRequiredAttributes() {
    [
        'FirstName', 'LastName', 'Role', 'PhoneNumber', 'Birthday', 'Password', 'ConfirmPassword'
    ].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.removeAttribute('required');
    });
}

document.addEventListener('DOMContentLoaded', removeNativeRequiredAttributes);
// =============== END REMOVE NATIVE REQUIRED ATTRIBUTES ===============
