document.addEventListener("DOMContentLoaded", async () => {
    setupAdminProfileModal();
    setupAdminSensorModals();
    setupAdminSensorAddModal();
    setupAdminSensorEditModal();
    setupAdminSensorRequiredFieldsValidation();

    await renderAdminSensorSections();
    await loadAdminSensorFormOptions();

    bindAdminAddButton();
    setupAdminSensorSelectPlaceholderState();
});

const adminSensorAddRequiredFields = [
    { id: "adminSensorAddType", label: "Sensor Type" },
    { id: "adminSensorAddName", label: "Sensor Name" },
];

let shouldTrackAdminSensorRequiredHighlights = false;
let pendingAdminSensorAddPayload = null;

const adminSensorSectionDisplayOrder = {
    "Ammonia Sensor": 1,
    "Temperature Sensor": 2,
    "Feed Sensor": 3,
    "Water Sensor": 4,
};

function sortAdminSensorSections(sections) {
    return sections
        .map((section, index) => ({ section, index }))
        .sort((left, right) => {
            const leftType = left.section.sensor_type || left.section.title || "";
            const rightType = right.section.sensor_type || right.section.title || "";
            const leftOrder = adminSensorSectionDisplayOrder[leftType] ?? 999;
            const rightOrder = adminSensorSectionDisplayOrder[rightType] ?? 999;

            return leftOrder === rightOrder
                ? left.index - right.index
                : leftOrder - rightOrder;
        })
        .map(({ section }) => section);
}

function showAdminSensorConfirmModal(message, title = "Confirm Save", confirmText = "Confirm") {
    return new Promise((resolve) => {
        const existing = document.getElementById("adminSensorGenericConfirmModal");
        existing?.remove();

        const modal = document.createElement("div");
        modal.className = "admin-sensor-modal-backdrop confirm-modal-top show";
        modal.id = "adminSensorGenericConfirmModal";
        modal.innerHTML = `
            <div class="admin-sensor-modal-card admin-sensor-delete-card">
                <div class="admin-sensor-modal-header center">
                    <h2></h2>
                    <div class="admin-sensor-header-line"></div>
                </div>
                <div class="admin-sensor-modal-body">
                    <p class="admin-sensor-delete-text"></p>
                    <div class="admin-sensor-modal-actions">
                        <button type="button" class="admin-sensor-btn close" data-confirm-cancel>Cancel</button>
                        <button type="button" class="admin-sensor-btn save" data-confirm-ok></button>
                    </div>
                </div>
            </div>
        `;

        modal.querySelector("h2").textContent = title;
        modal.querySelector("p").textContent = message;
        modal.querySelector("[data-confirm-ok]").textContent = confirmText;

        const close = (confirmed) => {
            modal.remove();
            resolve(confirmed);
        };

        modal.querySelector("[data-confirm-cancel]").addEventListener("click", () => close(false));
        modal.querySelector("[data-confirm-ok]").addEventListener("click", () => close(true));
        modal.addEventListener("click", (event) => {
            if (event.target === modal) close(false);
        });

        document.body.appendChild(modal);
    });
}

function showAdminSensorNoticeModal(message, title = "Notice") {
    return new Promise((resolve) => {
        document.getElementById("adminSensorGenericNoticeModal")?.remove();

        const modal = document.createElement("div");
        modal.className = "admin-sensor-modal-backdrop confirm-modal-top show";
        modal.id = "adminSensorGenericNoticeModal";
        modal.innerHTML = `
            <div class="admin-sensor-modal-card admin-sensor-delete-card">
                <div class="admin-sensor-modal-header center">
                    <h2></h2>
                    <div class="admin-sensor-header-line"></div>
                </div>
                <div class="admin-sensor-modal-body">
                    <p class="admin-sensor-delete-text"></p>
                    <div class="admin-sensor-modal-actions">
                        <button type="button" class="admin-sensor-btn save" data-notice-ok>OK</button>
                    </div>
                </div>
            </div>
        `;

        modal.querySelector("h2").textContent = title;
        modal.querySelector("p").textContent = message;

        const close = () => {
            modal.remove();
            resolve();
        };

        modal.querySelector("[data-notice-ok]").addEventListener("click", close);
        modal.addEventListener("click", (event) => {
            if (event.target === modal) close();
        });

        document.body.appendChild(modal);
    });
}

