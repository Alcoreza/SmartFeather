const BASE_URL = '/api/admin/workers';
let deleteId = null;
const employeesCache = new Map();

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
// ================= LOAD EMPLOYEES =================
async function loadEmployees() {
    const table = document.getElementById('workersTableBody');
    table.innerHTML = '';

    try {
        const res = await fetch(BASE_URL, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });

        const data = await res.json();

        employeesCache.clear();

        data.forEach(user => {
            employeesCache.set(String(user.EmployeeId), user);

            const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();

            table.innerHTML += `
                <tr data-id="${user.EmployeeId}">
                    <td>${fullName}</td>
                    <td>${user.EmployeeId}</td>
                    <td>${user.Role}</td>
                    <td class="text-center">
                        <div class="admin-worker-action-group">
                            
                            <!-- VIEW -->
                            <button class="admin-worker-icon-btn admin-view-btn view-btn" type="button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>

                            <!-- EDIT -->
                            <button class="admin-worker-icon-btn admin-edit-btn edit-btn" type="button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                            </button>

                            <!-- DELETE -->
                            <button class="admin-worker-icon-btn admin-delete-btn delete-btn" type="button">
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
        });

    } catch (err) {
        console.error(err);
        alert('Failed to load employees');
    }
}

// ================= OPEN ADD/EDIT =================
document.getElementById('openAddWorkerModal')?.addEventListener('click', () => {
    document.getElementById('workerModalTitle').innerText = 'Add Employee';
    document.getElementById('workerForm').reset();
    document.getElementById('EmployeeId').value = '';
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
    document.getElementById('Password').value = '';
}

// ================= SAVE EMPLOYEE (ADD/EDIT) =================
document.getElementById('workerForm')?.addEventListener('submit', async e => {
    e.preventDefault();

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
        Password: document.getElementById('Password').value || null
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
        await loadEmployees();
    } catch (err) { console.error(err); alert('Network error'); }
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
    const tr = e.target.closest('tr');
    if (!tr) return;
    const id = tr.getAttribute('data-id');

    if (e.target.classList.contains('edit-btn')) openEditModal(id);
    if (e.target.classList.contains('view-btn')) openViewModal(id);
    if (e.target.classList.contains('delete-btn')) openDeleteModal(id);
});

// ================= CLOSE MODALS =================
document.querySelectorAll('[data-close-admin-modal]').forEach(btn => {
    btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close-admin-modal')));
});

// ================= INITIAL LOAD =================
document.addEventListener('DOMContentLoaded', () => {
    loadEmployees();
    setupAdminProfileModal();
});
