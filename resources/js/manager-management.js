const profileModal = document.getElementById("profileModal");
const openProfileModalBtn = document.getElementById("openProfileModal");
const closeProfileModalBtn = document.getElementById("closeProfileModal");

const createTaskTypeModal = document.getElementById("createTaskTypeModal");
const openCreateTaskTypeModalBtn = document.getElementById("openCreateTaskTypeModal");
const closeCreateTaskTypeModalBtn = document.getElementById("closeCreateTaskTypeModal");
const cancelCreateTaskTypeModalBtn = document.getElementById("cancelCreateTaskTypeModal");
const createTaskTypeForm = document.getElementById("createTaskTypeForm");
const newTaskTypeInput = document.getElementById("newTaskType");
const createTaskTypeMessage = document.getElementById("createTaskTypeMessage");

function showManagementConfirmModal(message, title = "Confirm Save", confirmText = "Confirm") {
    return new Promise((resolve) => {
        document.getElementById("managerManagementGenericConfirmModal")?.remove();

        const modal = document.createElement("div");
        modal.className = "manager-management-modal-backdrop confirm-modal-top show";
        modal.id = "managerManagementGenericConfirmModal";
        modal.innerHTML = `
            <div class="manager-management-modal-card">
                <div class="manager-management-modal-header">
                    <div>
                        <p class="manager-management-modal-eyebrow">Management</p>
                        <h2></h2>
                    </div>
                </div>
                <div class="manager-management-modal-form">
                    <p class="manager-management-modal-help"></p>
                    <div class="manager-management-modal-actions">
                        <button type="button" class="manager-management-modal-secondary-btn" data-confirm-cancel>Cancel</button>
                        <button type="button" class="manager-management-modal-primary-btn" data-confirm-ok></button>
                    </div>
                </div>
            </div>
        `;

        modal.querySelector("h2").textContent = title;
        modal.querySelector(".manager-management-modal-help").textContent = message;
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

function setMessage(message, type = "error") {
    if (!createTaskTypeMessage) {
        return;
    }

    if (!message) {
        createTaskTypeMessage.textContent = "";
        createTaskTypeMessage.className = "manager-management-modal-message";
        return;
    }

    createTaskTypeMessage.textContent = message;
    createTaskTypeMessage.className = `manager-management-modal-message ${type}`;
}

function resetCreateTaskTypeModal() {
    if (newTaskTypeInput) {
        newTaskTypeInput.value = "";
    }

    setMessage("");
}

function openModal(modal) {
    if (!modal) {
        return;
    }

    modal.classList.add("show");
    document.body.style.overflow = "hidden";
}

function closeModal(modal) {
    if (!modal) {
        return;
    }

    modal.classList.remove("show");

    const anyModalVisible = document.querySelector(".manager-management-modal-backdrop.show, .profile-modal-backdrop.show");
    document.body.style.overflow = anyModalVisible ? "hidden" : "";
}

async function populateProfileModal() {
    try {
        const response = await fetch("/api/user");

        if (!response.ok) {
            throw new Error("Failed to fetch user info");
        }

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
    } catch (error) {
        console.error("Failed to load profile details.", error);
    }
}

async function handleCreateTaskTypeSubmit(event) {
    event.preventDefault();

    if (!newTaskTypeInput) {
        return;
    }

    const taskType = newTaskTypeInput.value.trim();

    if (!taskType) {
        setMessage("Please enter a new task type.", "error");
        return;
    }

    const confirmed = await showManagementConfirmModal(
        `Create "${taskType}" as a new task type?`,
        "Confirm Task Type",
    );

    if (!confirmed) {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    try {
        const response = await fetch("/api/manager/tasks/types", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token || "",
            },
            body: JSON.stringify({ task_type: taskType }),
        });

        const responseData = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(responseData.error || responseData.errors?.task_type?.[0] || "Unable to save task type.");
        }

        setMessage(`Added "${responseData.task}" successfully.`, "success");
        newTaskTypeInput.value = "";

        setTimeout(() => {
            closeModal(createTaskTypeModal);
        }, 600);
    } catch (error) {
        setMessage(error.message || "Unable to save task type.", "error");
    }
}

if (openProfileModalBtn && profileModal) {
    openProfileModalBtn.addEventListener("click", async () => {
        await populateProfileModal();
        openModal(profileModal);
    });
}

if (closeProfileModalBtn && profileModal) {
    closeProfileModalBtn.addEventListener("click", () => {
        closeModal(profileModal);
    });
}

if (profileModal) {
    profileModal.addEventListener("click", (event) => {
        if (event.target === profileModal) {
            closeModal(profileModal);
        }
    });
}

if (openCreateTaskTypeModalBtn && createTaskTypeModal) {
    openCreateTaskTypeModalBtn.addEventListener("click", () => {
        resetCreateTaskTypeModal();
        openModal(createTaskTypeModal);

        setTimeout(() => {
            newTaskTypeInput?.focus();
        }, 60);
    });
}

if (closeCreateTaskTypeModalBtn && createTaskTypeModal) {
    closeCreateTaskTypeModalBtn.addEventListener("click", () => {
        resetCreateTaskTypeModal();
        closeModal(createTaskTypeModal);
    });
}

if (cancelCreateTaskTypeModalBtn && createTaskTypeModal) {
    cancelCreateTaskTypeModalBtn.addEventListener("click", () => {
        resetCreateTaskTypeModal();
        closeModal(createTaskTypeModal);
    });
}

if (createTaskTypeModal) {
    createTaskTypeModal.addEventListener("click", (event) => {
        if (event.target === createTaskTypeModal) {
            resetCreateTaskTypeModal();
            closeModal(createTaskTypeModal);
        }
    });
}

if (createTaskTypeForm) {
    createTaskTypeForm.addEventListener("submit", handleCreateTaskTypeSubmit);
}

if (profileModal || createTaskTypeModal) {
    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        if (profileModal?.classList.contains("show")) {
            closeModal(profileModal);
            return;
        }

        if (createTaskTypeModal?.classList.contains("show")) {
            resetCreateTaskTypeModal();
            closeModal(createTaskTypeModal);
        }
    });
}
