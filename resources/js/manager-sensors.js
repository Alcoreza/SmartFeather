document.addEventListener("DOMContentLoaded", async () => {
    setupManagerProfileModal();
    setupSensorModals();
    await renderManagerSensorSections();
});

async function renderManagerSensorSections() {
    const mount = document.getElementById("managerSensorSections");
    if (!mount) return;

    try {
        const response = await fetch("/api/manager/sensors");
        const data = await response.json();

        const sections = Array.isArray(data.sections) ? data.sections : [];

        mount.innerHTML = sections
            .map((section, index) => createSectionMarkup(section, index))
            .join("");

        bindManagerSensorFilters();
        bindSensorViewButtons();
        bindThresholdButtons();
        animateSensorSections();
        animateSensorRows();
    } catch (error) {
        console.error("Failed to load manager sensor placeholders.", error);
        mount.innerHTML = `
            <div class="manager-sensor-section">
                <div class="manager-sensor-empty">Sensor placeholder data could not be loaded.</div>
            </div>
        `;
    }
}

function createSectionMarkup(section, index) {
    const rows = Array.isArray(section.items) ? section.items : [];
    const firstItem = rows[0] || {};
    const sensorType = section.sensor_type || section.title;
    const showsResourceColumn = managerSensorShowsResourceColumn(sensorType);
    const columnCount = showsResourceColumn ? 7 : 6;

    return `
        <section
            class="manager-sensor-section"
            data-section-id="${escapeHtml(section.id)}"
            data-sensor-type="${escapeHtml(sensorType)}"
            data-column-count="${columnCount}"
            data-lowest-threshold="${escapeHtml(firstItem.lowest_threshold || "")}"
            data-highest-threshold="${escapeHtml(firstItem.highest_threshold || "")}"
        >
            <div class="manager-sensor-section-head">
                <div class="manager-sensor-section-main">
                    <h2 class="manager-sensor-section-title">${escapeHtml(section.title)}</h2>
                </div>

                <div class="manager-sensor-tools">
                    <select class="manager-sensor-filter" aria-label="Filter sensors by status">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="under maintenance">Under Maintenance</option>
                    </select>

                    <button
                        type="button"
                        class="manager-sensor-threshold-btn"
                        data-open-threshold
                        aria-label="Threshold settings"
                    >
                        ⚙
                    </button>
                </div>
            </div>

            <div class="manager-sensor-table-wrap">
                <table class="manager-sensor-table">
                    <thead>
                        <tr>
                            <th>Sensor Name</th>
                            <th>House</th>
                            <th>Pen</th>
                            ${showsResourceColumn ? "<th>Resource No.</th>" : ""}
                            <th>Value</th> <!-- ✅ NEW -->
                            <th>Status</th>
                            <th>View</th>
                        </tr>
                    </thead>

                    <tbody>
                        ${
                            rows.length
                                ? rows.map(item => `
<tr
    class="${getManagerSensorStatusRowClass(item.status)}"
    data-name="${escapeHtml(item.name)}"
    data-house-number="${escapeHtml(item.house_number)}"
    data-pen-number="${escapeHtml(item.pen_number)}"
    data-status="${escapeHtml(item.status)}"
    data-sensor-type="${escapeHtml(sensorType)}"
>
    <td>${escapeHtml(item.name)}</td>
    <td>${escapeHtml(item.house_number)}</td>
    <td>${escapeHtml(item.pen_number)}</td>
    ${showsResourceColumn ? `<td>${escapeHtml(getManagerSensorResourceNumber(sensorType, item))}</td>` : ""}

    <!-- ✅ VALUE COLUMN -->
    <td>
        <div class="manager-sensor-value">
            ${escapeHtml(item.formatted_value || "No Data")}
        </div>
        <div class="manager-sensor-timestamp">
            ${item.timestamp ? new Date(item.timestamp).toLocaleString() : ""}
        </div>
    </td>

    <td>
        <span class="manager-sensor-status-pill ${getManagerSensorStatusClass(item.status)}">
            ${escapeHtml(item.status || "Active")}
        </span>
    </td>

    <td>
        <button
            type="button"
            class="manager-sensor-view-btn"
            data-open-view
            data-sensor-type="${escapeHtml(sensorType)}"
            data-sensor-name="${escapeHtml(item.name)}"
            data-house-number="${escapeHtml(item.house_number)}"
            data-pen-number="${escapeHtml(item.pen_number)}"
            aria-label="View ${escapeHtml(item.name)}"
        >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        `,
                                      )
                                      .join("")
                                : `
                            <tr>
                                <td colspan="${columnCount}">
                                    <div class="manager-sensor-empty">No placeholder sensors available.</div>
                                </td>
                            </tr>
                        `
                        }
                    </tbody>
                </table>
            </div>
        </section>
    `;
}

