const BASE_URL = '/api/admin/workers';
const ADMIN_WORKERS_ROWS_PER_PAGE = 5;
const EMPLOYEE_PHONE_PATTERN = /^09\d{9}$/;

let deleteId = null;
const employeesCache = new Map();
let adminWorkersCurrentPage = 0;
let adminWorkersCurrentRole = 'All';
let pendingWorkerSave = null;

// ================= MODAL HELPERS =================
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function showAdminWorkerConfirmModal(message, title = 'Confirm Save', confirmText = 'Confirm') {
    return new Promise((resolve) => {
        document.getElementById('adminWorkerGenericConfirmModal')?.remove();

        const modal = document.createElement('div');
        modal.className = 'admin-worker-modal-backdrop confirm-modal-top';
        modal.id = 'adminWorkerGenericConfirmModal';
        modal.style.display = 'flex';
        modal.innerHTML = `
            <div class="admin-worker-modal-card admin-delete-worker-card">
                <div class="admin-worker-modal-header">
                    <h2></h2>
                    <div class="admin-worker-header-line"></div>
                </div>
                <div class="admin-worker-modal-body">
                    <p class="admin-delete-worker-text"></p>
                    <div class="admin-worker-modal-actions">
                        <button type="button" class="admin-worker-btn cancel" data-confirm-cancel>Cancel</button>
                        <button type="button" class="admin-worker-btn save" data-confirm-ok></button>
                    </div>
                </div>
            </div>
        `;

        modal.querySelector('h2').textContent = title;
        modal.querySelector('p').textContent = message;
        modal.querySelector('[data-confirm-ok]').textContent = confirmText;

        const close = (confirmed) => {
            modal.remove();
            resolve(confirmed);
        };

        modal.querySelector('[data-confirm-cancel]').addEventListener('click', () => close(false));
        modal.querySelector('[data-confirm-ok]').addEventListener('click', () => close(true));
        modal.addEventListener('click', (event) => {
            if (event.target === modal) close(false);
        });

        document.body.appendChild(modal);
    });
}

function usernamePart(value) {
    return String(value || '').replace(/[^a-z0-9]/gi, '');
}

function usernameSuffixPart(value) {
    const suffix = usernamePart(value).toLowerCase();

    if (suffix === 'junior') return 'jr';
    if (suffix === 'senior') return 'sr';

    return suffix;
}

function generateUsername(firstName, lastName, suffix = '') {
    const initial = (firstName || '').trim().charAt(0);
    const surname = usernamePart(lastName);
    const suffixPart = usernameSuffixPart(suffix);

    return `${initial}${surname}${suffixPart}`.toLowerCase();
}

function normalizeUsername(value) {
    return String(value || '').trim().toLowerCase();
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
    const suffix = document.getElementById('Suffix').value || '';
    usernameInput.value = generateUsername(firstName, lastName, suffix);
}

const passwordInput = document.getElementById('passwordModalPassword');
const confirmPasswordInput = document.getElementById('passwordModalConfirm');
const oldPasswordInput = document.getElementById('passwordModalOldPassword');
const confirmPasswordMessage = document.getElementById('confirmPasswordMessage');
const oldPasswordMessage = document.getElementById('oldPasswordMessage');
const phoneInput = document.getElementById('PhoneNumber');
const birthdayInput = document.getElementById('Birthday');
const addPasswordFields = document.getElementById('addPasswordFields');
const addPasswordInput = document.getElementById('AddPassword');
const addConfirmPasswordInput = document.getElementById('AddConfirmPassword');
const editPasswordField = document.getElementById('editPasswordField');
let pendingPassword = null;

const workerRequiredFields = [
    { id: 'FirstName', label: 'First Name' },
    { id: 'LastName', label: 'Last Name' },
    { id: 'Role', label: 'Role' },
    { id: 'PhoneNumber', label: 'Phone Number' },
    { id: 'Birthday', label: 'Birthday' },
    { id: 'Gender', label: 'Gender' },
    { id: 'AddPassword', label: 'Password', addOnly: true },
    { id: 'AddConfirmPassword', label: 'Confirm Password', addOnly: true }
];

let shouldTrackRequiredHighlights = false;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getReadableErrorMessage(error) {
    if (!error) return 'Unable to save employee. Please try again.';

    if (error.errors) {
        const messages = Object.values(error.errors).flat().filter(Boolean);
        if (messages.length) return messages.join(' ');
    }

    return error.message || error.error || 'Unable to save employee. Please try again.';
}

async function parseErrorResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        return response.json();
    }

    const text = await response.text();
    return {
        message: text
            ? `Server error (${response.status}). Please try again or check the Laravel log.`
            : `Request failed (${response.status}). Please try again.`,
    };
}

