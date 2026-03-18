const BASE_URL = '/api/admin/workers';

// ================= LOAD =================
async function loadEmployees() {
    try {
        const res = await fetch(BASE_URL);
        const data = await res.json();
        const table = document.getElementById('workersTableBody');
        table.innerHTML = '';

        data.forEach(user => {
            const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`.trim();

            table.innerHTML += `
                <tr>
                    <td>${fullName}</td>
                    <td>${user.EmployeeId}</td>
                    <td>${user.Role}</td>
                    <td class="text-center">
                        <button onclick="openViewModal(${user.EmployeeId})">View</button>
                        <button onclick="openEditModal(${user.EmployeeId})">Edit</button>
                        <button onclick="openDeleteModal(${user.EmployeeId})">Delete</button>
                    </td>
                </tr>
            `;
        });
    } catch (err) {
        console.error(err);
        alert('Failed to load employees');
    }
}

// ================= ADD =================
document.getElementById('openAddWorkerModal')?.addEventListener('click', () => {
    document.getElementById('addModal').style.display = 'block';
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

        document.getElementById('addModal').style.display = 'none';
        document.getElementById('addEmployeeForm').reset();
        loadEmployees();
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});

// ================= EDIT =================
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

        document.getElementById('editModal').style.display = 'block';
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

        document.getElementById('editModal').style.display = 'none';
        loadEmployees();
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});

// ================= DELETE =================
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

// ================= VIEW =================
function openViewModal(id) {
    fetch(`${BASE_URL}/${id}`)
        .then(res => res.json())
        .then(user => {
            const fullName = `${user.FirstName} ${user.MiddleName ?? ''} ${user.LastName} ${user.Suffix ?? ''}`;
            alert(`Name: ${fullName}\nRole: ${user.Role}\nPhone: ${user.PhoneNumber ?? ''}\nBirthday: ${user.Birthday ?? ''}\nGender: ${user.Gender ?? ''}\nAddress: ${user.Address ?? ''}`);
        })
        .catch(err => {
            console.error(err);
            alert('Failed to fetch employee data');
        });
}

// ================= INITIALIZE =================
document.addEventListener('DOMContentLoaded', () => {
    loadEmployees();

    // Close modals
    document.querySelectorAll('[data-close-admin-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-close-admin-modal');
            document.getElementById(modalId).style.display = 'none';
        });
    });

    // Role filter
    document.getElementById('roleFilter')?.addEventListener('change', e => {
        const selected = e.target.value;
        document.querySelectorAll('#workersTableBody tr').forEach(row => {
            const role = row.children[2].innerText;
            row.style.display = (selected === 'All' || role === selected) ? '' : 'none';
        });
    });
});
