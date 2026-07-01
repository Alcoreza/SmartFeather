document.addEventListener("DOMContentLoaded", async () => {
    await Promise.all([renderManagerTasks(false), loadTaskFormOptions()]);

    setupTaskFilters();
    setupTaskRowPaginationControls();
    setupTaskSelectPlaceholderState();
    setupManagerTaskModals();
    setupAddTaskModal();
    setupEditTaskModal();
    setupManagerProfileModal();
    refreshTaskFilterOptions();
    renderCurrentTaskTable();
    animateTaskSections();
});

const ALL_HOUSES_OPTION = "All houses";
const ALL_PRIORITY_OPTION = "All priority";
const PRIORITY_ORDER = ["Low", "Medium", "High"];
const TASK_ROWS_PER_PAGE = 5;
const TASK_DOT_LIMIT = 5;
const TASK_STATUS_OPTIONS = ["pending", "for_approval", "completed"];
const TASK_TABLE_COLUMNS = {
    pending: [
        { key: "name", label: "Name" },
        { key: "task_assigned", label: "Task<br>Assigned" },
        { key: "house_number", label: "House" },
        { key: "pen_number", label: "Pen<br>Number" },
        { key: "detailed_task", label: "Detailed<br>Task" },
        { key: "priority", label: "Priority" },
        { key: "time_assigned", label: "Time<br>Assigned" },
        { key: "finish_by", label: "Finish<br>By" },
        { key: "actions", label: "Actions" },
    ],
    for_approval: [
        { key: "name", label: "Name" },
        { key: "task_assigned", label: "Task<br>Assigned" },
        { key: "house_number", label: "House" },
        { key: "pen_number", label: "Pen<br>Number" },
        { key: "detailed_task", label: "Detailed<br>Task" },
        { key: "photo", label: "Photo" },
        { key: "priority", label: "Priority" },
        { key: "time_assigned", label: "Time<br>Assigned" },
        { key: "finish_by", label: "Finish<br>By" },
        { key: "mark", label: "Mark" },
    ],
    completed: [
        { key: "name", label: "Name" },
        { key: "task_assigned", label: "Task<br>Assigned" },
        { key: "house_number", label: "House" },
        { key: "pen_number", label: "Pen<br>Number" },
        { key: "detailed_task", label: "Detailed<br>Task" },
        { key: "photo", label: "Photo" },
        { key: "priority", label: "Priority" },
        { key: "notes", label: "Notes" },
        { key: "time_assigned", label: "Time<br>Assigned" },
        { key: "finish_by", label: "Finish<br>By" },
        { key: "time_completed", label: "Time<br>Completed" },
    ],
};

let taskPendingVerify = null;
let taskPendingDelete = null;
let taskPendingEdit = null;
let taskDataFingerprint = "";
let selectedTaskStatus = "pending";
let taskDataCache = {
    pending: [],
    for_approval: [],
    completed: [],
};
let taskRowPages = {
    pending: 0,
    for_approval: 0,
    completed: 0,
};
let taskLastRowPages = {
    pending: 0,
    for_approval: 0,
    completed: 0,
};
let availableHouseOptions = [];
let taskFormOptions = {
    houses: [],
    task_categories: [],
    priority_levels: [],
};
let nextTaskRowUid = 0;
const addTaskRequiredFields = [
    { name: "worker_name", label: "Assign Flockman" },
    { name: "task_category", label: "Task" },
    { name: "priority_level", label: "Priority Level" },
    { name: "house_number", label: "House" },
    { name: "pen_number", label: "Pen Number" },
    { name: "time_assigned", label: "Time to finish" },
    { name: "date_assigned", label: "Date to finish" },
];
let shouldTrackAddTaskRequiredHighlights = false;
let taskFilters = {
    house: ALL_HOUSES_OPTION,
    priority: ALL_PRIORITY_OPTION,
};

async function renderManagerTasks(shouldRender = true) {
    try {
        const response = await fetch("/api/manager/tasks");
        const data = await response.json();

        const nextTaskData = {
            pending: data.pending || [],
            for_approval: data.for_approval || [],
            completed: data.completed || [],
        };
        const nextFingerprint = JSON.stringify(nextTaskData);
        const hasChanged = nextFingerprint !== taskDataFingerprint;

        taskDataCache = nextTaskData;
        taskDataFingerprint = nextFingerprint;

        refreshTaskFilterOptions();
        if (shouldRender && hasChanged) {
            renderCurrentTaskTable();
        }
    } catch (error) {
        console.error("Failed to load manager tasks.", error);
    }
}

