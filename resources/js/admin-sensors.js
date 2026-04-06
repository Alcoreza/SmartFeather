document.addEventListener("DOMContentLoaded", async () => {
    setupAdminProfileModal();
    setupAdminSensorModals();
    setupAdminSensorAddModal();
    setupAdminSensorEditModal();

    await renderAdminSensorSections();
    await loadAdminSensorFormOptions();

    bindAdminAddButton();
    setupAdminSensorSelectPlaceholderState();
});

async function renderAdminSensorSections() {
    const mount = document.getElementById("adminSensorSections");
    if (!mount) return;

    try {
        const response = await fetch("/api/admin/sensors");
        const data = await response.json();
        const sections = Array.isArray(data.sections) ? data.sections : [];

        mount.innerHTML = sections
            .map((section) => createAdminSectionMarkup(section))
            .join("");

        bindAdminSensorSorts();
        bindAdminViewButtons();
        bindAdminEditButtons();
        bindAdminThresholdButtons();
        bindAdminDeleteButtons();
        animateAdminSensorSections();
        animateAdminSensorRows();
    } catch (error) {
        console.error("Failed to load admin sensor placeholders.", error);
        mount.innerHTML = `
            <div class="admin-sensor-section">
                <div class="admin-sensor-empty">Sensor placeholder data could not be loaded.</div>
            </div>
        `;
    }
}

