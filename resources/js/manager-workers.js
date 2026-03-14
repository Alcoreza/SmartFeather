document.addEventListener("DOMContentLoaded", async () => {
    await renderWorkers();
    setupWorkersFilter();
    setupWorkersModal();
    setupProfileModal();
});

let workersCache = [];

async function renderWorkers(role = "All") {
    const tbody = document.getElementById("workersTableBody");

    if (!tbody) {
        return;
    }

    try {
        const response = await fetch("/api/manager/workers");
        const workers = await response.json();
        workersCache = Array.isArray(workers) ? workers : [];

        const filteredWorkers =
            role === "All"
                ? workersCache
                : workersCache.filter((worker) => worker.role === role);

        tbody.innerHTML = filteredWorkers
            .map(
                (worker) => `
            <tr>
                <td>${worker.name}</td>
                <td>${worker.id}</td>
                <td>${worker.role}</td>
                <td class="text-center">
                    <button
                        class="view-worker-btn icon-btn"
                        type="button"
                        aria-label="View employee"
                        data-id="${worker.id || ""}"
                        data-first-name="${worker.first_name || ""}"
                        data-middle-name="${worker.middle_name || ""}"
                        data-last-name="${worker.last_name || ""}"
                        data-suffix="${worker.suffix || ""}"
                        data-role="${worker.role || ""}"
                        data-phone="${worker.phone || ""}"
                        data-birthday="${worker.birthday || ""}"
                        data-gender="${worker.gender || ""}"
                        data-address="${worker.address || ""}"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </td>
            </tr>
        `,
            )
            .join("");

        bindViewButtons();
    } catch (error) {
        console.error("Failed to load workers.", error);
    }
}

function setupWorkersFilter() {
    const filter = document.getElementById("roleFilter");

    if (!filter) {
        return;
    }

    filter.addEventListener("change", () => {
        renderWorkers(filter.value);
    });
}

function setupWorkersModal() {
    const modal = document.getElementById("workerModal");
    const closeBtn = document.getElementById("closeWorkerModal");

    if (!modal) {
        return;
    }

    if (closeBtn) {
        closeBtn.addEventListener("click", closeWorkerModal);
    }

    modal.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeWorkerModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeWorkerModal();
        }
    });
}

function bindViewButtons() {
    const buttons = document.querySelectorAll(".view-worker-btn");

    buttons.forEach((button) => {
        button.addEventListener("click", () => {
            setInputValue("workerFirstName", button.dataset.firstName);
            setInputValue("workerMiddleName", button.dataset.middleName);
            setInputValue("workerLastName", button.dataset.lastName);
            setInputValue("workerSuffix", button.dataset.suffix);
            setInputValue("workerRole", button.dataset.role);
            setInputValue("workerPhone", button.dataset.phone);
            setInputValue("workerId", button.dataset.id);
            setInputValue("workerBirthday", button.dataset.birthday);
            setInputValue("workerGender", button.dataset.gender);
            setInputValue("workerAddress", button.dataset.address);

            openWorkerModal();
        });
    });
}

function setInputValue(id, value) {
    const element = document.getElementById(id);

    if (element) {
        element.value = value || "";
    }
}

function openWorkerModal() {
    const modal = document.getElementById("workerModal");

    if (!modal) {
        return;
    }

    modal.classList.add("show");
    document.body.style.overflow = "hidden";
}

function closeWorkerModal() {
    const modal = document.getElementById("workerModal");

    if (!modal) {
        return;
    }

    modal.classList.remove("show");
    document.body.style.overflow = "";
}

function setupProfileModal() {
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