async function loadTaskFormOptions({ preserveSelections = false } = {}) {
    const workerSelect = document.getElementById("taskWorkerName");
    const selectedWorker = preserveSelections ? workerSelect?.value || "" : "";
    const rowSelections = preserveSelections
        ? Array.from(document.querySelectorAll("#tasksContainer .manager-task-row")).map((row) => ({
            row,
            taskCategory: row.querySelector(".task-category-select")?.value || "",
            priority: row.querySelector(".task-priority-select")?.value || "",
            house: row.querySelector(".task-house-select")?.value || "",
            pen: row.querySelector(".task-pen-select")?.value || "",
        }))
        : [];

    try {
        const response = await fetch("/api/manager/tasks/form-options");
        const data = await response.json();

        fillSelect(
            workerSelect,
            data.workers || [],
            "id",
            "name",
            "Select worker",
        );

        if (preserveSelections && selectedWorker && workerSelect) {
            workerSelect.value = selectedWorker;
        }

        taskFormOptions = {
            houses: data.houses || [],
            task_categories: data.task_categories || [],
            priority_levels: data.priority_levels || [],
        };

        availableHouseOptions = [...new Set((data.houses || [])
            .map((house) => String(house.number ?? "").trim())
            .filter(Boolean))]
            .sort((a, b) => a.localeCompare(b));

        document.querySelectorAll("#tasksContainer .manager-task-row").forEach((row) => {
            populateTaskRowSelects(row);

            if (!preserveSelections) return;

            const selection = rowSelections.find((item) => item.row === row);
            if (!selection) return;

            const taskSelect = row.querySelector(".task-category-select");
            const prioritySelect = row.querySelector(".task-priority-select");
            const houseSelect = row.querySelector(".task-house-select");

            if (taskSelect && selection.taskCategory) taskSelect.value = selection.taskCategory;
            if (prioritySelect && selection.priority) prioritySelect.value = selection.priority;
            if (houseSelect && selection.house) {
                houseSelect.value = selection.house;
                loadPensForTaskRow(row, selection.pen);
            }
        });

        refreshTaskFilterOptions();
        setupTaskSelectPlaceholderState();
    } catch (error) {
        console.error("Failed to load task form options.", error);
    }
}

function fillSimpleSelect(select, items, placeholder) {
    if (!select) return;

    select.innerHTML = `
        <option value="">${placeholder}</option>
        ${items.map((item) => `<option value="${item}">${item}</option>`).join("")}
    `;
}

function setupTaskFilters() {
    document.querySelectorAll("[data-task-status]").forEach((button) => {
        button.addEventListener("click", () => {
            const nextStatus = button.dataset.taskStatus;

            if (!TASK_STATUS_OPTIONS.includes(nextStatus) || nextStatus === selectedTaskStatus) {
                return;
            }

            selectedTaskStatus = nextStatus;
            taskRowPages[selectedTaskStatus] = taskRowPages[selectedTaskStatus] || 0;
            syncTaskStatusButtons();
            renderCurrentTaskTable();
            renderManagerTasks();
        });
    });

    document.querySelectorAll(".manager-task-filter-select[data-filter-type]").forEach((select) => {
        select.addEventListener("change", () => {
            const filterType = select.dataset.filterType;

            if (!filterType) {
                return;
            }

            taskFilters[filterType] = select.value;

            resetTaskRowPages();
            syncTaskFilterControls();
            renderCurrentTaskTable();
        });
    });
}

function resetTaskRowPages() {
    TASK_STATUS_OPTIONS.forEach((section) => {
        taskRowPages[section] = 0;
    });
}

function setupTaskRowPaginationControls() {
    const prevBtn = document.querySelector("[data-task-row-prev]");
    const nextBtn = document.querySelector("[data-task-row-next]");
    const dotsContainer = document.querySelector("[data-task-row-dots]");

    prevBtn?.addEventListener("click", () => {
        taskRowPages[selectedTaskStatus] = Math.max(0, taskRowPages[selectedTaskStatus] - 1);
        renderCurrentTaskTable();
    });

    nextBtn?.addEventListener("click", () => {
        taskRowPages[selectedTaskStatus] += 1;
        renderCurrentTaskTable();
    });

    dotsContainer?.addEventListener("click", (event) => {
        const dot = event.target.closest("[data-task-row-page]");
        if (!dot) return;
        taskRowPages[selectedTaskStatus] = Number(dot.dataset.taskRowPage || 0);
        renderCurrentTaskTable();
    });
}

function renderCurrentTaskTable() {
    renderTaskSection(selectedTaskStatus);
}

function renderTaskSection(section) {
    if (section === "pending") {
        renderUnifiedTaskTable("pending", taskDataCache.pending);
        return;
    }

    if (section === "for_approval") {
        renderUnifiedTaskTable("for_approval", taskDataCache.for_approval);
        return;
    }

    if (section === "completed") {
        renderUnifiedTaskTable("completed", taskDataCache.completed);
    }
}

function updateTaskRowPagination(section, totalItems) {
    const filteredItems = applyTaskFilters(totalItems, taskFilters);

    const totalPages = Math.max(1, Math.ceil(filteredItems.length / TASK_ROWS_PER_PAGE));
    taskRowPages[section] = Math.min(taskRowPages[section], totalPages - 1);

    const prevBtn = document.querySelector("[data-task-row-prev]");
    const nextBtn = document.querySelector("[data-task-row-next]");
    const dotsContainer = document.querySelector("[data-task-row-dots]");
    const pagination = document.querySelector("[data-task-pagination]");

    pagination?.classList.toggle("is-hidden", filteredItems.length <= TASK_ROWS_PER_PAGE);

    if (prevBtn) {
        prevBtn.disabled = taskRowPages[section] === 0;
    }

    if (nextBtn) {
        nextBtn.disabled = taskRowPages[section] >= totalPages - 1;
    }

    if (dotsContainer) {
        const visiblePages = getVisibleTaskPages(totalPages, taskRowPages[section]);
        const activeDotIndex = Math.max(0, visiblePages.indexOf(taskRowPages[section]));
        const direction = taskRowPages[section] > taskLastRowPages[section]
            ? "next"
            : taskRowPages[section] < taskLastRowPages[section]
                ? "prev"
                : "still";
        dotsContainer.dataset.pageDirection = direction;
        dotsContainer.style.setProperty("--active-dot-index", activeDotIndex);
        dotsContainer.style.setProperty("--active-dot-offset", `${activeDotIndex * 18}px`);
        const dotTrackWidth = (visiblePages.length * 10) + (Math.max(0, visiblePages.length - 1) * 8);
        dotsContainer.style.setProperty("--dot-track-width", `${dotTrackWidth}px`);
        dotsContainer.style.setProperty("--dot-track-half", `${dotTrackWidth / 2}px`);
        dotsContainer.innerHTML = visiblePages.map((index) => `
            <button
                type="button"
                class="manager-task-page-dot ${index === taskRowPages[section] ? 'active' : ''}"
                data-task-row-page="${index}"
                aria-label="Go to page ${index + 1}"
            ></button>
        `).join("");
        taskLastRowPages[section] = taskRowPages[section];
    }
}