async function renderAdminSensorSections() {
    const mount = document.getElementById("adminSensorSections");
    if (!mount) return;

    try {
        const response = await fetch("/api/admin/sensors");
        const data = await response.json();
        const sections = sortAdminSensorSections(
            Array.isArray(data.sections) ? data.sections : [],
        );

        mount.innerHTML = sections
            .map((section) => createAdminSectionMarkup(section))
            .join("");

        bindAdminStatusFilters();
        bindAdminToggleStatusButtons();
        bindAdminStatusConfirmButton();
        bindAdminViewButtons();
        bindAdminEditButtons();
        bindAdminThresholdButtons();
        bindAdminDeleteButtons();
        bindAdminDeleteConfirmButton();
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
    const sensorType = section.sensor_type || section.title;
    const showsResourceColumn = adminSensorShowsResourceColumn(sensorType);
    const columnCount = showsResourceColumn ? 7 : 6;

    return `
        <section
            class="admin-sensor-section"
            data-section-id="${escapeHtml(section.id)}"
            data-sensor-type="${escapeHtml(sensorType)}"
            data-column-count="${columnCount}"
            data-lowest-threshold="${escapeHtml(firstItem.lowest_threshold || "")}"
            data-highest-threshold="${escapeHtml(firstItem.highest_threshold || "")}"
        >
            <div class="admin-sensor-section-head">
                <div class="admin-sensor-section-main">
                    <h2 class="admin-sensor-section-title">${escapeHtml(section.title)}</h2>
                </div>

                <div class="admin-sensor-tools">
                    <select class="admin-sensor-status-filter" data-status-filter>
                        <option value="all">All Sensors</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="under maintenance">Under Maintenance</option>
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
                            <th>House</th>
                            <th>Pen Number</th>
                            ${showsResourceColumn ? "<th>Resource No.</th>" : ""}
                            <th>Value</th>
                            <th>Status</th>
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
                                        class="${getAdminSensorStatusRowClass(item.status)}"
                                        data-id="${escapeHtml(item.id)}"
                                        data-name="${escapeHtml(item.name)}"
                                        data-house-number="${escapeHtml(item.house_number)}"
                                        data-pen-number="${escapeHtml(item.pen_number)}"
                                        data-house-id="${escapeHtml(item.house_id ?? "")}"
                                        data-pen-id="${escapeHtml(item.pen_id ?? "")}"
                                        data-feeder-number="${escapeHtml(item.feeder_number ?? "")}"
                                        data-drinker-number="${escapeHtml(item.drinker_number ?? "")}"
                                        data-sensor-type="${escapeHtml(sensorType)}"
                                        data-lowest-threshold="${escapeHtml(item.lowest_threshold || "")}"
                                        data-highest-threshold="${escapeHtml(item.highest_threshold || "")}"
                                        data-status="${escapeHtml(item.status || "Active")}"
                                    >
                                        <td>${escapeHtml(item.name)}</td>
                                        <td>${escapeHtml(item.house_number)}</td>
                                        <td>${escapeHtml(item.pen_number)}</td>
                                        ${showsResourceColumn ? `<td>${escapeHtml(getAdminSensorResourceNumber(sensorType, item))}</td>` : ""}
                                        <td>
                                            <div class="admin-sensor-value">
                                                ${escapeHtml(item.formatted_value || "No Data")}
                                            </div>
                                            <div class="admin-sensor-timestamp">
                                                ${item.timestamp ? new Date(item.timestamp).toLocaleString() : ""}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="admin-sensor-status-pill ${getAdminSensorStatusClass(item.status)}">
                                                ${escapeHtml(item.status || "Active")}
                                            </span>
                                        </td>
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

                                                ${getAdminSensorStatusActionMarkup(item)}

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
                                        <td colspan="${columnCount}">
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

function adminSensorShowsResourceColumn(sensorType) {
    return ["Feed Sensor", "Water Sensor"].includes(sensorType);
}

function getAdminSensorStatusActionMarkup(item) {
    const status = (item.status || "Active").toLowerCase();

    if (status === "inactive") {
        return "";
    }

    const isMaintenance = status === "under maintenance";
    const actionLabel = isMaintenance
        ? `Mark ${escapeHtml(item.name)} as active`
        : `Mark ${escapeHtml(item.name)} as under maintenance`;
    const icon = isMaintenance
        ? `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M8 12.5l2.5 2.5L16 9.5"></path>
            </svg>
        `
        : `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M9.5 9.5v5"></path>
                <path d="M14.5 9.5v5"></path>
            </svg>
        `;

    const maintenanceButton = `
        <button
            type="button"
            class="admin-sensor-toggle-btn ${isMaintenance ? "activate" : "maintenance"}"
            data-toggle-status
            aria-label="${actionLabel}"
        >
            ${icon}
        </button>
    `;
    const inactiveButton = `
        <button
            type="button"
            class="admin-sensor-toggle-btn inactive"
            data-toggle-status
            data-next-status="Inactive"
            aria-label="Make ${escapeHtml(item.name)} inactive"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v10"></path>
                <path d="M18.4 6.6a9 9 0 1 1-12.8 0"></path>
            </svg>
        </button>
    `;

    return `${maintenanceButton}${inactiveButton}`;
}

function getAdminSensorStatusClass(status) {
    const normalized = (status || "Active").toLowerCase();

    if (normalized === "inactive") {
        return "inactive";
    }

    return normalized === "under maintenance" ? "maintenance" : "active";
}

function getAdminSensorStatusRowClass(status) {
    const normalized = (status || "").toLowerCase();

    if (normalized === "under maintenance") {
        return "admin-sensor-row-maintenance";
    }

    return normalized === "inactive" ? "admin-sensor-row-inactive" : "";
}

function getAdminSensorResourceNumber(sensorType, item) {
    if (sensorType === "Feed Sensor") {
        return item.feeder_number ? `Feeder ${item.feeder_number}` : "--";
    }

    if (sensorType === "Water Sensor") {
        return item.drinker_number ? `Drinker ${item.drinker_number}` : "--";
    }

    return "--";
}

async function loadAdminSensorFormOptions() {
    try {
        const response = await fetch("/api/admin/sensors/form-options");
        const data = await response.json();

        fillAdminSimpleSelect(
            document.getElementById("adminSensorAddType"),
            data.sensor_types || [],
            "Select sensor type",
        );

        const addHouseSelect = document.getElementById("adminSensorAddHouse");
        const addPenSelect = document.getElementById("adminSensorAddPen");

        fillAdminSimpleSelect(addHouseSelect, data.houses || [], "No house (inactive)");
        fillAdminSimpleSelect(addPenSelect, [], "No pen (inactive)");
        fillAdminSimpleSelect(
            document.getElementById("adminSensorAddFeederNumber"),
            [],
            "Select house and pen first",
        );
        fillAdminSimpleSelect(
            document.getElementById("adminSensorAddDrinkerNumber"),
            [],
            "Select house and pen first",
        );

        const editHouseSelect = document.getElementById("adminSensorEditHouse");
        const editPenSelect = document.getElementById("adminSensorEditPen");

        fillAdminSimpleSelect(
            document.getElementById("adminSensorEditType"),
            data.sensor_types || [],
            "Select sensor type",
        );

        fillAdminSimpleSelect(editHouseSelect, data.houses || [], "No house (inactive)");
        fillAdminSimpleSelect(editPenSelect, [], "No pen (inactive)");
        resetAdminSensorEditResourceOptions();

        if (addHouseSelect) {
            addHouseSelect.addEventListener("change", async (event) => {
                await loadAdminPensForHouse(event.target.value, addPenSelect);
                fillAdminSimpleSelect(
                    document.getElementById("adminSensorAddFeederNumber"),
                    [],
                    "Select pen first",
                );
                fillAdminSimpleSelect(
                    document.getElementById("adminSensorAddDrinkerNumber"),
                    [],
                    "Select pen first",
                );
                setupAdminSensorSelectPlaceholderState();
                syncAdminSensorRequiredFieldHighlight(
                    adminSensorAddRequiredFields.find((field) => field.id === "adminSensorAddPen"),
                );
            });
        }

        if (addPenSelect) {
            addPenSelect.addEventListener("change", async () => {
                await syncAdminAddFeederOptions();
                await syncAdminAddDrinkerOptions();
                setupAdminSensorSelectPlaceholderState();
            });
        }

        document
            .getElementById("adminSensorAddType")
            ?.addEventListener("change", async () => {
                syncAdminSensorResourceFields("add");
                await syncAdminAddFeederOptions();
                await syncAdminAddDrinkerOptions();
                setupAdminSensorSelectPlaceholderState();
            });

        if (editHouseSelect) {
            editHouseSelect.addEventListener("change", async (event) => {
                resetAdminSensorEditResourceOptions();
                await loadAdminPensForHouse(event.target.value, editPenSelect);
                if (editPenSelect) {
                    editPenSelect.value = "";
                }
                setupAdminSensorSelectPlaceholderState();
            });
        }

        if (editPenSelect) {
            editPenSelect.addEventListener("change", async () => {
                resetAdminSensorEditResourceOptions();
                await syncAdminEditFeederOptions();
                await syncAdminEditDrinkerOptions();
                setupAdminSensorSelectPlaceholderState();
            });
        }

        document
            .getElementById("adminSensorEditType")
            ?.addEventListener("change", async () => {
                syncAdminSensorResourceFields("edit");
                resetAdminSensorEditResourceOptions();
                await syncAdminEditFeederOptions();
                await syncAdminEditDrinkerOptions();
                setupAdminSensorSelectPlaceholderState();
            });

        syncAdminSensorResourceFields("add");
        syncAdminSensorResourceFields("edit");
    } catch (error) {
        console.error("Failed to load admin sensor form options.", error);
    }
}

function resetAdminSensorEditResourceOptions() {
    fillAdminSimpleSelect(
        document.getElementById("adminSensorEditFeederNumber"),
        [],
        "Select pen first",
    );
    fillAdminSimpleSelect(
        document.getElementById("adminSensorEditDrinkerNumber"),
        [],
        "Select pen first",
    );
}

async function syncAdminAddFeederOptions() {
    const type = document.getElementById("adminSensorAddType")?.value || "";
    const houseId = document.getElementById("adminSensorAddHouse")?.value || "";
    const penId = document.getElementById("adminSensorAddPen")?.value || "";
    const feederSelect = document.getElementById("adminSensorAddFeederNumber");

    if (!feederSelect || type !== "Feed Sensor") {
        if (feederSelect) {
            feederSelect.value = "";
        }
        return;
    }

    if (!houseId || !penId) {
        fillAdminSimpleSelect(feederSelect, [], "Select house and pen first");
        return;
    }

    await loadAdminAvailableFeedersForPen(houseId, penId, feederSelect);
}

async function syncAdminAddDrinkerOptions() {
    const type = document.getElementById("adminSensorAddType")?.value || "";
    const houseId = document.getElementById("adminSensorAddHouse")?.value || "";
    const penId = document.getElementById("adminSensorAddPen")?.value || "";
    const drinkerSelect = document.getElementById("adminSensorAddDrinkerNumber");

    if (!drinkerSelect || type !== "Water Sensor") {
        if (drinkerSelect) {
            drinkerSelect.value = "";
        }
        return;
    }

    if (!houseId || !penId) {
        fillAdminSimpleSelect(drinkerSelect, [], "Select house and pen first");
        return;
    }

    await loadAdminAvailableDrinkersForPen(houseId, penId, drinkerSelect);
}

async function syncAdminEditFeederOptions(selectedValue = "") {
    const type = document.getElementById("adminSensorEditType")?.value || "";
    const houseId = document.getElementById("adminSensorEditHouse")?.value || "";
    const penId = document.getElementById("adminSensorEditPen")?.value || "";
    const sensorId = document.getElementById("adminSensorEditId")?.value || "";
    const feederSelect = document.getElementById("adminSensorEditFeederNumber");

    if (!feederSelect || type !== "Feed Sensor") {
        if (feederSelect) {
            feederSelect.value = "";
        }
        return;
    }

    if (!houseId || !penId) {
        fillAdminSimpleSelect(feederSelect, [], "Select house and pen first");
        return;
    }

    await loadAdminAvailableFeedersForPen(houseId, penId, feederSelect, sensorId);
    if (selectedValue) {
        feederSelect.value = selectedValue;
    }
}

async function syncAdminEditDrinkerOptions(selectedValue = "") {
    const type = document.getElementById("adminSensorEditType")?.value || "";
    const houseId = document.getElementById("adminSensorEditHouse")?.value || "";
    const penId = document.getElementById("adminSensorEditPen")?.value || "";
    const sensorId = document.getElementById("adminSensorEditId")?.value || "";
    const drinkerSelect = document.getElementById("adminSensorEditDrinkerNumber");

    if (!drinkerSelect || type !== "Water Sensor") {
        if (drinkerSelect) {
            drinkerSelect.value = "";
        }
        return;
    }

    if (!houseId || !penId) {
        fillAdminSimpleSelect(drinkerSelect, [], "Select house and pen first");
        return;
    }

    await loadAdminAvailableDrinkersForPen(houseId, penId, drinkerSelect, sensorId);
    if (selectedValue) {
        drinkerSelect.value = selectedValue;
    }
}

function syncAdminSensorResourceFields(mode) {
    const prefix = mode === "edit" ? "adminSensorEdit" : "adminSensorAdd";
    const type = document.getElementById(`${prefix}Type`)?.value || "";
    const feederInput = document.getElementById(`${prefix}FeederNumber`);
    const drinkerInput = document.getElementById(`${prefix}DrinkerNumber`);
    const feederField = feederInput?.closest(".admin-sensor-form-field");
    const drinkerField = drinkerInput?.closest(".admin-sensor-form-field");
    const isFeedSensor = type === "Feed Sensor";
    const isWaterSensor = type === "Water Sensor";

    if (feederField) {
        feederField.hidden = !isFeedSensor;
    }

    if (drinkerField) {
        drinkerField.hidden = !isWaterSensor;
    }

    if (!isFeedSensor && feederInput) {
        feederInput.value = "";
    }

    if (!isWaterSensor && drinkerInput) {
        drinkerInput.value = "";
    }
}

function normalizeAdminSensorResourcePayload(payload) {
    const hasHouse = Boolean(payload.house_houseid);
    const hasPen = Boolean(payload.pen_penid);

    if (!hasHouse || !hasPen) {
        payload.feeder_number = "";
        payload.drinker_number = "";
    }

    if (payload.sensor_type !== "Feed Sensor") {
        payload.feeder_number = "";
    }

    if (payload.sensor_type !== "Water Sensor") {
        payload.drinker_number = "";
    }
}

async function loadAdminPensForHouse(houseId, penSelect) {
    if (!penSelect) return;

    if (!houseId) {
        fillAdminSimpleSelect(penSelect, [], "No pen (inactive)");
        return;
    }

    try {
        const response = await fetch(`/api/admin/sensors/houses/${houseId}/pens`);
        const data = await response.json();
        fillAdminSimpleSelect(penSelect, data.pens || [], "No pen (inactive)");
    } catch (error) {
        console.error("Failed to load pens for selected house.", error);
        fillAdminSimpleSelect(penSelect, [], "No pen (inactive)");
    }
}

async function loadAdminAvailableFeedersForPen(houseId, penId, feederSelect, ignoreSensorId = "") {
    if (!feederSelect) return;

    try {
        const ignoreQuery = ignoreSensorId
            ? `&ignore_sensor_id=${encodeURIComponent(ignoreSensorId)}`
            : "";
        const response = await fetch(
            `/api/admin/sensors/pens/${penId}/available-feeders?house_id=${encodeURIComponent(houseId)}${ignoreQuery}`,
        );
        const data = await response.json();
        const feeders = data.feeders || [];
        fillAdminSimpleSelect(
            feederSelect,
            feeders,
            feeders.length ? "Select feeder" : "No available feeders",
        );
    } catch (error) {
        console.error("Failed to load available feeders.", error);
        fillAdminSimpleSelect(feederSelect, [], "No available feeders");
    }
}

async function loadAdminAvailableDrinkersForPen(houseId, penId, drinkerSelect, ignoreSensorId = "") {
    if (!drinkerSelect) return;

    try {
        const ignoreQuery = ignoreSensorId
            ? `&ignore_sensor_id=${encodeURIComponent(ignoreSensorId)}`
            : "";
        const response = await fetch(
            `/api/admin/sensors/pens/${penId}/available-drinkers?house_id=${encodeURIComponent(houseId)}${ignoreQuery}`,
        );
        const data = await response.json();
        const drinkers = data.drinkers || [];
        fillAdminSimpleSelect(
            drinkerSelect,
            drinkers,
            drinkers.length ? "Select drinker" : "No available drinkers",
        );
    } catch (error) {
        console.error("Failed to load available drinkers.", error);
        fillAdminSimpleSelect(drinkerSelect, [], "No available drinkers");
    }
}

function fillAdminSimpleSelect(select, items, placeholder) {
    if (!select) return;

    select.innerHTML = `
        <option value="">${escapeHtml(placeholder)}</option>
        ${items
            .map((item) => {
                if (item && typeof item === "object") {
                    return `<option value="${escapeHtml(item.value)}">${escapeHtml(item.label)}</option>`;
                }
                return `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`;
            })
            .join("")}
    `;
}

function getCsrfToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    return tokenElement?.getAttribute("content") || "";
}

async function apiRequest(url, method = "GET", data = null) {
    const options = {
        method,
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    };

    if (["POST", "PUT", "PATCH", "DELETE"].includes(method.toUpperCase())) {
        options.headers["X-CSRF-TOKEN"] = getCsrfToken();
    }

    if (data) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    if (!response.ok) {
        const body = await response.text();
        let message = body;

        try {
            const json = JSON.parse(body);
            message =
                Object.values(json.errors || {})
                    .flat()
                    .filter(Boolean)
                    .join(" ") ||
                json.message ||
                body;
        } catch (error) {
            message = body;
        }

        throw new Error(message || `API request failed: ${response.status}`);
    }

    return response.json();
}

function setupAdminSensorSelectPlaceholderState() {
    const selects = document.querySelectorAll(".admin-sensor-select-placeholder");

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
            select.removeEventListener("change", select._adminSensorPlaceholderHandler);
        }

        select._adminSensorPlaceholderHandler = updateState;
        select.addEventListener("change", updateState);
    });
}

function isAdminSensorRequiredFieldEmpty(field) {
    if (!field) return false;

    return !(document.getElementById(field.id)?.value || "").trim();
}

function clearAdminSensorRequiredFieldHighlight(input) {
    input?.closest(".admin-sensor-form-field")?.classList.remove("has-error");
}

function syncAdminSensorRequiredFieldHighlight(field) {
    if (!field) return;

    const input = document.getElementById(field.id);
    if (!input) return;

    if (
        shouldTrackAdminSensorRequiredHighlights &&
        isAdminSensorRequiredFieldEmpty(field)
    ) {
        input.closest(".admin-sensor-form-field")?.classList.add("has-error");
        return;
    }

    clearAdminSensorRequiredFieldHighlight(input);

    const hasErrors = document.querySelector(
        "#adminSensorAddForm .admin-sensor-form-field.has-error",
    );
    if (!hasErrors) {
        shouldTrackAdminSensorRequiredHighlights = false;
        clearAdminSensorAddFormError();
    }
}

function clearAdminSensorRequiredFieldHighlights() {
    shouldTrackAdminSensorRequiredHighlights = false;

    document
        .querySelectorAll("#adminSensorAddForm .admin-sensor-form-field.has-error")
        .forEach((field) => field.classList.remove("has-error"));
}

function getMissingAdminSensorAddRequiredFields() {
    return adminSensorAddRequiredFields.filter(isAdminSensorRequiredFieldEmpty);
}

function markMissingAdminSensorRequiredFields(missingFields) {
    clearAdminSensorRequiredFieldHighlights();
    shouldTrackAdminSensorRequiredHighlights = true;

    missingFields.forEach((field) => {
        document
            .getElementById(field.id)
            ?.closest(".admin-sensor-form-field")
            ?.classList.add("has-error");
    });
}

function showAdminSensorRequiredFieldsError() {
    const missingFields = getMissingAdminSensorAddRequiredFields();

    if (!missingFields.length) {
        clearAdminSensorRequiredFieldHighlights();
        clearAdminSensorAddFormError();
        return false;
    }

    markMissingAdminSensorRequiredFields(missingFields);

    showAdminSensorAddFormError();
    setTimeout(() => document.getElementById(missingFields[0].id)?.focus(), 250);

    return true;
}

function showAdminSensorAddFormError(message = "Please fill in the required fields.") {
    const formError = document.getElementById("adminSensorAddFormError");
    if (!formError) return;

    formError.textContent = message;
    formError.classList.add("show");
    formError.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function clearAdminSensorAddFormError() {
    const formError = document.getElementById("adminSensorAddFormError");

    formError?.classList.remove("show");
    if (formError) {
        formError.textContent = "";
    }
}

function showAdminSensorEditFormError(message = "Failed to update sensor.") {
    const formError = document.getElementById("adminSensorEditFormError");
    if (!formError) return;

    formError.textContent = message;
    formError.classList.add("show");
    formError.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function clearAdminSensorEditFormError() {
    const formError = document.getElementById("adminSensorEditFormError");

    formError?.classList.remove("show");
    if (formError) {
        formError.textContent = "";
    }
}

function setupAdminSensorRequiredFieldsValidation() {
    adminSensorAddRequiredFields.forEach((field) => {
        const input = document.getElementById(field.id);

        input?.addEventListener("input", () => syncAdminSensorRequiredFieldHighlight(field));
        input?.addEventListener("change", () => syncAdminSensorRequiredFieldHighlight(field));
    });
}

function bindAdminSensorSorts() {
    document.querySelectorAll(".admin-sensor-section").forEach((section) => {
        const sortSelect = section.querySelector("[data-sort-kind]");
        const tbody = section.querySelector("tbody");
        if (!sortSelect || !tbody) return;

        const sortRows = () => {
            const rows = Array.from(tbody.querySelectorAll("tr[data-id]")).filter(
                (row) => !row.hidden,
            );
            const kind = sortSelect.value;

            rows.sort((a, b) => {
                if (kind === "name") {
                    return (a.dataset.name || "").localeCompare(b.dataset.name || "");
                }
                if (kind === "house_number") {
                    return Number(a.dataset.houseNumber || 0) - Number(b.dataset.houseNumber || 0);
                }
                return Number(a.dataset.penNumber || 0) - Number(b.dataset.penNumber || 0);
            });

            rows.forEach((row) => tbody.appendChild(row));
            animateAdminSensorRows(section);
        };

        sortSelect.addEventListener("change", sortRows);
        sortRows();
    });
}

function bindAdminSensorFilters() {
    document.querySelectorAll(".admin-sensor-section").forEach((section) => {
        const filterInput = section.querySelector(".admin-sensor-filter");
        const tbody = section.querySelector("tbody");
        if (!filterInput || !tbody) return;

        const updateNoMatchRow = (visibleCount) => {
            const existing = tbody.querySelector("tr.admin-sensor-filter-empty");
            const columnCount = section.dataset.columnCount || "6";
            if (visibleCount === 0) {
                if (!existing) {
                    const noMatchRow = document.createElement("tr");
                    noMatchRow.className = "admin-sensor-filter-empty";
                    noMatchRow.innerHTML = `
                        <td colspan="${columnCount}">
                            <div class="admin-sensor-empty">No sensors match the filter.</div>
                        </td>
                    `;
                    tbody.appendChild(noMatchRow);
                }
            } else if (existing) {
                existing.remove();
            }
        };

        const filterRows = () => {
            const query = filterInput.value.trim().toLowerCase();
            const rows = Array.from(tbody.querySelectorAll("tr[data-id]"));

            let visibleCount = 0;
            rows.forEach((row) => {
                const name = (row.dataset.name || "").toLowerCase();
                const house = (row.dataset.houseNumber || "").toLowerCase();
                const pen = (row.dataset.penNumber || "").toLowerCase();
                const status = (row.dataset.status || "").toLowerCase();
                const matches =
                    !query ||
                    name.includes(query) ||
                    house.includes(query) ||
                    pen.includes(query) ||
                    status.includes(query);

                row.hidden = !matches;
                if (matches) visibleCount += 1;
            });

            updateNoMatchRow(visibleCount);
        };

        filterInput.addEventListener("input", filterRows);
        filterRows();
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
        button.addEventListener("click", async () => {
            const row = button.closest("tr[data-id]");
            if (!row) return;

            const type = document.getElementById("adminSensorEditType");
            const name = document.getElementById("adminSensorEditName");
            const house = document.getElementById("adminSensorEditHouse");
            const pen = document.getElementById("adminSensorEditPen");
            const id = document.getElementById("adminSensorEditId");

            if (type) type.value = row.dataset.sensorType || "";
            if (name) name.value = row.dataset.name || "";
            if (house) house.value = row.dataset.houseId || row.dataset.houseNumber || "";
            if (id) id.value = row.dataset.id || "";

            clearAdminSensorEditFormError();
            syncAdminSensorResourceFields("edit");
            resetAdminSensorEditResourceOptions();

            if (house && house.value) {
                await loadAdminPensForHouse(house.value, pen);
                if (pen) {
                    pen.value = row.dataset.penId || row.dataset.penNumber || "";
                }
            } else if (pen) {
                pen.value = "";
            }

            await syncAdminEditFeederOptions(row.dataset.feederNumber || "");
            await syncAdminEditDrinkerOptions(row.dataset.drinkerNumber || "");
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
            const typeDisplay = document.getElementById("adminSensorThresholdTypeDisplay");
            const low = document.getElementById("adminSensorThresholdLow");
            const high = document.getElementById("adminSensorThresholdHigh");

            if (type) type.value = section.dataset.sensorType || "";
            if (typeDisplay) typeDisplay.value = section.dataset.sensorType || "";
            if (low) low.value = section.dataset.lowestThreshold || "";
            if (high) high.value = section.dataset.highestThreshold || "";

            openAdminSensorModal("adminSensorThresholdModal");
        });
    });

    const saveButton = document.getElementById("adminSensorThresholdSave");
    if (saveButton) {
        saveButton.addEventListener("click", async () => {
            const type = document.getElementById("adminSensorThresholdType");
            const low = document.getElementById("adminSensorThresholdLow");
            const high = document.getElementById("adminSensorThresholdHigh");

            if (!type || !type.value) {
                await showAdminSensorNoticeModal("Sensor type is required.", "Missing Sensor Type");
                return;
            }

            const lowestThreshold = low && low.value.trim() ? parseInt(low.value) : null;
            const highestThreshold = high && high.value.trim() ? parseInt(high.value) : null;

            if (
                (low && low.value.trim() && isNaN(lowestThreshold)) ||
                (high && high.value.trim() && isNaN(highestThreshold))
            ) {
                await showAdminSensorNoticeModal("Threshold values must be valid numbers.", "Invalid Threshold");
                return;
            }

            const confirmed = await showAdminSensorConfirmModal(
                "Save these sensor threshold changes?",
                "Confirm Threshold",
            );

            if (!confirmed) {
                return;
            }

            try {
                const payload = {
                    sensor_type: type.value,
                    lowest_threshold: lowestThreshold,
                    highest_threshold: highestThreshold,
                };

                await apiRequest("/api/admin/sensors/thresholds", "PUT", payload);
                closeAdminSensorModal("adminSensorThresholdModal");
                await renderAdminSensorSections();
            } catch (error) {
                console.error("Failed to save sensor thresholds.", error);
                await showAdminSensorNoticeModal(`Failed to save sensor thresholds: ${error.message}`, "Unable to Save Thresholds");
            }
        });
    }
}

function bindAdminDeleteButtons() {
    document.querySelectorAll("[data-open-delete]").forEach((button) => {
        button.addEventListener("click", () => {
            const row = button.closest("tr[data-id]");
            const modal = document.getElementById("adminSensorDeleteModal");
            const deleteIdInput = document.getElementById("adminSensorDeleteId");
            const deleteText = document.getElementById("adminSensorDeleteText");

            if (row && modal && deleteIdInput) {
                deleteIdInput.value = row.dataset.id || "";
            }

            if (deleteText) {
                deleteText.textContent = `Are you sure you want to delete ${row?.dataset.name || "this sensor"}?`;
            }

            clearAdminSensorDeleteError();
            openAdminSensorModal("adminSensorDeleteModal");
        });
    });
}

function bindAdminDeleteConfirmButton() {
    const deleteButton = document.querySelector(
        "#adminSensorDeleteModal .admin-sensor-btn.delete",
    );
    if (!deleteButton) return;
    if (deleteButton._adminSensorDeleteHandlerSet) return;
    deleteButton._adminSensorDeleteHandlerSet = true;

    deleteButton.addEventListener("click", async () => {
        const deleteIdInput = document.getElementById("adminSensorDeleteId");
        const sensorId = deleteIdInput?.value;

        if (!sensorId) {
            console.error("No sensor selected for deletion");
            return;
        }

        try {
            deleteButton.disabled = true;
            clearAdminSensorDeleteError();
            await apiRequest(`/api/admin/sensors/${sensorId}`, "DELETE");
            closeAdminSensorModal("adminSensorDeleteModal");
            if (deleteIdInput) deleteIdInput.value = "";
            await renderAdminSensorSections();
        } catch (error) {
            showAdminSensorDeleteError(error.message || "Failed to delete sensor.");
            console.error("Failed to delete sensor.", error);
        } finally {
            deleteButton.disabled = false;
        }
    });
}

function showAdminSensorDeleteError(message = "Failed to delete sensor.") {
    const formError = document.getElementById("adminSensorDeleteError");
    if (!formError) return;

    formError.textContent = message;
    formError.classList.add("show");
}

function clearAdminSensorDeleteError() {
    const formError = document.getElementById("adminSensorDeleteError");

    formError?.classList.remove("show");
    if (formError) {
        formError.textContent = "";
    }
}

function bindAdminAddButton() {
    const openAddButton = document.getElementById("openAddSensorModal");
    if (!openAddButton) return;

    openAddButton.addEventListener("click", () => {
        const form = document.getElementById("adminSensorAddForm");
        if (form) form.reset();

        fillAdminSimpleSelect(document.getElementById("adminSensorAddPen"), [], "No pen (inactive)");
        fillAdminSimpleSelect(
            document.getElementById("adminSensorAddFeederNumber"),
            [],
            "Select house and pen first",
        );
        fillAdminSimpleSelect(
            document.getElementById("adminSensorAddDrinkerNumber"),
            [],
            "Select house and pen first",
        );
        clearAdminSensorRequiredFieldHighlights();
        clearAdminSensorAddFormError();
        setupAdminSensorSelectPlaceholderState();
        syncAdminSensorResourceFields("add");
        openAdminSensorModal("adminSensorAddModal");
    });
}

function setupAdminSensorAddModal() {
    const form = document.getElementById("adminSensorAddForm");
    if (!form) return;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (showAdminSensorRequiredFieldsError()) {
            return;
        }

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        normalizeAdminSensorResourcePayload(payload);

        pendingAdminSensorAddPayload = payload;
        setAdminSensorAddConfirmText(payload);
        openAdminSensorModal("adminSensorAddConfirmModal");
    });

    const confirmButton = document.getElementById("adminSensorAddConfirm");
    if (!confirmButton) return;

    confirmButton.addEventListener("click", async () => {
        if (!pendingAdminSensorAddPayload) return;

        try {
            confirmButton.disabled = true;
            await apiRequest("/api/admin/sensors", "POST", pendingAdminSensorAddPayload);
            closeAdminSensorModal("adminSensorAddConfirmModal");
            closeAdminSensorModal("adminSensorAddModal");
            form.reset();
            pendingAdminSensorAddPayload = null;
            clearAdminSensorRequiredFieldHighlights();
            clearAdminSensorAddFormError();
            setupAdminSensorSelectPlaceholderState();
            await renderAdminSensorSections();
        } catch (error) {
            closeAdminSensorModal("adminSensorAddConfirmModal");
            showAdminSensorAddFormError(error.message || "Failed to create sensor.");
            console.error("Failed to create sensor.", error);
        } finally {
            confirmButton.disabled = false;
        }
    });
}

function setAdminSensorAddConfirmText(payload) {
    const text = document.getElementById("adminSensorAddConfirmText");
    if (!text) return;

    const sensorName = payload.sensor_name || "this sensor";
    const sensorType = payload.sensor_type || "sensor";

    if (!payload.house_houseid && !payload.pen_penid) {
        text.textContent = `Add ${sensorName} as an inactive ${sensorType}?`;
        return;
    }

    text.textContent = `Add ${sensorName} as a ${sensorType}?`;
}

function setupAdminSensorEditModal() {
    const form = document.getElementById("adminSensorEditForm");
    if (!form) return;

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        normalizeAdminSensorResourcePayload(payload);
        const sensorId = formData.get("sensor_id");

        if (!sensorId) {
            console.error("Missing sensor id for update");
            return;
        }

        const confirmed = await showAdminSensorConfirmModal(
            "Save changes to this sensor configuration?",
            "Confirm Sensor Changes",
        );

        if (!confirmed) {
            return;
        }

        try {
            await apiRequest(`/api/admin/sensors/${sensorId}`, "PUT", payload);
            closeAdminSensorModal("adminSensorEditModal");
            clearAdminSensorEditFormError();
            await renderAdminSensorSections();
        } catch (error) {
            showAdminSensorEditFormError(error.message || "Failed to update sensor.");
            console.error("Failed to update sensor.", error);
        }
    });
}

function setupAdminSensorModals() {
    document.querySelectorAll("[data-close-admin-sensor-modal]").forEach((button) => {
        button.addEventListener("click", () => {
            closeAdminSensorModal(button.dataset.closeAdminSensorModal);
        });
    });

    document.querySelectorAll(".admin-sensor-modal-backdrop").forEach((modal) => {
        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                closeAdminSensorModal(modal.id);
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            document.querySelectorAll(".admin-sensor-modal-backdrop.show").forEach((modal) => {
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

    const hasOpenModal = document.querySelector(".admin-sensor-modal-backdrop.show");
    const profileModal = document.getElementById("profileModal");
    const profileOpen = profileModal?.classList.contains("show");

    document.body.style.overflow = hasOpenModal || profileOpen ? "hidden" : "";
}

function animateAdminSensorSections() {
    document.querySelectorAll(".admin-sensor-section").forEach((section, index) => {
        section.style.opacity = "0";
        section.style.transform = "translateY(18px)";

        setTimeout(() => {
            section.style.transition = "opacity 0.45s ease, transform 0.45s ease";
            section.style.opacity = "1";
            section.style.transform = "translateY(0)";
        }, 100 + index * 110);
    });
}

function animateAdminSensorRows(scope = document) {
    const rows = scope.querySelectorAll(".admin-sensor-table tbody tr[data-id]");

    rows.forEach((row, index) => {
        row.style.opacity = "0";
        row.style.transform = "translateY(10px)";

        setTimeout(() => {
            row.style.transition = "opacity 0.35s ease, transform 0.35s ease";
            row.style.opacity = "1";
            row.style.transform = "translateY(0)";
        }, 220 + index * 55);
    });
}

async function populateAdminProfileModal() {
    try {
        const response = await fetch("/api/user");
        if (!response.ok) throw new Error("Failed to fetch user info");
        const user = await response.json();
        document.getElementById("profileFirstName").value = user.FirstName || "";
        document.getElementById("profileMiddleName").value = user.MiddleName || "";
        document.getElementById("profileLastName").value = user.LastName || "";
        document.getElementById("profileSuffix").value = user.Suffix || "";
        document.getElementById("profileRole").value = user.Role || "";
        document.getElementById("profilePhone").value = user.PhoneNumber || "";
        document.getElementById("profileId").value = user.EmployeeId || "";
        document.getElementById("profileBirthday").value = user.Birthday || "";
        document.getElementById("profileGender").value = user.Gender || "";
        document.getElementById("profileAddress").value = user.Address || "";
    } catch (e) {
        // Optionally show error
    }
}

function setupAdminProfileModal() {
    const profileModal = document.getElementById("adminProfileModal");
    const openProfileModalBtn = document.getElementById("openProfileModal");
    const closeProfileModalBtn = document.getElementById("closeAdminProfileModal");

    if (openProfileModalBtn && profileModal) {
        openProfileModalBtn.addEventListener("click", async () => {
            await populateAdminProfileModal();
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

function bindAdminStatusFilters() {
    document.querySelectorAll(".admin-sensor-section").forEach((section) => {
        const filterSelect = section.querySelector("[data-status-filter]");
        const tbody = section.querySelector("tbody");
        if (!filterSelect || !tbody) return;

        const updateNoMatchRow = (visibleCount) => {
            const existing = tbody.querySelector("tr.admin-sensor-filter-empty");
            const columnCount = section.dataset.columnCount || "6";
            if (visibleCount === 0) {
                if (!existing) {
                    const noMatchRow = document.createElement("tr");
                    noMatchRow.className = "admin-sensor-filter-empty";
                    noMatchRow.innerHTML = `
                        <td colspan="${columnCount}">
                            <div class="admin-sensor-empty">No sensors match the selected status.</div>
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
            const rows = Array.from(tbody.querySelectorAll("tr[data-id]"));

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

function bindAdminToggleStatusButtons() {
    document.querySelectorAll("[data-toggle-status]").forEach((button) => {
        button.addEventListener("click", () => {
            const row = button.closest("tr[data-id]");
            if (!row) return;

            const sensorId = row.dataset.id;
            const sensorName = row.dataset.name || "this sensor";
            const currentStatus = (row.dataset.status || "Active").trim().toLowerCase();
            const nextStatus = button.dataset.nextStatus ||
                (currentStatus === "under maintenance" ? "Active" : "Under Maintenance");

            const idInput = document.getElementById("adminSensorStatusId");
            const statusInput = document.getElementById("adminSensorStatusValue");
            const text = document.getElementById("adminSensorStatusText");

            if (idInput) idInput.value = sensorId;
            if (statusInput) statusInput.value = nextStatus;
            if (text) {
                if (nextStatus === "Inactive") {
                    text.textContent = `Make ${sensorName} inactive and remove its house and pen assignment?`;
                } else {
                    text.textContent =
                        nextStatus === "Under Maintenance"
                            ? `Are you sure you want to mark ${sensorName} as under maintenance?`
                            : `Are you sure you want to mark ${sensorName} as active?`;
                }
            }

            openAdminSensorModal("adminSensorStatusModal");
        });
    });
}

function bindAdminStatusConfirmButton() {
    const confirmButton = document.getElementById("adminSensorStatusConfirm");
    if (!confirmButton) return;

    confirmButton.addEventListener("click", async () => {
        const sensorId = document.getElementById("adminSensorStatusId")?.value;
        const nextStatus = document.getElementById("adminSensorStatusValue")?.value;

        if (!sensorId || !nextStatus) return;

        try {
            await apiRequest(`/api/admin/sensors/${sensorId}/status`, "PATCH", {
                status: nextStatus,
            });

            closeAdminSensorModal("adminSensorStatusModal");
            await renderAdminSensorSections();
        } catch (error) {
            console.error("Failed to update sensor status.", error);
            await showAdminSensorNoticeModal(`Failed to update sensor status: ${error.message}`, "Unable to Update Status");
        }
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