function createAdminSectionMarkup(section) {
    const rows = Array.isArray(section.items) ? section.items : [];
    const firstItem = rows[0] || {};

    return `
        <section
            class="admin-sensor-section"
            data-section-id="${escapeHtml(section.id)}"
            data-sensor-type="${escapeHtml(section.sensor_type || section.title)}"
            data-lowest-threshold="${escapeHtml(firstItem.lowest_threshold || "")}"
            data-highest-threshold="${escapeHtml(firstItem.highest_threshold || "")}"
        >
            <div class="admin-sensor-section-head">
                <div class="admin-sensor-section-main">
                    <h2 class="admin-sensor-section-title">${escapeHtml(section.title)}</h2>
                </div>

                <div class="admin-sensor-tools">
                    <select class="admin-sensor-sort" data-sort-kind>
                        <option value="name">Name</option>
                        <option value="house_number">House Number</option>
                        <option value="pen_number">Pen Number</option>
                    </select>

                    <button type="button" class="admin-sensor-threshold-btn" data-open-threshold aria-label="Threshold settings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 21v-7"></path>
                            <path d="M4 10V3"></path>
                            <path d="M12 21v-9"></path>
                            <path d="M12 8V3"></path>
                            <path d="M20 21v-5"></path>
                            <path d="M20 12V3"></path>
                            <path d="M1 10h6"></path>
                            <path d="M9 8h6"></path>
                            <path d="M17 12h6"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="admin-sensor-table-wrap">
                <table class="admin-sensor-table">
                    <thead>
                        <tr>
                            <th>Sensor Name</th>
                            <th>House Number</th>
                            <th>Pen Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${
                            rows.length
                                ? rows
                                      .map(
                                          (item) => `
                                    <tr
                                        data-id="${escapeHtml(item.id)}"
                                        data-name="${escapeHtml(item.name)}"
                                        data-house-number="${escapeHtml(item.house_number)}"
                                        data-pen-number="${escapeHtml(item.pen_number)}"
                                        data-sensor-type="${escapeHtml(section.sensor_type || section.title)}"
                                        data-lowest-threshold="${escapeHtml(item.lowest_threshold || "")}"
                                        data-highest-threshold="${escapeHtml(item.highest_threshold || "")}"
                                    >
                                        <td>${escapeHtml(item.name)}</td>
                                        <td>${escapeHtml(item.house_number)}</td>
                                        <td>${escapeHtml(item.pen_number)}</td>
                                        <td>
                                            <div class="admin-sensor-actions">
                                                <button type="button" class="admin-sensor-view-btn" data-open-view-row aria-label="View ${escapeHtml(item.name)}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </button>

                                                <button type="button" class="admin-sensor-edit-btn" data-open-edit-row aria-label="Edit ${escapeHtml(item.name)}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M12 20h9"></path>
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                                                    </svg>
                                                </button>

                                                <button type="button" class="admin-sensor-delete-btn" data-open-delete aria-label="Delete ${escapeHtml(item.name)}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M3 6h18"></path>
                                                        <path d="M8 6V4h8v2"></path>
                                                        <path d="M19 6l-1 14H6L5 6"></path>
                                                        <path d="M10 11v6"></path>
                                                        <path d="M14 11v6"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `,
                                      )
                                      .join("")
                                : `
                                    <tr>
                                        <td colspan="4">
                                            <div class="admin-sensor-empty">No placeholder sensors available.</div>
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

async function loadAdminSensorFormOptions() {
    const sensorTypes = ["Temperature", "Ammonia", "Water", "Feeds"];
    const houses = ["1", "2", "3"];
    const pens = ["1", "2", "3", "4"];

    fillAdminSimpleSelect(
        document.getElementById("adminSensorAddType"),
        sensorTypes,
        "Select sensor type",
    );

    fillAdminSimpleSelect(
        document.getElementById("adminSensorAddHouse"),
        houses,
        "Select house",
    );

    fillAdminSimpleSelect(
        document.getElementById("adminSensorAddPen"),
        pens,
        "Select pen",
    );

    fillAdminSimpleSelect(
        document.getElementById("adminSensorEditType"),
        sensorTypes,
        "Select sensor type",
    );

    fillAdminSimpleSelect(
        document.getElementById("adminSensorEditHouse"),
        houses,
        "Select house",
    );

    fillAdminSimpleSelect(
        document.getElementById("adminSensorEditPen"),
        pens,
        "Select pen",
    );
}

function fillAdminSimpleSelect(select, items, placeholder) {
    if (!select) return;

    select.innerHTML = `
        <option value="">${placeholder}</option>
        ${items.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`).join("")}
    `;
}

function setupAdminSensorSelectPlaceholderState() {
    const selects = document.querySelectorAll(
        ".admin-sensor-select-placeholder",
    );

    selects.forEach((select) => {
        const updateState = () => {
            if (select.value === "") {
                select.classList.remove("has-value");
            } else {
                select.classList.add("has-value");
            }
        };

        updateState();

        if (select._adminSensorPlaceholderHandler) {
            select.removeEventListener(
                "change",
                select._adminSensorPlaceholderHandler,
            );
        }

        select._adminSensorPlaceholderHandler = updateState;
        select.addEventListener("change", updateState);
    });
}

function bindAdminSensorSorts() {
    document.querySelectorAll(".admin-sensor-section").forEach((section) => {
        const sortSelect = section.querySelector("[data-sort-kind]");
        const tbody = section.querySelector("tbody");
        if (!sortSelect || !tbody) return;

        const sortRows = () => {
            const rows = Array.from(tbody.querySelectorAll("tr[data-id]"));
            const kind = sortSelect.value;

            rows.sort((a, b) => {
                if (kind === "name") {
                    return (a.dataset.name || "").localeCompare(
                        b.dataset.name || "",
                    );
                }

                if (kind === "house_number") {
                    return (
                        Number(a.dataset.houseNumber || 0) -
                        Number(b.dataset.houseNumber || 0)
                    );
                }

                return (
                    Number(a.dataset.penNumber || 0) -
                    Number(b.dataset.penNumber || 0)
                );
            });

            rows.forEach((row) => tbody.appendChild(row));
            animateAdminSensorRows(section);
        };

        sortSelect.addEventListener("change", sortRows);
        sortRows();
    });
}

function bindAdminViewButtons() {
    document.querySelectorAll("[data-open-view-row]").forEach((button) => {
        button.addEventListener("click", () => {
            const row = button.closest("tr[data-id]");
            if (!row) return;

            const type = document.getElementById("adminSensorViewType");
            const name = document.getElementById("adminSensorViewName");
            const house = document.getElementById("adminSensorViewHouse");
            const pen = document.getElementById("adminSensorViewPen");

            if (type) type.value = row.dataset.sensorType || "";
            if (name) name.value = row.dataset.name || "";
            if (house) house.value = row.dataset.houseNumber || "";
            if (pen) pen.value = row.dataset.penNumber || "";

            openAdminSensorModal("adminSensorViewModal");
        });
    });
}

function bindAdminEditButtons() {
    document.querySelectorAll("[data-open-edit-row]").forEach((button) => {
        button.addEventListener("click", () => {
            const row = button.closest("tr[data-id]");
            if (!row) return;

            const type = document.getElementById("adminSensorEditType");
            const name = document.getElementById("adminSensorEditName");
            const house = document.getElementById("adminSensorEditHouse");
            const pen = document.getElementById("adminSensorEditPen");

            if (type) type.value = row.dataset.sensorType || "";
            if (name) name.value = row.dataset.name || "";
            if (house) house.value = row.dataset.houseNumber || "";
            if (pen) pen.value = row.dataset.penNumber || "";

            setupAdminSensorSelectPlaceholderState();
            openAdminSensorModal("adminSensorEditModal");
        });
    });
}

function bindAdminThresholdButtons() {
    document.querySelectorAll("[data-open-threshold]").forEach((button) => {
        button.addEventListener("click", () => {
            const section = button.closest(".admin-sensor-section");
            if (!section) return;

            const type = document.getElementById("adminSensorThresholdType");
            const low = document.getElementById("adminSensorThresholdLow");
            const high = document.getElementById("adminSensorThresholdHigh");

            if (type) type.value = section.dataset.sensorType || "";
            if (low) low.value = section.dataset.lowestThreshold || "";
            if (high) high.value = section.dataset.highestThreshold || "";

            openAdminSensorModal("adminSensorThresholdModal");
        });
    });
}

function bindAdminDeleteButtons() {
    document.querySelectorAll("[data-open-delete]").forEach((button) => {
        button.addEventListener("click", () => {
            openAdminSensorModal("adminSensorDeleteModal");
        });
    });
}

function bindAdminAddButton() {
    const openAddButton = document.getElementById("openAddSensorModal");
    if (!openAddButton) return;

    openAddButton.addEventListener("click", () => {
        const form = document.getElementById("adminSensorAddForm");
        if (form) form.reset();

        setupAdminSensorSelectPlaceholderState();
        openAdminSensorModal("adminSensorAddModal");
    });
}

function setupAdminSensorAddModal() {
    const form = document.getElementById("adminSensorAddForm");
    if (!form) return;

    form.addEventListener("submit", (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        console.log("New admin sensor payload:", payload);

        closeAdminSensorModal("adminSensorAddModal");
        form.reset();
        setupAdminSensorSelectPlaceholderState();
    });
}

function setupAdminSensorEditModal() {
    const form = document.getElementById("adminSensorEditForm");
    if (!form) return;

    form.addEventListener("submit", (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        console.log("Edited admin sensor payload:", payload);

        closeAdminSensorModal("adminSensorEditModal");
    });
}

function setupAdminSensorModals() {
    document
        .querySelectorAll("[data-close-admin-sensor-modal]")
        .forEach((button) => {
            button.addEventListener("click", () => {
                closeAdminSensorModal(button.dataset.closeAdminSensorModal);
            });
        });

    document
        .querySelectorAll(".admin-sensor-modal-backdrop")
        .forEach((modal) => {
            modal.addEventListener("click", (event) => {
                if (event.target === modal) {
                    closeAdminSensorModal(modal.id);
                }
            });
        });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            document
                .querySelectorAll(".admin-sensor-modal-backdrop.show")
                .forEach((modal) => {
                    closeAdminSensorModal(modal.id);
                });
        }
    });
}

function openAdminSensorModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.add("show");
    document.body.style.overflow = "hidden";
}

function closeAdminSensorModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.remove("show");

    const hasOpenModal = document.querySelector(
        ".admin-sensor-modal-backdrop.show",
    );
    const profileModal = document.getElementById("profileModal");
    const profileOpen = profileModal?.classList.contains("show");

    document.body.style.overflow = hasOpenModal || profileOpen ? "hidden" : "";
}

function animateAdminSensorSections() {
    document
        .querySelectorAll(".admin-sensor-section")
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

function animateAdminSensorRows(scope = document) {
    const rows = scope.querySelectorAll(
        ".admin-sensor-table tbody tr[data-id]",
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

function setupAdminProfileModal() {
    const profileModal = document.getElementById("adminProfileModal");
    const openProfileModalBtn = document.getElementById("openProfileModal");
    const closeProfileModalBtn = document.getElementById(
        "closeAdminProfileModal",
    );

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