function getVisibleTaskPages(totalPages, currentPage) {
    if (totalPages <= TASK_DOT_LIMIT) {
        return Array.from({ length: totalPages }, (_, index) => index);
    }

    const centerOffset = Math.floor(TASK_DOT_LIMIT / 2);
    let start = Math.max(0, currentPage - centerOffset);
    let end = start + TASK_DOT_LIMIT;

    if (end > totalPages) {
        end = totalPages;
        start = Math.max(0, end - TASK_DOT_LIMIT);
    }

    return Array.from({ length: end - start }, (_, index) => start + index);
}

function refreshTaskFilterOptions() {
    const houseOptions = buildHouseFilterOptions();
    const priorityOptions = buildPriorityFilterOptions();

    taskFilters.house = houseOptions.includes(taskFilters.house)
        ? taskFilters.house
        : houseOptions[0];

    taskFilters.priority = priorityOptions.includes(taskFilters.priority)
        ? taskFilters.priority
        : priorityOptions[0];

    syncTaskFilterControls();
}

function syncTaskFilterControls() {
    const houseSelect = document.getElementById("taskHouseFilter");
    const prioritySelect = document.getElementById("taskPriorityFilter");

    syncTaskStatusButtons();

    if (houseSelect) {
        const houseOptions = buildHouseFilterOptions();
        updateTaskFilterSelect("taskHouseFilter", houseOptions, taskFilters.house);
    }

    if (prioritySelect) {
        const priorityOptions = buildPriorityFilterOptions();
        updateTaskFilterSelect("taskPriorityFilter", priorityOptions, taskFilters.priority);
    }
}

