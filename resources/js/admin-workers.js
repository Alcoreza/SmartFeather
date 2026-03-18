const BASE_URL = '/api/admin/workers';

// LOAD TABLE
async function loadEmployees() {
    const res = await fetch(BASE_URL);
    const data = await res.json();

    const table = document.getElementById('workersTableBody');
    table.innerHTML = '';

    data.forEach(user => {
        const fullName = `
            ${user.first_name} 
            ${user.middle_name ?? ''} 
            ${user.last_name} 
            ${user.suffix ?? ''}
        `;

        table.innerHTML += `
            <tr>
                <td>${fullName}</td>
                <td>${user.id}</td>
                <td>${user.role}</td>
                <td class="text-center">
                    <button onclick="openEditModal('${user.id}')">Edit</button>
                    <button onclick="deleteEmployee('${user.id}')">Delete</button>
                </td>
            </tr>
        `;
    });
}

document.addEventListener('DOMContentLoaded', loadEmployees);

// ADD
async function addEmployee() {
    const data = {
        id: document.getElementById('id').value,
        first_name: document.getElementById('first_name').value,
        middle_name: document.getElementById('middle_name').value,
        last_name: document.getElementById('last_name').value,
        suffix: document.getElementById('suffix').value,
        role: document.getElementById('role').value,
        phone_number: document.getElementById('phone_number').value,
        birthday: document.getElementById('birthday').value,
        gender: document.getElementById('gender').value,
        address: document.getElementById('address').value
    };

    await fetch(BASE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    });

    loadEmployees();
}

// OPEN EDIT MODAL
async function openEditModal(id) {
    const res = await fetch(`${BASE_URL}/${id}`);
    const user = await res.json();

    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_first_name').value = user.first_name;
    document.getElementById('edit_middle_name').value = user.middle_name ?? '';
    document.getElementById('edit_last_name').value = user.last_name;
    document.getElementById('edit_suffix').value = user.suffix ?? '';
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_phone_number').value = user.phone_number ?? '';
    document.getElementById('edit_birthday').value = user.birthday ?? '';
    document.getElementById('edit_gender').value = user.gender ?? '';
    document.getElementById('edit_address').value = user.address ?? '';

    document.getElementById('editModal').style.display = 'block';
}

// UPDATE
async function updateEmployee() {
    const id = document.getElementById('edit_id').value;

    const data = {
        first_name: document.getElementById('edit_first_name').value,
        middle_name: document.getElementById('edit_middle_name').value,
        last_name: document.getElementById('edit_last_name').value,
        suffix: document.getElementById('edit_suffix').value,
        role: document.getElementById('edit_role').value,
        phone_number: document.getElementById('edit_phone_number').value,
        birthday: document.getElementById('edit_birthday').value,
        gender: document.getElementById('edit_gender').value,
        address: document.getElementById('edit_address').value
    };

    await fetch(`${BASE_URL}/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    });

    document.getElementById('editModal').style.display = 'none';
    loadEmployees();
}

// DELETE
async function deleteEmployee(id) {
    if (!confirm('Delete this employee?')) return;

    await fetch(`${BASE_URL}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });

    loadEmployees();
}