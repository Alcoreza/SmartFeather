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

function setupManagerProfileModal() {
    const profileModal = document.getElementById("profileModal");
    const openProfileModalBtn = document.getElementById("openProfileModal");
    const closeProfileModalBtn = document.getElementById("closeProfileModal");

    if (openProfileModalBtn && profileModal) {
        openProfileModalBtn.addEventListener("click", () => {
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