function syncTaskStatusButtons() {
    document.querySelectorAll("[data-task-status]").forEach((button) => {
        const isActive = button.dataset.taskStatus === selectedTaskStatus;
        button.classList.remove("active");
        button.classList.toggle("is-selected", isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
}

function buildHouseFilterOptions() {
    const values = [...new Set(availableHouseOptions.filter(Boolean))];

    return [ALL_HOUSES_OPTION, ...values.sort((a, b) => a.localeCompare(b))];
}

function buildPriorityFilterOptions() {
    return [ALL_PRIORITY_OPTION, ...PRIORITY_ORDER];
}

function updateTaskFilterSelect(selectId, options, currentValue) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const nextValue = options.includes(currentValue) ? currentValue : options[0];

    select.innerHTML = options
        .map((option) => `<option value="${option}">${option}</option>`)
        .join("");

    select.value = nextValue;
}

function applyTaskFilters(items, filters) {
    return items.filter((item) => {
        const house = String(item.house_number ?? "").trim();
        const priority = String(item.priority ?? "").trim();

        const matchesHouse = filters.house === ALL_HOUSES_OPTION || house === filters.house;
        const matchesPriority = filters.priority === ALL_PRIORITY_OPTION || priority === filters.priority;

        return matchesHouse && matchesPriority;
    });
}

function renderUnifiedTaskTable(section, items) {
    const thead = document.getElementById("managerTasksTableHead");
    const tbody = document.getElementById("managerTasksTableBody");
    const table = tbody?.closest(".manager-task-table");
    const tableWrap = tbody?.closest(".manager-task-table-wrap");
    if (!tbody) return;

    const columns = TASK_TABLE_COLUMNS[section] || TASK_TABLE_COLUMNS.pending;
    table?.classList.remove(
        "manager-task-table-pending",
        "manager-task-table-for-approval",
        "manager-task-table-completed",
    );
    table?.classList.add(`manager-task-table-${section.replace("_", "-")}`);
    tableWrap?.classList.remove(
        "manager-task-table-wrap-pending",
        "manager-task-table-wrap-for-approval",
        "manager-task-table-wrap-completed",
    );
    tableWrap?.classList.add(`manager-task-table-wrap-${section.replace("_", "-")}`);

    if (thead) {
        thead.innerHTML = `<tr>${columns.map((column) => `<th>${column.label}</th>`).join("")}</tr>`;
    }

    const filteredItems = applyTaskFilters(items, taskFilters);
    const totalPages = Math.max(1, Math.ceil(filteredItems.length / TASK_ROWS_PER_PAGE));
    taskRowPages[section] = Math.min(taskRowPages[section], totalPages - 1);

    if (!filteredItems.length) {
        tbody.innerHTML = `<tr><td colspan="${columns.length}" class="manager-task-empty">No tasks match the selected filters.</td></tr>`;
        animateTaskRows();
        updateTaskRowPagination(section, items);
        return;
    }

    const start = taskRowPages[section] * TASK_ROWS_PER_PAGE;
    const pageItems = filteredItems.slice(start, start + TASK_ROWS_PER_PAGE);

    tbody.innerHTML = pageItems
        .map((item) => `
            <tr>
                ${columns.map((column) => `<td data-task-label="${escapeHtml(stripHtml(column.label))}">${renderTaskCell(column.key, item, section)}</td>`).join("")}
            </tr>
        `)
        .join("");

    updateTaskRowPagination(section, items);
    bindPhotoButtons();
    if (section === "pending") bindPendingTaskActionButtons();
    if (section === "for_approval") bindVerifyButtons();
    animateTaskRows();
}

function renderTaskCell(key, item, section) {
    if (key === "priority") {
        return renderTaskPriorityBadge(item.priority);
    }

    if (key === "photo") {
        return item.photo_url
            ? `<button
                type="button"
                class="manager-task-photo-link"
                data-photo-name="${item.photo_name}"
                data-photo-url="${item.photo_url}"
            >
                <img src="${item.photo_url}" alt="${item.photo_name}" style="max-width:120px; max-height:80px; object-fit:cover; border-radius:6px;">
            </button>`
            : "No photo";
    }

    if (key === "mark") {
        return `<button
            type="button"
            class="manager-task-verify-btn"
            data-verify-task='${encodeTaskPayload(item)}'
        >
            Verify
        </button>`;
    }

    if (key === "actions" && section === "pending") {
        return `<div class="manager-task-action-group">
            <button
                type="button"
                class="manager-task-action-btn manager-task-edit-btn"
                data-edit-task='${encodeTaskPayload(item)}'
                aria-label="Edit task"
                title="Edit"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                </svg>
            </button>
            <button
                type="button"
                class="manager-task-action-btn manager-task-delete-btn"
                data-delete-task='${encodeTaskPayload(item)}'
                aria-label="Delete task"
                title="Delete"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M3 6h18"></path>
                    <path d="M8 6V4h8v2"></path>
                    <path d="M19 6l-1 14H6L5 6"></path>
                    <path d="M10 11v5"></path>
                    <path d="M14 11v5"></path>
                </svg>
            </button>
        </div>`;
    }

    return escapeHtml(item[key] ?? "");
}

function renderTaskPriorityBadge(priority) {
    const priorityText = String(priority ?? "").trim();
    const priorityClass = ["low", "medium", "high"].includes(priorityText.toLowerCase())
        ? priorityText.toLowerCase()
        : "unset";

    return `<span class="manager-task-priority-badge ${escapeHtml(priorityClass)}">${escapeHtml(priorityText || "Unset")}</span>`;
}

function stripHtml(value) {
    return String(value ?? "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function encodeTaskPayload(item) {
    return JSON.stringify(item)
        .replace(/&/g, "&amp;")
        .replace(/'/g, "&#39;")
        .replace(/"/g, "&quot;");
}

function decodeTaskPayload(value) {
    try {
        return JSON.parse(
            value
                .replace(/&quot;/g, '"')
                .replace(/&#39;/g, "'")
                .replace(/&amp;/g, "&"),
        );
    } catch (error) {
        console.error("Failed to parse task payload.", error);
        return null;
    }
}

function bindPhotoButtons() {
    document.querySelectorAll(".manager-task-photo-link").forEach((button) => {
        button.addEventListener("click", () => {
            const img = document.getElementById("taskPhotoPreview");
            if (!img) return;

            img.src = button.dataset.photoUrl || "";
            img.alt = button.dataset.photoName || "Task proof photo";

            openTaskModal("taskPhotoModal");
        });
    });
}

function bindVerifyButtons() {
    document.querySelectorAll("[data-verify-task]").forEach((button) => {
        button.addEventListener("click", () => {
            taskPendingVerify = decodeTaskPayload(
                button.dataset.verifyTask || "",
            );

            const text = document.getElementById("taskVerifyText");
            if (text && taskPendingVerify) {
                text.textContent = `Do you want to mark ${taskPendingVerify.task_assigned} for ${taskPendingVerify.name} as complete?`;
            }

            openTaskModal("taskVerifyModal");
        });
    });
}

function bindPendingTaskActionButtons() {
    document.querySelectorAll("[data-edit-task]").forEach((button) => {
        button.addEventListener("click", async () => {
            const task = decodeTaskPayload(button.dataset.editTask || "");
            if (task) {
                await openEditTaskModal(task);
            }
        });
    });

    document.querySelectorAll("[data-delete-task]").forEach((button) => {
        button.addEventListener("click", () => {
            taskPendingDelete = decodeTaskPayload(button.dataset.deleteTask || "");

            const text = document.getElementById("deleteTaskText");
            if (text && taskPendingDelete) {
                text.textContent = `Delete ${taskPendingDelete.task_assigned} for ${taskPendingDelete.name}? This action cannot be undone.`;
            }

            openTaskModal("deleteTaskModal");
        });
    });
}

function setupManagerTaskModals() {
    document.querySelectorAll("[data-close-task-modal]").forEach((button) => {
        button.addEventListener("click", () => {
            closeTaskModal(button.dataset.closeTaskModal);
        });
    });

    document
        .querySelectorAll(".manager-task-modal-backdrop")
        .forEach((modal) => {
            modal.addEventListener("click", (event) => {
                if (event.target === modal) {
                    closeTaskModal(modal.id);
                }
            });
        });

    const confirmButton = document.getElementById("confirmTaskVerify");
    if (confirmButton) {
        confirmButton.addEventListener("click", async () => {
            if (taskPendingVerify) {
                await updateTaskStatus(taskPendingVerify.id, "Completed");
            }

            taskPendingVerify = null;
            closeTaskModal("taskVerifyModal");
        });
    }

    const deleteButton = document.getElementById("confirmTaskDelete");
    if (deleteButton) {
        deleteButton.addEventListener("click", async () => {
            if (taskPendingDelete) {
                await deletePendingTask(taskPendingDelete.id);
            }

            taskPendingDelete = null;
            closeTaskModal("deleteTaskModal");
        });
    }

    const editButton = document.getElementById("confirmEditTask");
    if (editButton) {
        editButton.addEventListener("click", async () => {
            let saved = false;

            if (taskPendingEdit) {
                saved = await savePendingTaskEdit(taskPendingEdit.id, taskPendingEdit.payload);
            }

            if (saved) {
                taskPendingEdit = null;
                closeTaskModal("confirmEditTaskModal");
            }
        });
    }

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            document
                .querySelectorAll(".manager-task-modal-backdrop.show")
                .forEach((modal) => {
                    closeTaskModal(modal.id);
                });
        }
    });
}

function setupAddTaskModal() {
    const openButton = document.getElementById("openAddTaskModal");
    const form = document.getElementById("addTaskForm");
    const formError = document.getElementById("addTaskFormError");
    const addRowButton = document.getElementById("addTaskRowBtn");

    if (openButton) {
        openButton.addEventListener("click", () => {
            form?.reset();
            resetAddTaskRows();
            clearAddTaskFormError();
            openTaskModal("addTaskModal");
            loadTaskFormOptions({ preserveSelections: true });
        });
    }

    addRowButton?.addEventListener("click", () => {
        addNewTaskRow({ scrollToRow: true });
    });

    if (form) {
        form.querySelectorAll("input, select, textarea").forEach((field) => {
            field.addEventListener("input", () => clearTaskFieldError(field));
            field.addEventListener("change", () => clearTaskFieldError(field));
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            const validation = validateAddTaskForm(form, payload);
            if (!validation.isValid) {
                showAddTaskFormError(formError, validation.message || validation.missingFields);
                return;
            }

            const toLocalDateTimeString = (dateObj) => {
                const pad = (value) => String(value).padStart(2, "0");
                return `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())}` +
                    `T${pad(dateObj.getHours())}:${pad(dateObj.getMinutes())}:${pad(dateObj.getSeconds())}`;
            };

            const timeAssigned = toLocalDateTimeString(new Date());
            const taskPayloads = validation.tasks.map((task) => ({
                user_employeeid: payload.worker_name,
                tasktype: task.taskCategory,
                prioritylevel: task.priorityLevel,
                house_houseid: task.houseNumber,
                pennumber: task.penNumber,
                timeassigned: timeAssigned,
                finishby: `${task.dateAssigned}T${task.timeAssigned}:00`,
                detailedtask: task.detailedTask,
                status: "Pending",
            }));

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                for (const body of taskPayloads) {
                    const response = await fetch("/api/manager/tasks", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": token || "",
                        },
                        body: JSON.stringify(body),
                    });

                    const responseText = await response.text();

                    if (!response.ok) {
                        throw new Error(responseText || `Failed to save task (${response.status}).`);
                    }
                }

                await renderManagerTasks();
                closeTaskModal("addTaskModal");
                form.reset();
                resetAddTaskRows();
                setupTaskSelectPlaceholderState();
            } catch (error) {
                console.error("Failed to save new task:", error);
                showAddTaskFormError(formError, "Unable to save task. Please try again.");
            }
        });
    }
}