function managerSensorShowsResourceColumn(sensorType) {
    return ["Feed Sensor", "Water Sensor"].includes(sensorType);
}

function getManagerSensorStatusClass(status) {
    const normalized = (status || "Active").toLowerCase();

    if (normalized === "inactive") {
        return "inactive";
    }

    return normalized === "under maintenance" ? "maintenance" : "active";
}

function getManagerSensorStatusRowClass(status) {
    const normalized = (status || "").toLowerCase();

    if (normalized === "under maintenance") {
        return "manager-sensor-row-maintenance";
    }

    return normalized === "inactive" ? "manager-sensor-row-inactive" : "";
}

function getManagerSensorResourceNumber(sensorType, item) {
    if (sensorType === "Feed Sensor") {
        return item.feeder_number ? `Feeder ${item.feeder_number}` : "--";
    }

    if (sensorType === "Water Sensor") {
        return item.drinker_number ? `Drinker ${item.drinker_number}` : "--";
    }

    return "--";
}

function bindManagerSensorFilters() {
    document.querySelectorAll(".manager-sensor-section").forEach((section) => {
        const filterSelect = section.querySelector(".manager-sensor-filter");
        const tbody = section.querySelector("tbody");
        if (!filterSelect || !tbody) return;

        const updateNoMatchRow = (visibleCount) => {
            const existing = tbody.querySelector(
                "tr.manager-sensor-filter-empty",
            );
            const columnCount = section.dataset.columnCount || "6";
            if (visibleCount === 0) {
                if (!existing) {
                    const noMatchRow = document.createElement("tr");
                    noMatchRow.className = "manager-sensor-filter-empty";
                    noMatchRow.innerHTML = `
                        <td colspan="${columnCount}">
                            <div class="manager-sensor-empty">No sensors match the filter.</div>
                        </td>
                    `;
                    tbody.appendChild(noMatchRow);
                }
            } else if (existing) {
                existing.remove();
            }
        };

        const filterRows = () => {
            const selectedStatus = filterSelect.value.trim().toLowerCase();
            const rows = Array.from(tbody.querySelectorAll("tr[data-name]"));

            let visibleCount = 0;
            rows.forEach((row) => {
                const status = (row.dataset.status || "").toLowerCase();
                const matches = !selectedStatus || status === selectedStatus;

                row.hidden = !matches;
                if (matches) visibleCount += 1;
            });

            updateNoMatchRow(visibleCount);
        };

        filterSelect.addEventListener("change", filterRows);
        filterRows();
    });
}

function bindSensorViewButtons() {
    document.querySelectorAll("[data-open-view]").forEach((button) => {
        button.addEventListener("click", () => {
            const type = document.getElementById("sensorViewType");
            const name = document.getElementById("sensorViewName");
            const house = document.getElementById("sensorViewHouse");
            const pen = document.getElementById("sensorViewPen");

            if (type) type.value = button.dataset.sensorType || "";
            if (name) name.value = button.dataset.sensorName || "";
            if (house) house.value = button.dataset.houseNumber || "";
            if (pen) pen.value = button.dataset.penNumber || "";

            openSensorModal("sensorViewModal");
        });
    });
}