function getTodayDateValue() {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${today.getFullYear()}-${month}-${day}`;
}

function getYesterdayDateValue() {
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const month = String(yesterday.getMonth() + 1).padStart(2, '0');
    const day = String(yesterday.getDate()).padStart(2, '0');

    return `${yesterday.getFullYear()}-${month}-${day}`;
}

function setBirthdayMaxDate() {
    if (birthdayInput) {
        birthdayInput.max = getYesterdayDateValue();
    }
}

function sanitizePhoneValue(value) {
    return String(value || '').replace(/\D/g, '').slice(0, 11);
}

function sanitizeNameValue(value) {
    return String(value || '').replace(/\d/g, '');
}

function markWorkerFieldError(input, message) {
    input?.closest('.admin-worker-field')?.classList.add('has-error');
    showWorkerFormError(message);
    setTimeout(() => input?.focus(), 150);
}

function clearWorkerFieldError(input) {
    input?.closest('.admin-worker-field')?.classList.remove('has-error');

    const hasErrors = document.querySelector('#workerForm .admin-worker-field.has-error');
    if (!hasErrors) {
        clearWorkerFormError();
    }
}

function getWorkerInputForErrorKey(key) {
    const fieldMap = {
        FirstName: 'FirstName',
        MiddleName: 'MiddleName',
        LastName: 'LastName',
        Suffix: 'Suffix',
        Role: 'Role',
        PhoneNumber: 'PhoneNumber',
        Birthday: 'Birthday',
        Gender: 'Gender',
        Address: 'Address',
        Username: 'Username',
        Password: isAddMode() ? 'AddPassword' : 'passwordModalPassword',
    };

    return document.getElementById(fieldMap[key] || key);
}

function showBackendValidationErrors(error) {
    if (!error?.errors) {
        showWorkerFormError(getReadableErrorMessage(error));
        return;
    }

    if (error.errors.PhoneNumber?.length) {
        const phoneInput = getWorkerInputForErrorKey('PhoneNumber');
        markWorkerFieldError(phoneInput, error.errors.PhoneNumber[0]);
    } else {
        showWorkerFormError(getReadableErrorMessage(error));
    }

    Object.keys(error.errors).forEach((key) => {
        getWorkerInputForErrorKey(key)?.closest('.admin-worker-field')?.classList.add('has-error');
    });
}

function validateWorkerFormats() {
    const phoneValue = phoneInput?.value.trim() || '';

    if (!EMPLOYEE_PHONE_PATTERN.test(phoneValue)) {
        markWorkerFieldError(phoneInput, 'Phone number must use 09XXXXXXXXX format.');
        return false;
    }

    const birthdayValue = birthdayInput?.value || '';
    if (!birthdayValue || Number.isNaN(new Date(birthdayValue).getTime())) {
        markWorkerFieldError(birthdayInput, 'Please enter a valid birthday.');
        return false;
    }

    if (birthdayValue >= getTodayDateValue()) {
        markWorkerFieldError(birthdayInput, 'Birthday must be earlier than today.');
        return false;
    }

    return true;
}

function validateAddPasswordFields() {
    if (!isAddMode()) {
        return true;
    }

    const password = addPasswordInput?.value || '';
    const confirmPassword = addConfirmPasswordInput?.value || '';

    if (!password) {
        markWorkerFieldError(addPasswordInput, 'Please enter a password.');
        return false;
    }

    if (password.length < 8) {
        markWorkerFieldError(addPasswordInput, 'Password must be at least 8 characters.');
        return false;
    }

    if (!confirmPassword) {
        markWorkerFieldError(addConfirmPasswordInput, 'Please confirm the password.');
        return false;
    }

    if (password !== confirmPassword) {
        addPasswordInput?.closest('.admin-worker-field')?.classList.add('has-error');
        markWorkerFieldError(addConfirmPasswordInput, 'Passwords do not match.');
        return false;
    }

    clearWorkerFieldError(addPasswordInput);
    clearWorkerFieldError(addConfirmPasswordInput);
    return true;
}

function validateBirthdayField(showError = false) {
    const birthdayValue = birthdayInput?.value || '';

    if (!birthdayValue) {
        return true;
    }

    if (Number.isNaN(new Date(birthdayValue).getTime()) || birthdayValue >= getTodayDateValue()) {
        if (showError) {
            markWorkerFieldError(birthdayInput, 'Birthday must be earlier than today.');
        } else {
            birthdayInput?.closest('.admin-worker-field')?.classList.add('has-error');
        }

        return false;
    }

    birthdayInput?.closest('.admin-worker-field')?.classList.remove('has-error');
    const hasErrors = document.querySelector('#workerForm .admin-worker-field.has-error');
    if (!hasErrors) {
        clearWorkerFormError();
    }

    return true;
}

async function validateEmployeePhoneAvailability(employeeId = '') {
    const response = await fetch(`${BASE_URL}/check-phone`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            PhoneNumber: phoneInput?.value || '',
            EmployeeId: employeeId || null,
        }),
    });

    const result = await parseErrorResponse(response);

    if (!response.ok) {
        showBackendValidationErrors(result);
        return false;
    }

    if (!result.available) {
        markWorkerFieldError(phoneInput, result.message || 'This phone number is already assigned to another employee.');
        return false;
    }

    clearWorkerFieldError(phoneInput);
    return true;
}

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

    if (password.length < 8) {
        confirmPasswordMessage.textContent = 'Password must be at least 8 characters.';
        return false;
    }

    clearConfirmPasswordMessage();
    return true;
}

function resetConfirmPasswordState() {
    if (passwordInput) passwordInput.value = '';
    if (confirmPasswordInput) confirmPasswordInput.value = '';
    if (oldPasswordInput) oldPasswordInput.value = '';
    if (addPasswordInput) addPasswordInput.value = '';
    if (addConfirmPasswordInput) addConfirmPasswordInput.value = '';
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

    if (addPasswordFields) {
        addPasswordFields.style.display = isEditMode ? 'none' : 'grid';
    }

    if (editPasswordField) {
        editPasswordField.style.display = isEditMode ? 'block' : 'none';
    }

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
    if (field.addOnly && !isAddMode()) {
        return false;
    }

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
                <td colspan="3" class="admin-workers-empty-row">No employees match the selected filter.</td>
            </tr>
            ${Array.from({ length: ADMIN_WORKERS_ROWS_PER_PAGE - 1 }, () => `
                <tr class="admin-workers-placeholder-row" aria-hidden="true">
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

    table.innerHTML = pageWorkers.map(user => {
        const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();

        return `
            <tr data-id="${escapeHtml(user.EmployeeId)}">
                <td>${escapeHtml(fullName)}</td>
                <td>${escapeHtml(user.Role)}</td>
                <td class="text-center">
                    <div class="admin-worker-action-group">
                        
                        <!-- VIEW -->
                        <button class="admin-worker-icon-btn admin-view-btn view-btn" type="button" data-id="${escapeHtml(user.EmployeeId)}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                        <!-- EDIT -->
                        <button class="admin-worker-icon-btn admin-edit-btn edit-btn" type="button" data-id="${escapeHtml(user.EmployeeId)}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </button>

                        <!-- DEACTIVATE -->
                        <button class="admin-worker-icon-btn admin-delete-btn delete-btn" type="button" data-id="${escapeHtml(user.EmployeeId)}">
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
        dots.innerHTML = Array.from({ length: totalPages }, (_, index) => `
            <button
                type="button"
                class="admin-workers-page-dot ${index === adminWorkersCurrentPage ? 'active' : ''}"
                data-admin-workers-page="${index}"
                aria-label="Go to page ${index + 1}"
                aria-current="${index === adminWorkersCurrentPage ? 'page' : 'false'}"
            ></button>
        `).join('');
    }
}

function setupAdminWorkersPagination() {
    document.querySelector('[data-admin-workers-prev]')?.addEventListener('click', () => {
        adminWorkersCurrentPage = Math.max(0, adminWorkersCurrentPage - 1);
        renderWorkersTable();
    });

    document.querySelector('[data-admin-workers-next]')?.addEventListener('click', () => {
        adminWorkersCurrentPage += 1;
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
    setBirthdayMaxDate();
    syncUsernamePreview();
    openModal('workerModal');
});

document.getElementById('FirstName')?.addEventListener('input', syncUsernamePreview);
document.getElementById('LastName')?.addEventListener('input', syncUsernamePreview);
document.getElementById('Suffix')?.addEventListener('input', syncUsernamePreview);

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
    document.getElementById('Username').value = normalizeUsername(user.Username);
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

    if (!validateWorkerFormats()) {
        return;
    }

    if (!validateAddPasswordFields()) {
        return;
    }

    const id = document.getElementById('EmployeeId').value;

    try {
        const phoneAvailable = await validateEmployeePhoneAvailability(id);
        if (!phoneAvailable) {
            return;
        }
    } catch (err) {
        console.error(err);
        showWorkerFormError('Unable to check phone number. Please try again.');
        return;
    }

    const addPassword = addPasswordInput?.value || null;
    const data = {
        FirstName: document.getElementById('FirstName').value,
        MiddleName: document.getElementById('MiddleName').value || null,
        LastName: document.getElementById('LastName').value,
        Suffix: document.getElementById('Suffix').value || null,
        Username: normalizeUsername(document.getElementById('Username').value),
        Role: document.getElementById('Role').value,
        PhoneNumber: document.getElementById('PhoneNumber').value || null,
        Birthday: document.getElementById('Birthday').value || null,
        Gender: document.getElementById('Gender').value || null,
        Address: document.getElementById('Address').value || null,
        Password: isAddMode() ? addPassword : null
    };

    if (!data.Password) delete data.Password;

    pendingWorkerSave = {
        id,
        method: id ? 'PUT' : 'POST',
        url: id ? `${BASE_URL}/${id}` : BASE_URL,
        data,
    };

    const fullName = `${data.FirstName || ''} ${data.MiddleName || ''} ${data.LastName || ''} ${data.Suffix || ''}`.trim();
    const confirmMessage = document.getElementById('saveWorkerConfirmMessage');
    if (confirmMessage) {
        confirmMessage.textContent = id
            ? `Save changes to ${fullName || 'this employee'}?`
            : `Add ${fullName || 'this employee'} as a new employee?`;
    }

    openModal('saveWorkerConfirmModal');
});

document.getElementById('confirmSaveWorkerBtn')?.addEventListener('click', async () => {
    if (!pendingWorkerSave) {
        closeModal('saveWorkerConfirmModal');
        return;
    }

    try {
        const res = await fetch(pendingWorkerSave.url, {
            method: pendingWorkerSave.method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(pendingWorkerSave.data)
        });

        if (!res.ok) {
            const err = await parseErrorResponse(res);
            closeModal('saveWorkerConfirmModal');
            showBackendValidationErrors(err);
            return;
        }

        closeModal('saveWorkerConfirmModal');
        closeModal('workerModal');
        pendingPassword = null;
        pendingWorkerSave = null;
        await loadEmployees();
    } catch (err) {
        console.error(err);
        closeModal('saveWorkerConfirmModal');
        showWorkerFormError('Network error. Please check your connection and try again.');
    }
});

document.getElementById('editPasswordBtn')?.addEventListener('click', () => {
    openPasswordModal();
});

workerRequiredFields.forEach(field => {
    const input = document.getElementById(field.id);

    input?.addEventListener('input', () => syncRequiredFieldHighlight(field));
    input?.addEventListener('change', () => syncRequiredFieldHighlight(field));
});

['FirstName', 'MiddleName', 'LastName', 'Suffix'].forEach((fieldId) => {
    const input = document.getElementById(fieldId);

    input?.addEventListener('input', () => {
        const sanitizedValue = sanitizeNameValue(input.value);

        if (input.value !== sanitizedValue) {
            input.value = sanitizedValue;
        }

        if (['FirstName', 'LastName', 'Suffix'].includes(fieldId)) {
            syncUsernamePreview();
        }
    });
});

phoneInput?.addEventListener('input', () => {
    phoneInput.value = sanitizePhoneValue(phoneInput.value);
    if (EMPLOYEE_PHONE_PATTERN.test(phoneInput.value)) {
        clearWorkerFieldError(phoneInput);
    }
});

birthdayInput?.addEventListener('change', () => {
    setBirthdayMaxDate();
    validateBirthdayField(true);
});

birthdayInput?.addEventListener('input', () => {
    validateBirthdayField(true);
});

[addPasswordInput, addConfirmPasswordInput].forEach((input) => {
    input?.addEventListener('input', () => {
        if (addPasswordInput?.value && addConfirmPasswordInput?.value && addPasswordInput.value === addConfirmPasswordInput.value && addPasswordInput.value.length >= 8) {
            clearWorkerFieldError(addPasswordInput);
            clearWorkerFieldError(addConfirmPasswordInput);
        }
    });
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

    const confirmed = await showAdminWorkerConfirmModal('Save this password change?', 'Save Password');

    if (!confirmed) {
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
            const err = await parseErrorResponse(res);

            if (err.errors?.old_password) {
                oldPasswordMessage.textContent = err.errors.old_password[0];
                return;
            }

            oldPasswordMessage.textContent = getReadableErrorMessage(err);
            return;
        }

        closeModal('passwordModal');
        await loadEmployees();
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});

// ================= DEACTIVATE EMPLOYEE =================
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

        if (!res.ok) {
            const err = await parseErrorResponse(res);
            alert(getReadableErrorMessage(err) || 'Error deactivating employee');
            return;
        }

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
    document.getElementById('view_username').innerText = normalizeUsername(user.Username);
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
    setBirthdayMaxDate();
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
        'FirstName',
        'LastName',
        'Role',
        'PhoneNumber',
        'Birthday',
        'Password',
        'ConfirmPassword',
        'AddPassword',
        'AddConfirmPassword'
    ].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.removeAttribute('required');
    });
}

document.addEventListener('DOMContentLoaded', removeNativeRequiredAttributes);
// =============== END REMOVE NATIVE REQUIRED ATTRIBUTES ===============
