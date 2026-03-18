const BASE_URL = '/api/admin/workers';

// ================= LOAD EMPLOYEES =================
async function loadEmployees() {
    try {
        const res = await fetch(BASE_URL);
        const data = await res.json();

        const table = document.getElementById('workersTableBody');
        table.innerHTML = '';

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
    } catch (err) {
        console.error(err);
        alert('Failed to load employees');
    }
}

// ================= MODAL HELPERS =================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'block';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// ================= ADD EMPLOYEE =================
document.getElementById('openAddWorkerModal')?.addEventListener('click', () => {
    openModal('addModal');
});

document.getElementById('addEmployeeForm')?.addEventListener('submit', async e => {
    e.preventDefault();

    const data = {
        FirstName: document.getElementById('first_name').value,
        MiddleName: document.getElementById('middle_name').value || null,
        LastName: document.getElementById('last_name').value,
        Suffix: document.getElementById('suffix').value || null,
        Role: document.getElementById('role').value,
        PhoneNumber: document.getElementById('phone_number').value || null,
        Birthday: document.getElementById('birthday').value || null,
        Gender: document.getElementById('gender').value || null,
        Address: document.getElementById('address').value || null,
        Password: document.getElementById('password').value
    };

    try {
        const res = await fetch(BASE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        if (!res.ok) {
            const err = await res.json();
            alert('Error: ' + JSON.stringify(err.errors ?? err));
            return;
        }

        closeModal('addModal');
        document.getElementById('addEmployeeForm').reset();
        loadEmployees();
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});

// ================= EDIT EMPLOYEE =================
async function openEditModal(id) {
    try {
        const res = await fetch(`${BASE_URL}/${id}`);
        const user = await res.json();

        document.getElementById('edit_id').value = user.EmployeeId;
        document.getElementById('edit_first_name').value = user.FirstName;
        document.getElementById('edit_middle_name').value = user.MiddleName ?? '';
        document.getElementById('edit_last_name').value = user.LastName;
        document.getElementById('edit_suffix').value = user.Suffix ?? '';
        document.getElementById('edit_role').value = user.Role;
        document.getElementById('edit_phone_number').value = user.PhoneNumber ?? '';
        document.getElementById('edit_birthday').value = user.Birthday ?? '';
        document.getElementById('edit_gender').value = user.Gender ?? '';
        document.getElementById('edit_address').value = user.Address ?? '';
        document.getElementById('edit_password').value = '';

        openModal('editModal');
    } catch (err) {
        console.error(err);
        alert('Failed to fetch employee data');
    }
}

document.getElementById('editEmployeeForm')?.addEventListener('submit', async e => {
    e.preventDefault();

    const id = document.getElementById('edit_id').value;
    const data = {
        FirstName: document.getElementById('edit_first_name').value,
        MiddleName: document.getElementById('edit_middle_name').value || null,
        LastName: document.getElementById('edit_last_name').value,
        Suffix: document.getElementById('edit_suffix').value || null,
        Role: document.getElementById('edit_role').value,
        PhoneNumber: document.getElementById('edit_phone_number').value || null,
        Birthday: document.getElementById('edit_birthday').value || null,
        Gender: document.getElementById('edit_gender').value || null,
        Address: document.getElementById('edit_address').value || null,
        Password: document.getElementById('edit_password').value
    };

    if (data.Password === '') delete data.Password;

    try {
        const res = await fetch(`${BASE_URL}/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        if (!res.ok) {
            const err = await res.json();
            alert('Error: ' + JSON.stringify(err.errors ?? err));
            return;
        }

        closeModal('editModal');
        loadEmployees();
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});

// ================= DELETE EMPLOYEE =================
let deleteId = null;
function openDeleteModal(id) {
    deleteId = id;
    if (confirm("Are you sure you want to delete this employee?")) {
        deleteEmployee();
    }
}

async function deleteEmployee() {
    try {
        const res = await fetch(`${BASE_URL}/${deleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!res.ok) {
            alert('Error deleting employee');
            return;
        }

        loadEmployees();
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
}

// ================= VIEW EMPLOYEE =================
async function openViewModal(id) {
    try {
        const res = await fetch(`${BASE_URL}/${id}`);
        const user = await res.json();

        // Fill a view modal (you can reuse edit modal structure or create a new one)
        const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();
        alert(`Name: ${fullName}\nRole: ${user.Role}\nPhone: ${user.PhoneNumber ?? ''}\nBirthday: ${user.Birthday ?? ''}\nGender: ${user.Gender ?? ''}\nAddress: ${user.Address ?? ''}`);
    } catch (err) {
        console.error(err);
        alert('Failed to fetch employee data');
    }
}

// ================= EVENT DELEGATION FOR TABLE BUTTONS =================
document.addEventListener('click', e => {
    const tr = e.target.closest('tr');
    if (!tr) return;
    const id = tr.getAttribute('data-id');

    if (e.target.classList.contains('edit-btn')) openEditModal(id);
    if (e.target.classList.contains('view-btn')) openViewModal(id);
    if (e.target.classList.contains('delete-btn')) openDeleteModal(id);
});

// ================= ROLE FILTER =================
document.getElementById('roleFilter')?.addEventListener('change', e => {
    const selected = e.target.value;
    document.querySelectorAll('#workersTableBody tr').forEach(row => {
        const role = row.children[2].innerText;
        row.style.display = (selected === 'All' || role === selected) ? '' : 'none';
    });
});

// ================= CLOSE MODALS =================
document.querySelectorAll('[data-close-admin-modal]').forEach(btn => {
    btn.addEventListener('click', () => {
        const modalId = btn.getAttribute('data-close-admin-modal');
        closeModal(modalId);
    });
});

// ================= INITIAL LOAD =================
document.addEventListener('DOMContentLoaded', () => {
    loadEmployees();
});
