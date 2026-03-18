const BASE_URL = '/api/admin/workers';
let deleteId = null;

// ================= MODAL HELPERS =================
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// ================= LOAD EMPLOYEES =================
async function loadEmployees() {
    const table = document.getElementById('workersTableBody');
    table.innerHTML = '';
    try {
        const res = await fetch(BASE_URL, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        });
        const data = await res.json();

        data.forEach(user => {
            const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();
            table.innerHTML += `
                <tr data-id="${user.EmployeeId}">
                    <td>${fullName}</td>
                    <td>${user.EmployeeId}</td>
                    <td>${user.Role}</td>
                    <td class="text-center">
                        <button class="view-btn">View</button>
                        <button class="edit-btn">Edit</button>
                        <button class="delete-btn">Delete</button>
                    </td>
                </tr>
            `;
        });
    } catch (err) { console.error(err); alert('Failed to load employees'); }
}

// ================= OPEN ADD/EDIT =================
document.getElementById('openAddWorkerModal')?.addEventListener('click', () => {
    document.getElementById('workerModalTitle').innerText = 'Add Employee';
    document.getElementById('workerForm').reset();
    document.getElementById('EmployeeId').value = '';
    openModal('workerModal');
});

async function openEditModal(id) {
    try {
        const res = await fetch(`${BASE_URL}/${id}`);
        const user = await res.json();

        document.getElementById('workerModalTitle').innerText = 'Edit Employee';
        document.getElementById('EmployeeId').value = user.EmployeeId;
        document.getElementById('FirstName').value = user.FirstName;
        document.getElementById('MiddleName').value = user.MiddleName ?? '';
        document.getElementById('LastName').value = user.LastName;
        document.getElementById('Suffix').value = user.Suffix ?? '';
        document.getElementById('Role').value = user.Role;
        document.getElementById('PhoneNumber').value = user.PhoneNumber ?? '';
        document.getElementById('Birthday').value = user.Birthday ?? '';
        document.getElementById('Gender').value = user.Gender ?? '';
        document.getElementById('Address').value = user.Address ?? '';
        document.getElementById('Password').value = '';

        openModal('workerModal');
    } catch (err) { console.error(err); alert('Failed to fetch employee data'); }
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
        loadEmployees();
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
        loadEmployees();
    } catch (err) { console.error(err); alert('Network error'); }
});

// ================= VIEW EMPLOYEE =================
async function openViewModal(id) {
    try {
        const res = await fetch(`${BASE_URL}/${id}`);
        const user = await res.json();
        const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();

        document.getElementById('view_name').innerText = fullName;
        document.getElementById('view_role').innerText = user.Role;
        document.getElementById('view_phone_number').innerText = user.PhoneNumber ?? '';
        document.getElementById('view_birthday').innerText = user.Birthday ?? '';
        document.getElementById('view_gender').innerText = user.Gender ?? '';
        document.getElementById('view_address').innerText = user.Address ?? '';

        openModal('viewModal');
    } catch (err) { console.error(err); alert('Failed to fetch employee data'); }
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
document.addEventListener('DOMContentLoaded', () => loadEmployees());