function setupEditTaskModal() {
    const form = document.getElementById("editTaskForm");
    const formError = document.getElementById("editTaskFormError");

    if (!form) return;

    form.querySelectorAll("input, select, textarea").forEach((field) => {
        field.addEventListener("input", () => clearEditTaskFieldError(field));
        field.addEventListener("change", () => clearEditTaskFieldError(field));
    });

    form.querySelector(".task-house-select")?.addEventListener("change", async () => {
        await loadPensForEditTaskForm();
    });

    form.querySelector(".task-category-select")?.addEventListener("change", async () => {
        await loadPensForEditTaskForm();
    });

    form.addEventListener("submit", (event) => {
        event.preventDefault();

        if (!taskPendingEdit?.item) {
            return;
        }

        const validation = validateEditTaskForm(form);
        if (!validation.isValid) {
            showEditTaskFormError(formError, validation.message || "Please fill in the required fields.");
            return;
        }

        taskPendingEdit = {
            item: taskPendingEdit.item,
            id: taskPendingEdit.item.id,
            payload: validation.payload,
        };

        closeTaskModal("editTaskModal");
        openTaskModal("confirmEditTaskModal");
    });
}

async function openEditTaskModal(task) {
    taskPendingEdit = { item: task };
    await loadTaskFormOptions();

    const form = document.getElementById("editTaskForm");
    if (!form) return;

    clearEditTaskFormError();
    form.reset();

    const workerField = document.getElementById("editTaskWorkerName");
    if (workerField) {
        workerField.value = task.name || "";
    }

    populateTaskRowSelects(form);

    form.querySelector("[name='task_category']").value = task.task_assigned || "";
    form.querySelector("[name='priority_level']").value = task.priority || "";
    form.querySelector("[name='house_number']").value = task.house_id || "";
    form.querySelector("[name='detailed_task']").value = task.detailed_task || "";

    const finishParts = splitTaskDateTime(task.finish_by);
    form.querySelector("[name='date_assigned']").value = finishParts.date;
    form.querySelector("[name='time_assigned']").value = finishParts.time;

    await loadPensForEditTaskForm(task.pen_id);
    setupTaskSelectPlaceholderState();
    openTaskModal("editTaskModal");
}