function bindThresholdButtons() {
    document.querySelectorAll("[data-open-threshold]").forEach((button) => {
        button.addEventListener("click", () => {
            const section = button.closest(".manager-sensor-section");
            if (!section) return;

            const type = document.getElementById("sensorThresholdType");
            const low = document.getElementById("sensorThresholdLow");
            const high = document.getElementById("sensorThresholdHigh");

            if (type) type.value = section.dataset.sensorType || "";
            if (low) low.value = section.dataset.lowestThreshold || "";
            if (high) high.value = section.dataset.highestThreshold || "";

            openSensorModal("sensorThresholdModal");
        });
    });
}

function setupSensorModals() {
    document.querySelectorAll("[data-close-sensor-modal]").forEach((button) => {
        button.addEventListener("click", () => {
            closeSensorModal(button.dataset.closeSensorModal);
        });
    });

    document
        .querySelectorAll(".manager-sensor-modal-backdrop")
        .forEach((modal) => {
            modal.addEventListener("click", (event) => {
                if (event.target === modal) {
                    closeSensorModal(modal.id);
                }
            });
        });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            document
                .querySelectorAll(".manager-sensor-modal-backdrop.show")
                .forEach((modal) => {
                    closeSensorModal(modal.id);
                });
        }
    });
}

function openSensorModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.add("show");
    document.body.style.overflow = "hidden";
}

function closeSensorModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.remove("show");

    const hasOpenSensorModal = document.querySelector(
        ".manager-sensor-modal-backdrop.show",
    );
    const profileModal = document.getElementById("profileModal");
    const profileOpen = profileModal?.classList.contains("show");

    document.body.style.overflow =
        hasOpenSensorModal || profileOpen ? "hidden" : "";
}

function animateSensorSections() {
    document
        .querySelectorAll(".manager-sensor-section")
        .forEach((section, index) => {
            section.style.opacity = "0";
            section.style.transform = "translateY(18px)";

            setTimeout(
                () => {
                    section.style.transition =
                        "opacity 0.45s ease, transform 0.45s ease";
                    section.style.opacity = "1";
                    section.style.transform = "translateY(0)";
                },
                100 + index * 110,
            );
        });
}

function animateSensorRows(scope = document) {
    const rows = scope.querySelectorAll(
        ".manager-sensor-table tbody tr[data-name]",
    );

    rows.forEach((row, index) => {
        row.style.opacity = "0";
        row.style.transform = "translateY(10px)";

        setTimeout(
            () => {
                row.style.transition =
                    "opacity 0.35s ease, transform 0.35s ease";
                row.style.opacity = "1";
                row.style.transform = "translateY(0)";
            },
            220 + index * 55,
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
function bindManagerStatusFilters() {
    document.querySelectorAll(".manager-sensor-section").forEach((section) => {
        const filterSelect = section.querySelector("[data-status-filter]");
        const tbody = section.querySelector("tbody");
        if (!filterSelect || !tbody) return;

        const updateNoMatchRow = (visibleCount) => {
            const existing = tbody.querySelector(
                "tr.manager-sensor-filter-empty",
            );
            const columnCount = section.dataset.columnCount || "6";
            if (visibleCount === 0) {
                if (!existing) {
                    const noMatchRow = document.createElement("tr");
                    noMatchRow.className = "manager-sensor-filter-empty";
                    noMatchRow.innerHTML = `
                        <td colspan="${columnCount}">
                            <div class="manager-sensor-empty">No sensors match the selected status.</div>
                        </td>
                    `;
                    tbody.appendChild(noMatchRow);
                }
            } else if (existing) {
                existing.remove();
            }
        };

        const filterRows = () => {
            const selected = filterSelect.value.trim().toLowerCase();
            const rows = Array.from(tbody.querySelectorAll("tr[data-name]"));

            let visibleCount = 0;
            rows.forEach((row) => {
                const status = (row.dataset.status || "active").toLowerCase();
                const matches = selected === "all" || status === selected;
                row.hidden = !matches;
                if (matches) visibleCount += 1;
            });

            updateNoMatchRow(visibleCount);
        };

        filterSelect.addEventListener("change", filterRows);
        filterRows();
    });
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}
