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

const createInventoryTypeModal = document.getElementById("createInventoryTypeModal");
const openCreateInventoryTypeModalBtn = document.getElementById("openCreateInventoryTypeModal");
const closeCreateInventoryTypeModalBtn = document.getElementById("closeCreateInventoryTypeModal");
const cancelCreateInventoryTypeModalBtn = document.getElementById("cancelCreateInventoryTypeModal");
const createInventoryTypeForm = document.getElementById("createInventoryTypeForm");
const inventoryCategory = document.getElementById("inventoryCategory");
const newInventoryTypeName = document.getElementById("newInventoryTypeName");
const inventoryTypeNameLabel = document.getElementById("inventoryTypeNameLabel");
const createInventoryTypeMessage = document.getElementById("createInventoryTypeMessage");

function setInventoryMessage(message, type = "error") {
    if (!createInventoryTypeMessage) return;

    createInventoryTypeMessage.textContent = message || "";
    createInventoryTypeMessage.className = message
        ? `manager-management-modal-message ${type}`
        : "manager-management-modal-message";
}

function resetCreateInventoryTypeModal() {
    if (inventoryCategory) inventoryCategory.value = "";
    if (newInventoryTypeName) newInventoryTypeName.value = "";
    if (inventoryTypeNameLabel) inventoryTypeNameLabel.textContent = "Type of Vitamins";

    setInventoryMessage("");
}

function showPopup(message, type = "success", callback = null) {
    const oldPopup = document.getElementById("managementPopupOverlay");
    if (oldPopup) oldPopup.remove();

    const overlay = document.createElement("div");
    overlay.id = "managementPopupOverlay";

    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
    `;

    const box = document.createElement("div");

    box.style.cssText = `
        background: #ffffff;
        width: 360px;
        max-width: 90%;
        border-radius: 18px;
        padding: 28px;
        text-align: center;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        font-family: inherit;
    `;

    box.innerHTML = `
        <h2 style="
            margin:0 0 10px;
            color:${type === "success" ? "#1f7a3f" : "#b42318"};
        ">
            ${type === "success" ? "Success" : "Error"}
        </h2>

        <p style="
            margin:0 0 22px;
            color:#333;
            font-size:15px;
        ">
            ${message}
        </p>

        <button id="managementPopupOkBtn" style="
            border:none;
            background:${type === "success" ? "#1f7a3f" : "#b42318"};
            color:white;
            padding:10px 28px;
            border-radius:999px;
            cursor:pointer;
            font-weight:600;
        ">
            OK
        </button>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    document.getElementById("managementPopupOkBtn")
        .addEventListener("click", () => {
            overlay.remove();

            if (typeof callback === "function") {
                callback();
            }
        });
}

async function handleCreateInventoryTypeSubmit(event) {
    event.preventDefault();

    const category = inventoryCategory?.value || "";
    const name = newInventoryTypeName?.value.trim() || "";
    const initialStock = newInventoryInitialStock?.value || "";
    const critical = newInventoryCritical?.value || "";

    if (!category || !name || initialStock === "" || critical === "") {
        showPopup("Please complete all fields.", "error");
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    try {
        const response = await fetch("/api/manager/inventory/types", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": token || "",
            },
            body: JSON.stringify({
                category: category,
                name: name,
                initial_stock: Number(initialStock),
                critical: Number(critical),
            }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {

            if (data.errors) {
                const firstError = Object.values(data.errors)[0]?.[0];

                showPopup(
                    firstError || "Unable to save inventory type.",
                    "error"
                );

                return;
            }

            showPopup(
                data.error || data.message || "Unable to save inventory type.",
                "error"
            );

            return;
        }

        showPopup(
            `Added "${data.name}" successfully.`,
            "success",
            () => {
                resetCreateInventoryTypeModal();
                closeModal(createInventoryTypeModal);
                location.reload();
            }
        );

    } catch (error) {

        console.error(error);

        showPopup(
            error.message || "Unable to save inventory type.",
            "error"
        );
    }
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

inventoryCategory?.addEventListener("change", () => {
    if (inventoryCategory.value === "feed") {
        inventoryTypeNameLabel.textContent = "Type of Feed";
        newInventoryTypeName.placeholder = "Enter feed type";
    } else {
        inventoryTypeNameLabel.textContent = "Type of Vitamins";
        newInventoryTypeName.placeholder = "Enter vitamin type";
    }
});

openCreateInventoryTypeModalBtn?.addEventListener("click", () => {
    resetCreateInventoryTypeModal();
    openModal(createInventoryTypeModal);

    setTimeout(() => {
        inventoryCategory?.focus();
    }, 60);
});

closeCreateInventoryTypeModalBtn?.addEventListener("click", () => {
    resetCreateInventoryTypeModal();
    closeModal(createInventoryTypeModal);
});

cancelCreateInventoryTypeModalBtn?.addEventListener("click", () => {
    resetCreateInventoryTypeModal();
    closeModal(createInventoryTypeModal);
});

createInventoryTypeModal?.addEventListener("click", (event) => {
    if (event.target === createInventoryTypeModal) {
        resetCreateInventoryTypeModal();
        closeModal(createInventoryTypeModal);
    }
});

createInventoryTypeForm?.addEventListener("submit", handleCreateInventoryTypeSubmit);