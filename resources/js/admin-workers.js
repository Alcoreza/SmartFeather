document.addEventListener("DOMContentLoaded", async () => {
    await renderAdminWorkers();
    setupAdminWorkersFilter();
    setupAdminWorkerActionButtons();
    setupAdminWorkerModals();
    setupAdminDeleteWorkerModal();
    setupAdminProfileModal();
});

let adminWorkersCache = [];
let adminWorkerPendingDelete = null;

async function renderAdminWorkers(role = "All") {
    const tbody = document.getElementById("workersTableBody");
    if (!tbody) return;

    try {
        const response = await fetch("/api/admin/workers");
        const workers = await response.json();
        adminWorkersCache = Array.isArray(workers) ? workers : [];

        const filteredWorkers =
            role === "All"
                ? adminWorkersCache
                : adminWorkersCache.filter((worker) => worker.role === role);

        tbody.innerHTML = filteredWorkers
            .map(
                (worker) => `
            <tr>
                <td>${worker.name}</td>
                <td>${worker.id}</td>
                <td>${worker.role}</td>
                <td class="text-center">
                    <div class="admin-worker-action-group">
                        <button
                            class="admin-worker-icon-btn admin-view-btn"
                            type="button"
                            aria-label="View employee"
                            data-action="view"
                            data-worker='${escapeAdminWorker(worker)}'
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                        <button
                            class="admin-worker-icon-btn admin-edit-btn"
                            type="button"
                            aria-label="Edit employee"
                            data-action="edit"
                            data-worker='${escapeAdminWorker(worker)}'
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 20h4l10-10-4-4L4 16v4Z"></path>
                                <path d="M13 7l4 4"></path>
                            </svg>
                        </button>

                        <button
                            class="admin-worker-icon-btn admin-delete-btn"
                            type="button"
                            aria-label="Delete employee"
                            data-action="delete"
                            data-worker='${escapeAdminWorker(worker)}'
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"></path>
                                <path d="M8 6V4h8v2"></path>
                                <path d="M6 6l1 14h10l1-14"></path>
                                <path d="M10 10v7"></path>
                                <path d="M14 10v7"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `,
            )
            .join("");

        bindAdminWorkerRowButtons();
    } catch (error) {
        console.error("Failed to load admin workers.", error);
    }
}

