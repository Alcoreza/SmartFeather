const BASE_URL = '/api/admin/workers';

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
                        <button class="view-worker-btn icon-btn" type="button" data-id="${user.EmployeeId}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;
        });

    } catch (err) {
        console.error(err);
        alert('Failed to load employees');
    }
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
    const selected = e.target.value;

    document.querySelectorAll('#workersTableBody tr').forEach(row => {
        const role = row.children[2].innerText;
        row.style.display = (selected === 'All' || role === selected) ? '' : 'none';
    });
});

// ================= INITIAL LOAD =================
document.addEventListener('DOMContentLoaded', () => {
    loadEmployees();
    setupProfileModal();
});