async function loadPensForEditTaskForm(selectedPenId = null) {
    const form = document.getElementById("editTaskForm");
    if (!form) return;

    const houseSelect = form.querySelector(".task-house-select");
    const penSelect = form.querySelector(".task-pen-select");
    const taskType = form.querySelector(".task-category-select")?.value || "";
    if (!penSelect) return;

    if (!houseSelect?.value) {
        fillSelect(penSelect, [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
        return;
    }

    try {
        const params = new URLSearchParams();
        if (taskType) {
            params.set("task_type", taskType);
        }
        const query = params.toString() ? `?${params.toString()}` : "";
        const response = await fetch(`/api/manager/tasks/houses/${houseSelect.value}/pens${query}`);
        const data = await response.json();
        fillSelect(penSelect, data.pens || [], "number", "label", "Select pen");
        penSelect.value = String(selectedPenId || taskPendingEdit?.item?.pen_id || "");
        setupTaskSelectPlaceholderState();
    } catch (error) {
        console.error("Failed to load pens for task edit.", error);
        fillSelect(penSelect, [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
    }
}

function validateEditTaskForm(form) {
    const missingFields = [];
    const values = getTaskRowValues(form);

    clearEditTaskRequiredFieldHighlights(false);

    Object.entries({
        task_category: values.taskCategory,
        priority_level: values.priorityLevel,
        house_number: values.houseNumber,
        pen_number: values.penNumber,
        date_assigned: values.dateAssigned,
        time_assigned: values.timeAssigned,
    }).forEach(([name, value]) => {
        if (!String(value ?? "").trim()) {
            missingFields.push({ name });
            form.querySelector(`[name="${name}"]`)?.closest(".manager-task-form-field")?.classList.add("has-error");
        }
    });

    if (missingFields.length) {
        form.querySelector(`[name="${missingFields[0].name}"]`)?.focus();

        return {
            isValid: false,
            message: "Please fill in the required fields.",
        };
    }

    return {
        isValid: true,
        payload: {
            tasktype: values.taskCategory,
            prioritylevel: values.priorityLevel,
            house_houseid: values.houseNumber,
            pennumber: values.penNumber,
            finishby: `${values.dateAssigned} ${values.timeAssigned}:00`,
            detailedtask: values.detailedTask,
        },
    };
}

function splitTaskDateTime(value) {
    const parts = String(value || "").trim().split(/\s+/);
    const timeParts = String(parts[1] || "").split(":");

    return {
        date: parts[0] || "",
        time: timeParts.length >= 2 ? `${timeParts[0]}:${timeParts[1]}` : "",
    };
}

async function savePendingTaskEdit(taskId, payload) {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch(`/api/manager/tasks/${taskId}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token || "",
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || `Failed to update task (${response.status}).`);
        }

        await renderManagerTasks();
        closeTaskModal("editTaskModal");
        return true;
    } catch (error) {
        console.error("Failed to update task:", error);
        closeTaskModal("confirmEditTaskModal");
        openTaskModal("editTaskModal");
        showEditTaskFormError(
            document.getElementById("editTaskFormError"),
            "Unable to save task changes. Please try again.",
        );
        return false;
    }
}

async function deletePendingTask(taskId) {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch(`/api/manager/tasks/${taskId}`, {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": token || "",
            },
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || `Failed to delete task (${response.status}).`);
        }

        await renderManagerTasks();
    } catch (error) {
        console.error("Failed to delete task:", error);
        alert("Error deleting task: " + error.message);
    }
}

function resetAddTaskRows() {
    const container = document.getElementById("tasksContainer");
    if (!container) return;

    container.innerHTML = "";
    nextTaskRowUid = 0;
    addNewTaskRow();
}

function addNewTaskRow({ scrollToRow = false } = {}) {
    const container = document.getElementById("tasksContainer");
    if (!container) return null;

    const rowUid = nextTaskRowUid;
    nextTaskRowUid += 1;

    container.insertAdjacentHTML("beforeend", generateTaskRowHtml(rowUid));
    const row = container.lastElementChild;
    populateTaskRowSelects(row);
    bindTaskRowEvents(row);
    updateTaskCountBadge();
    setupTaskSelectPlaceholderState();

    if (scrollToRow && row) {
        row.scrollIntoView({ behavior: "smooth", block: "start" });
        row.querySelector("select[name='task_category']")?.focus({ preventScroll: true });
    }

    return row;
}

function generateTaskRowHtml(rowUid) {
    return `
        <div class="manager-task-row" data-task-row="${rowUid}">
            <div class="manager-task-row-header">
                <h4 class="manager-task-row-title">Task</h4>
                <button type="button" class="manager-task-row-remove-btn" aria-label="Remove task">&times;</button>
            </div>

            <div class="manager-task-row-grid">
                <div class="manager-task-form-field">
                    <label>Task*</label>
                    <select name="task_category" class="task-select-placeholder task-category-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Priority Level*</label>
                    <select name="priority_level" class="task-select-placeholder task-priority-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>House*</label>
                    <select name="house_number" class="task-select-placeholder task-house-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Pen Number*</label>
                    <select name="pen_number" class="task-select-placeholder task-pen-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Date to finish*</label>
                    <input type="date" name="date_assigned" class="task-date-input">
                </div>
                <div class="manager-task-form-field">
                    <label>Time to finish*</label>
                    <input type="time" name="time_assigned" class="task-time-input">
                </div>
                <div class="manager-task-form-field full">
                    <label>Detailed Task</label>
                    <textarea name="detailed_task" class="task-detailed-textarea" rows="2" placeholder="Write a clear and specific task instruction here."></textarea>
                </div>
            </div>
        </div>
    `;
}

function populateTaskRowSelects(row) {
    if (!row) return;

    fillSimpleSelect(
        row.querySelector(".task-category-select"),
        taskFormOptions.task_categories,
        "Select task category",
    );

    fillSimpleSelect(
        row.querySelector(".task-priority-select"),
        taskFormOptions.priority_levels,
        "Select priority",
    );

    fillSelect(
        row.querySelector(".task-house-select"),
        taskFormOptions.houses,
        "id",
        "number",
        "Select house",
    );

    fillSelect(
        row.querySelector(".task-pen-select"),
        [],
        "number",
        "label",
        "Select pen",
    );
}

function bindTaskRowEvents(row) {
    if (!row) return;

    row.querySelectorAll("input, select, textarea").forEach((field) => {
        field.addEventListener("input", () => clearTaskFieldError(field));
        field.addEventListener("change", () => clearTaskFieldError(field));
    });

    row.querySelector(".task-house-select")?.addEventListener("change", async () => {
        await loadPensForTaskRow(row);
    });

    row.querySelector(".task-category-select")?.addEventListener("change", async () => {
        await loadPensForTaskRow(row);
    });

    row.querySelector(".manager-task-row-remove-btn")?.addEventListener("click", () => {
        if (document.querySelectorAll("#tasksContainer .manager-task-row").length <= 1) {
            return;
        }

        row.remove();
        updateTaskCountBadge();
        clearAddTaskFormError();
    });
}

async function loadPensForTaskRow(row, selectedPenValue = "") {
    const houseSelect = row.querySelector(".task-house-select");
    const penSelect = row.querySelector(".task-pen-select");
    const taskType = row.querySelector(".task-category-select")?.value || "";
    if (!penSelect) return;

    if (!houseSelect?.value) {
        fillSelect(penSelect, [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
        return;
    }

    try {
        const params = new URLSearchParams();
        if (taskType) {
            params.set("task_type", taskType);
        }
        const query = params.toString() ? `?${params.toString()}` : "";
        const response = await fetch(`/api/manager/tasks/houses/${houseSelect.value}/pens${query}`);
        const data = await response.json();
        fillSelect(penSelect, data.pens || [], "number", "label", "Select pen");
        if (selectedPenValue) {
            penSelect.value = selectedPenValue;
        }
        setupTaskSelectPlaceholderState();
    } catch (error) {
        console.error("Failed to load pens for selected house.", error);
        fillSelect(penSelect, [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
    }
}

function updateTaskCountBadge() {
    const rows = [...document.querySelectorAll("#tasksContainer .manager-task-row")];
    const badge = document.getElementById("taskCountBadge");

    rows.forEach((row, index) => {
        const title = row.querySelector(".manager-task-row-title");
        if (title) {
            title.textContent = `Task ${index + 1}`;
        }
    });

    if (badge) {
        badge.textContent = rows.length;
    }
}

function validateAddTaskForm(form, payload) {
    const missingFields = [];
    const tasks = [];

    clearAddTaskRequiredFieldHighlights(false);

    if (!String(payload.worker_name ?? "").trim()) {
        missingFields.push({ name: "worker_name", label: "Assign Flockman" });
        form.elements.worker_name?.closest(".manager-task-form-field")?.classList.add("has-error");
    }

    const rows = [...document.querySelectorAll("#tasksContainer .manager-task-row")];
    let previousFinishAt = null;
    let hasSequenceErrors = false;

    rows.forEach((row) => {
        const values = getTaskRowValues(row);

        Object.entries({
            task_category: values.taskCategory,
            priority_level: values.priorityLevel,
            house_number: values.houseNumber,
            pen_number: values.penNumber,
            date_assigned: values.dateAssigned,
            time_assigned: values.timeAssigned,
        }).forEach(([name, value]) => {
            if (!String(value ?? "").trim()) {
                missingFields.push({ name });
                row.querySelector(`[name="${name}"]`)?.closest(".manager-task-form-field")?.classList.add("has-error");
            }
        });

        if (values.dateAssigned && values.timeAssigned) {
            const finishAt = new Date(`${values.dateAssigned}T${values.timeAssigned}:00`);
            if (previousFinishAt && finishAt <= previousFinishAt) {
                hasSequenceErrors = true;
                row.querySelector("[name='date_assigned']")?.closest(".manager-task-form-field")?.classList.add("has-error");
                row.querySelector("[name='time_assigned']")?.closest(".manager-task-form-field")?.classList.add("has-error");
            }
            previousFinishAt = finishAt;
        }

        tasks.push(values);
    });

    if (missingFields.length) {
        shouldTrackAddTaskRequiredHighlights = true;
        const firstMissing = form.querySelector(`[name="${missingFields[0].name}"]`);
        firstMissing?.focus();
    } else {
        shouldTrackAddTaskRequiredHighlights = false;
    }

    if (missingFields.length) {
        return {
            isValid: false,
            missingFields,
            tasks,
        };
    }

    if (hasSequenceErrors) {
        return {
            isValid: false,
            missingFields,
            tasks,
            message: "Each succeeding task must finish later than the task before it.",
        };
    }

    return {
        isValid: missingFields.length === 0,
        missingFields,
        tasks,
    };
}

function getTaskRowValues(row) {
    return {
        taskCategory: row.querySelector("[name='task_category']")?.value || "",
        priorityLevel: row.querySelector("[name='priority_level']")?.value || "",
        houseNumber: row.querySelector("[name='house_number']")?.value || "",
        penNumber: row.querySelector("[name='pen_number']")?.value || "",
        dateAssigned: row.querySelector("[name='date_assigned']")?.value || "",
        timeAssigned: row.querySelector("[name='time_assigned']")?.value || "",
        detailedTask: row.querySelector("[name='detailed_task']")?.value || "",
    };
}

function showAddTaskFormError(formError, messageOrFields) {
    if (!formError) return;

    if (Array.isArray(messageOrFields)) {
        formError.textContent = "Please fill in the required fields.";
    } else {
        formError.textContent = messageOrFields;
    }

    formError.classList.add("show");
    formError.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function clearAddTaskFormError() {
    const form = document.getElementById("addTaskForm");
    const formError = document.getElementById("addTaskFormError");

    formError?.classList.remove("show");
    if (formError) {
        formError.textContent = "";
    }

    clearAddTaskRequiredFieldHighlights();
}

function clearAddTaskRequiredFieldHighlights(resetTracking = true) {
    const form = document.getElementById("addTaskForm");

    if (resetTracking) {
        shouldTrackAddTaskRequiredHighlights = false;
    }

    form?.querySelectorAll(".manager-task-form-field.has-error").forEach((field) => {
        field.classList.remove("has-error");
    });
}

function showEditTaskFormError(formError, message) {
    if (!formError) return;

    formError.textContent = message;
    formError.classList.add("show");
    formError.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

function clearEditTaskFormError() {
    const formError = document.getElementById("editTaskFormError");

    formError?.classList.remove("show");
    if (formError) {
        formError.textContent = "";
    }

    clearEditTaskRequiredFieldHighlights();
}

function clearEditTaskRequiredFieldHighlights(resetTracking = true) {
    const form = document.getElementById("editTaskForm");

    form?.querySelectorAll(".manager-task-form-field.has-error").forEach((field) => {
        field.classList.remove("has-error");
    });
}

function clearEditTaskFieldError(field) {
    field.closest(".manager-task-form-field")?.classList.remove("has-error");

    const form = document.getElementById("editTaskForm");
    const hasErrors = form?.querySelector(".manager-task-form-field.has-error");
    if (!hasErrors) {
        clearEditTaskFormError();
    }
}

function clearTaskFieldError(field) {
    syncAddTaskRequiredFieldHighlight(field);

    const form = document.getElementById("addTaskForm");
    const hasErrors = form?.querySelector(".manager-task-form-field.has-error");
    if (!hasErrors) {
        clearAddTaskFormError();
    }
}

function syncAddTaskRequiredFieldHighlight(field) {
    const fieldWrapper = field.closest(".manager-task-form-field");
    const isRequiredField = addTaskRequiredFields.some(({ name }) => name === field.name);

    if (!isRequiredField) {
        fieldWrapper?.classList.remove("has-error");
        return;
    }

    const isMissing = !String(field.value ?? "").trim();

    if (shouldTrackAddTaskRequiredHighlights && isMissing) {
        fieldWrapper?.classList.add("has-error");
        return;
    }

    fieldWrapper?.classList.remove("has-error");
}

function openTaskModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.add("show");
    document.body.style.overflow = "hidden";
}

function closeTaskModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.classList.remove("show");
    document.body.style.overflow = "";
}

function animateTaskSections() {
    const sections = document.querySelectorAll(".manager-task-section");

    sections.forEach((section, index) => {
        section.style.opacity = "0";
        section.style.transform = "translateY(20px)";

        setTimeout(
            () => {
                section.style.transition =
                    "opacity 0.5s ease, transform 0.5s ease";
                section.style.opacity = "1";
                section.style.transform = "translateY(0)";
            },
            120 + index * 130,
        );
    });
}

function animateTaskRows() {
    const rows = document.querySelectorAll(".manager-task-table tbody tr");

    rows.forEach((row, index) => {
        row.style.opacity = "0";
        row.style.transform = "translateY(12px)";

        setTimeout(
            () => {
                row.style.transition =
                    "opacity 0.38s ease, transform 0.38s ease";
                row.style.opacity = "1";
                row.style.transform = "translateY(0)";
            },
            320 + index * 70,
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

function setupTaskSelectPlaceholderState() {
    const selects = document.querySelectorAll(".task-select-placeholder");

    selects.forEach((select) => {
        const updateState = () => {
            if (select.value === "") {
                select.classList.remove("has-value");
            } else {
                select.classList.add("has-value");
            }
        };

        updateState();
        select.removeEventListener(
            "change",
            select._taskPlaceholderHandler || (() => {}),
        );

        select._taskPlaceholderHandler = updateState;
        select.addEventListener("change", select._taskPlaceholderHandler);
    });
}
async function loadPensForHouse(houseId) {
    const penSelect = document.getElementById("taskPenNumber");
    if (!penSelect) return;

    if (!houseId) {
        fillSelect(penSelect, [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
        return;
    }

    try {
        const response = await fetch(`/api/manager/tasks/houses/${houseId}/pens`);
        const data = await response.json();
        fillSelect(penSelect, data.pens || [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
    } catch (error) {
        console.error("Failed to load pens for selected house.", error);
        fillSelect(penSelect, [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
    }
}

async function updateTaskStatus(taskId, newStatus) {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch(`/api/manager/tasks/${taskId}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token || "",
            },
            body: JSON.stringify({ status: newStatus }),
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(errorText || `Failed to update task (${response.status}).`);
        }

        await renderManagerTasks();
        alert(`Task marked as ${newStatus}!`);
    } catch (error) {
        console.error("Failed to update task status:", error);
        alert("Error updating task: " + error.message);
    }
}

function fillSelect(select, items, valueKey, labelKey, placeholder) {
    if (!select) return;

    select.innerHTML = `
        <option value="">${placeholder}</option>
        ${items.map((item) => {
            const value = item[valueKey];
            const label = item.label ?? item[labelKey];
            const disabled = item.disabled ? 'disabled' : '';
            const note = item.disabled ? ' (pending task)' : '';
            const style = item.disabled ? 'style="color:#999;"' : '';
            return `<option value="${value}" ${disabled} ${style}>${label}${note}</option>`;
        }).join("")}
    `;
}