function escapeAdminWorker(worker) {
    return JSON.stringify(worker)
        .replace(/&/g, "&amp;")
        .replace(/'/g, "&#39;")
        .replace(/"/g, "&quot;");
}

function setupAdminWorkersFilter() {
    const filter = document.getElementById("roleFilter");
    if (!filter) return;

    filter.addEventListener("change", () => {
        renderAdminWorkers(filter.value);
    });
}

function setupAdminWorkerActionButtons() {
    const addButton = document.getElementById("openAddWorkerModal");

    if (addButton) {
        addButton.addEventListener("click", () => {
            resetAdminAddModal();
            openAdminModal("adminAddWorkerModal");
        });
    }
}

function bindAdminWorkerRowButtons() {
    const buttons = document.querySelectorAll("[data-action]");

    buttons.forEach((button) => {
        button.addEventListener("click", () => {
            const worker = parseAdminWorker(button.dataset.worker);
            const action = button.dataset.action;

            if (!worker) return;

            if (action === "view") {
                fillAdminViewModal(worker);
                openAdminModal("adminViewWorkerModal");
            }

            if (action === "edit") {
                fillAdminEditModal(worker);
                openAdminModal("adminEditWorkerModal");
            }

            if (action === "delete") {
                adminWorkerPendingDelete = worker;

                const deleteText = document.getElementById(
                    "adminDeleteWorkerText",
                );
                if (deleteText) {
                    deleteText.textContent = `Do you want to delete ${worker.name}?`;
                }

                openAdminModal("adminDeleteWorkerModal");
            }
        });
    });
}

function parseAdminWorker(workerString) {
    if (!workerString) return null;

    try {
        return JSON.parse(
            workerString
                .replace(/&quot;/g, '"')
                .replace(/&#39;/g, "'")
                .replace(/&amp;/g, "&"),
        );
    } catch (error) {
        console.error("Failed to parse worker payload.", error);
        return null;
    }
}

function fillAdminViewModal(worker) {
    setAdminValue("adminViewFirstName", worker.first_name);
    setAdminValue("adminViewMiddleName", worker.middle_name);
    setAdminValue("adminViewLastName", worker.last_name);
    setAdminValue("adminViewSuffix", worker.suffix);
    setAdminValue("adminViewRole", worker.role);
    setAdminValue("adminViewPhone", worker.phone);
    setAdminValue("adminViewId", worker.id);
    setAdminValue("adminViewBirthday", worker.birthday);
    setAdminValue("adminViewGender", worker.gender);
    setAdminValue("adminViewAddress", worker.address);
}

function fillAdminEditModal(worker) {
    setAdminValue("adminEditFirstName", worker.first_name);
    setAdminValue("adminEditMiddleName", worker.middle_name);
    setAdminValue("adminEditLastName", worker.last_name);
    setAdminValue("adminEditSuffix", worker.suffix);
    setAdminValue("adminEditRole", worker.role);
    setAdminValue("adminEditPhone", worker.phone);
    setAdminValue("adminEditId", worker.id);
    setAdminValue("adminEditBirthday", worker.birthday);
    setAdminValue("adminEditGender", worker.gender);
    setAdminValue("adminEditAddress", worker.address);
    setAdminValue("adminEditPassword", "");
}

function resetAdminAddModal() {
    [
        "adminAddFirstName",
        "adminAddMiddleName",
        "adminAddLastName",
        "adminAddSuffix",
        "adminAddRole",
        "adminAddPhone",
        "adminAddId",
        "adminAddBirthday",
        "adminAddGender",
        "adminAddAddress",
        "adminAddPassword",
    ].forEach((id) => setAdminValue(id, ""));
}

function setAdminValue(id, value) {
    const input = document.getElementById(id);
    if (input) {
        input.value = value ?? "";
    }
}

function setupAdminWorkerModals() {
    document.querySelectorAll("[data-close-admin-modal]").forEach((button) => {
        button.addEventListener("click", () => {
            closeAdminModal(button.dataset.closeAdminModal);
        });
    });

    document
        .querySelectorAll(".admin-worker-modal-backdrop")
        .forEach((modal) => {
            modal.addEventListener("click", (event) => {
                if (event.target === modal) {
                    closeAdminModal(modal.id);
                }
            });
        });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            document
                .querySelectorAll(".admin-worker-modal-backdrop.show")
                .forEach((modal) => {
                    closeAdminModal(modal.id);
                });
        }
    });
}

function setupAdminDeleteWorkerModal() {
    const confirmButton = document.getElementById("confirmAdminDeleteWorker");

    if (!confirmButton) {
        return;
    }

    confirmButton.addEventListener("click", () => {
        if (adminWorkerPendingDelete) {
            console.log("Delete worker", adminWorkerPendingDelete);
        }

        adminWorkerPendingDelete = null;
        closeAdminModal("adminDeleteWorkerModal");
    });
}

function openAdminModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.add("show");
    document.body.style.overflow = "hidden";
}

function closeAdminModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.remove("show");

    const hasOpenModal = document.querySelector(
        ".admin-worker-modal-backdrop.show, .admin-profile-modal-backdrop.show",
    );
    document.body.style.overflow = hasOpenModal ? "hidden" : "";
}

function setupAdminProfileModal() {
    const modal = document.getElementById("adminProfileModal");
    const openButton = document.getElementById("openAdminProfileModal");
    const closeButton = document.getElementById("closeAdminProfileModal");

    if (openButton && modal) {
        openButton.addEventListener("click", () => {
            modal.classList.add("show");
            document.body.style.overflow = "hidden";
        });
    }

    if (closeButton && modal) {
        closeButton.addEventListener("click", () => {
            modal.classList.remove("show");
            document.body.style.overflow = "";
        });
    }

    if (modal) {
        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.classList.remove("show");
                document.body.style.overflow = "";
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                modal.classList.remove("show");
                document.body.style.overflow = "";
            }
        });
    }
}
