document.addEventListener("DOMContentLoaded", async () => {
    setupManagerProfileModal();
    await renderMaintenanceRecords();
});

async function renderMaintenanceRecords() {
    const tbody = document.getElementById("managerMaintenanceTableBody");
    const card = document.querySelector(".manager-maintenance-card");
    if (!tbody) return;

    try {
        const response = await fetch(
            "/api/manager/sensors/maintenance-records",
        );
        const data = await response.json();
        const records = Array.isArray(data.records) ? data.records : [];

        if (!records.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="manager-maintenance-empty">No maintenance records available.</div>
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = records
                .map(
                    (record) => `
                <tr>
                    <td>${escapeHtml(record.sensor_type)}</td>
                    <td>${escapeHtml(record.name)}</td>
                    <td>${escapeHtml(record.house_number)}</td>
                    <td>${escapeHtml(record.start_date)}</td>
                    <td>${escapeHtml(record.end_date)}</td>
                    <td>${escapeHtml(record.status)}</td>
                </tr>
            `,
                )
                .join("");
        }

        animateMaintenanceCard(card);
        animateMaintenanceRows();
    } catch (error) {
        console.error("Failed to load maintenance records.", error);
        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    <div class="manager-maintenance-empty">Maintenance records could not be loaded.</div>
                </td>
            </tr>
        `;
    }
}

function animateMaintenanceCard(card) {
    if (!card) return;

    card.style.opacity = "0";
    card.style.transform = "translateY(18px)";

    setTimeout(() => {
        card.style.transition = "opacity 0.45s ease, transform 0.45s ease";
        card.style.opacity = "1";
        card.style.transform = "translateY(0)";
    }, 120);
}

function animateMaintenanceRows() {
    const rows = document.querySelectorAll(
        ".manager-maintenance-table tbody tr td",
    );

    rows.forEach((cell, index) => {
        cell.style.opacity = "0";
        cell.style.transform = "translateY(10px)";

        setTimeout(
            () => {
                cell.style.transition =
                    "opacity 0.35s ease, transform 0.35s ease";
                cell.style.opacity = "1";
                cell.style.transform = "translateY(0)";
            },
            220 + index * 35,
        );
    });
}

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

function setupManagerProfileModal() {
    const profileModal = document.getElementById("profileModal");
    const openProfileModalBtn = document.getElementById("openProfileModal");
    const closeProfileModalBtn = document.getElementById("closeProfileModal");

    if (openProfileModalBtn && profileModal) {
        openProfileModalBtn.addEventListener("click", async () => {
            await populateProfileModal();
            profileModal.classList.add("show");
            document.body.style.overflow = "hidden";
        });
    }

    if (closeProfileModalBtn && profileModal) {
        closeProfileModalBtn.addEventListener("click", () => {
            profileModal.classList.remove("show");
            document.body.style.overflow = "";
        });
    }

    if (profileModal) {
        profileModal.addEventListener("click", (event) => {
            if (event.target === profileModal) {
                profileModal.classList.remove("show");
                document.body.style.overflow = "";
            }
        });
    }
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}
