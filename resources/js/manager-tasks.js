document.addEventListener("DOMContentLoaded", async () => {
    await Promise.all([renderManagerTasks(false), loadTaskFormOptions()]);

    document.querySelectorAll(".task-date-input").forEach(applyTaskDateMinimum);
    setupTaskFilters();
    setupTaskRowPaginationControls();
    setupTaskSelectPlaceholderState();
    setupManagerTaskModals();
    setupAddTaskModal();
    setupEditTaskModal();
    setupDeleteTaskModal();
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
        { key: "house_number", label: "House<br>Number" },
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
        { key: "house_number", label: "House<br>Number" },
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
        { key: "house_number", label: "House<br>Number" },
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
let pendingAddTasks = [];
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
const addTaskRequiredFields = [
    { name: "worker_name", label: "Assign Flockman" },
    { name: "task_category", label: "Task" },
    { name: "priority_level", label: "Priority Level" },
    { name: "house_number", label: "House Number" },
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

        taskDataCache = {
            pending: data.pending || [],
            for_approval: data.for_approval || [],
            completed: data.completed || [],
        };
        syncAvailableHouseOptions();

        refreshTaskFilterOptions();
        if (shouldRender) {
            renderCurrentTaskTable();
        }
    } catch (error) {
        console.error("Failed to load manager tasks.", error);
    }
}

let formOptionsCache = null;

async function loadTaskFormOptions() {
    try {
        const response = await fetch("/api/manager/tasks/form-options");
        const data = await response.json();
        formOptionsCache = data;
        syncAvailableHouseOptions();

        fillSelect(
            document.getElementById("taskWorkerName"),
            (data.workers || []).filter((worker) => !worker.disabled),
            "id",
            "name",
            "Select worker",
        );

        refreshTaskFilterOptions();
        setupTaskSelectPlaceholderState();
    } catch (error) {
        console.error("Failed to load task form options.", error);
    }
}

function fillSimpleSelect(select, items, placeholder) {
    if (!select) return;

    select.innerHTML = `
        <option value="">${escapeHtml(placeholder)}</option>
        ${items.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`).join("")}
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

function syncAvailableHouseOptions() {
    const taskHouseNumbers = Object.values(taskDataCache)
        .flat()
        .map((task) => String(task.house_number ?? "").trim())
        .filter(Boolean);
    const formHouseNumbers = (formOptionsCache?.houses || [])
        .map((house) => String(house.number ?? "").trim())
        .filter(Boolean);

    availableHouseOptions = [...new Set([...taskHouseNumbers, ...formHouseNumbers])];
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
    if (!tbody) return;

    table?.classList.toggle("manager-task-table-for-approval", section === "for_approval");
    table?.classList.toggle("manager-task-table-completed", section === "completed");

    const columns = TASK_TABLE_COLUMNS[section] || TASK_TABLE_COLUMNS.pending;
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
    if (section === "for_approval") bindVerifyButtons();
    animateTaskRows();
}

function renderTaskCell(key, item) {
    if (key === "priority") {
        return renderTaskPriorityBadge(item.priority);
    }

    if (key === "photo") {
        return item.photo_url
            ? `<button
                type="button"
                class="manager-task-photo-link"
                data-photo-name="${escapeHtml(item.photo_name)}"
                data-photo-url="${escapeHtml(item.photo_url)}"
            >
                <img src="${escapeHtml(item.photo_url)}" alt="${escapeHtml(item.photo_name)}" style="max-width:120px; max-height:80px; object-fit:cover; border-radius:6px;">
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

    return item[key] ?? "";
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

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
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

function generateTaskRowHtml(rowIndex) {
    return `
        <div class="manager-task-row" data-row-index="${rowIndex}">
            <div class="manager-task-row-header">
                <h4 class="manager-task-row-title">Task ${rowIndex + 1}</h4>
                ${rowIndex > 0 ? `<button type="button" class="manager-task-row-remove-btn remove-task-row-btn" data-row-index="${rowIndex}" title="Remove task">&times;</button>` : ''}
            </div>
            <div class="manager-task-row-grid">
                <div class="manager-task-form-field">
                    <label>Task*</label>
                    <select name="task_category_${rowIndex}" class="task-select-placeholder task-category-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Priority Level*</label>
                    <select name="priority_level_${rowIndex}" class="task-select-placeholder task-priority-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>House Number*</label>
                    <select name="house_number_${rowIndex}" class="task-select-placeholder task-house-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Pen Number*</label>
                    <select name="pen_number_${rowIndex}" class="task-select-placeholder task-pen-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Date to finish*</label>
                    <input type="date" name="date_assigned_${rowIndex}" class="task-date-input" min="${getTodayDateValue()}">
                </div>
                <div class="manager-task-form-field">
                    <label>Time to finish*</label>
                    <input type="time" name="time_assigned_${rowIndex}" class="task-time-input">
                </div>
                <div class="manager-task-form-field full">
                    <label>Detailed Task</label>
                    <textarea name="detailed_task_${rowIndex}" class="task-detailed-textarea" rows="2" placeholder="Write a clear and specific task instruction here."></textarea>
                </div>
            </div>
        </div>
    `;
}

function populateTaskRowOptions(rowIndex) {
    const row = document.querySelector(`[data-row-index="${rowIndex}"]`);
    if (!row || !formOptionsCache) return;

    const taskCategorySelect = row.querySelector(".task-category-select");
    const prioritySelect = row.querySelector(".task-priority-select");
    const houseSelect = row.querySelector(".task-house-select");
    const penSelect = row.querySelector(".task-pen-select");

    fillSimpleSelect(
        taskCategorySelect,
        formOptionsCache.task_categories || [],
        "Select task category",
    );

    fillSimpleSelect(
        prioritySelect,
        formOptionsCache.priority_levels || [],
        "Select priority",
    );

    fillSelect(
        houseSelect,
        formOptionsCache.houses || [],
        "id",
        "number",
        "Select house",
    );

    fillSelect(
        penSelect,
        [],
        "number",
        "label",
        "Select pen",
    );

    const refreshPens = async () => {
        const selectedHouseId = houseSelect?.value;
        if (!selectedHouseId) {
            fillSelect(penSelect, [], "number", "label", "Select pen");
            return;
        }

        try {
            const response = await fetch(getTaskPensUrl(selectedHouseId, taskCategorySelect?.value));
            const data = await response.json();
            fillSelect(penSelect, data.pens || [], "number", "label", "Select pen");
            setupTaskSelectPlaceholderState();
        } catch (error) {
            console.error("Failed to load pens:", error);
        }
    };

    houseSelect?.addEventListener("change", refreshPens);
    taskCategorySelect?.addEventListener("change", refreshPens);
}

function setupAddTaskModal() {
    const openButton = document.getElementById("openAddTaskModal");
    const form = document.getElementById("addTaskForm");
    const formError = document.getElementById("addTaskFormError");
    const tasksContainer = document.getElementById("tasksContainer");
    const addTaskRowBtn = document.getElementById("addTaskRowBtn");
    let nextTaskRowIndex = 0;

    if (openButton) {
        openButton.addEventListener("click", async () => {
            await loadTaskFormOptions();
            clearAddTaskFormError();
            
            // Initialize with one task row
            nextTaskRowIndex = 0;
            tasksContainer.innerHTML = "";
            addNewTaskRow();
            
            openTaskModal("addTaskModal");
        });
    }

    function addNewTaskRow(options = {}) {
        const rowIndex = nextTaskRowIndex;
        nextTaskRowIndex += 1;
        const rowHtml = generateTaskRowHtml(rowIndex);
        tasksContainer.insertAdjacentHTML("beforeend", rowHtml);
        
        populateTaskRowOptions(rowIndex);

        // Set up remove button for this row
        const removeBtn = tasksContainer.querySelector(`[data-row-index="${rowIndex}"] .remove-task-row-btn`);
        if (removeBtn) {
            removeBtn.addEventListener("click", (e) => {
                e.preventDefault();
                const rowElement = tasksContainer.querySelector(`[data-row-index="${rowIndex}"]`);
                rowElement.remove();
                updateTaskCountBadge();
            });
        }

        // Set up field listeners for error clearing
        const row = tasksContainer.querySelector(`[data-row-index="${rowIndex}"]`);
        applyTaskDateMinimum(row.querySelector(".task-date-input"));

        row.querySelectorAll("input, select, textarea").forEach((field) => {
            field.addEventListener("input", () => clearTaskFieldError(field));
            field.addEventListener("change", () => clearTaskFieldError(field));
        });

        updateTaskCountBadge();

        if (options.scrollToRow) {
            requestAnimationFrame(() => {
                row.scrollIntoView({ behavior: "smooth", block: "start" });
                row.querySelector(".task-category-select")?.focus({ preventScroll: true });
            });
        }
    }

    function updateTaskCountBadge() {
        tasksContainer.querySelectorAll(".manager-task-row").forEach((row, index) => {
            const title = row.querySelector(".manager-task-row-title");
            if (title) {
                title.textContent = `Task ${index + 1}`;
            }
        });

        const countBadge = document.getElementById("taskCountBadge");
        const taskCount = tasksContainer.querySelectorAll(".manager-task-row").length;
        if (countBadge) {
            countBadge.textContent = taskCount;
        }
    }

    if (addTaskRowBtn) {
        addTaskRowBtn.addEventListener("click", (e) => {
            e.preventDefault();
            addNewTaskRow({ scrollToRow: true });
        });
    }

    // Only set up form submission handler once
    if (form && !form._taskSubmitHandlerSet) {
        form._taskSubmitHandlerSet = true;

        const handleFormSubmit = async (event) => {
            event.preventDefault();

            const workerNameSelect = document.getElementById("taskWorkerName");
            const workerId = workerNameSelect?.value;

            if (!workerId) {
                showAddTaskFormError(formError, "Please select a flockman.");
                return;
            }

            // Collect all task rows
            const taskRows = tasksContainer.querySelectorAll(".manager-task-row");
            if (taskRows.length === 0) {
                showAddTaskFormError(formError, "Please add at least one task.");
                return;
            }

            // Validate all tasks
            const tasks = [];
            let hasRequiredErrors = false;
            let hasScheduleErrors = false;
            let hasSequenceErrors = false;
            let previousFinishAt = null;
            clearAddTaskRequiredFieldHighlights(false);

            taskRows.forEach((row) => {
                const rowIndex = row.dataset.rowIndex;
                const taskCategoryField = row.querySelector(`[name="task_category_${rowIndex}"]`);
                const priorityLevelField = row.querySelector(`[name="priority_level_${rowIndex}"]`);
                const houseNumberField = row.querySelector(`[name="house_number_${rowIndex}"]`);
                const penNumberField = row.querySelector(`[name="pen_number_${rowIndex}"]`);
                const timeAssignedField = row.querySelector(`[name="time_assigned_${rowIndex}"]`);
                const dateAssignedField = row.querySelector(`[name="date_assigned_${rowIndex}"]`);
                const detailedTaskField = row.querySelector(`[name="detailed_task_${rowIndex}"]`);
                const requiredFields = [
                    taskCategoryField,
                    priorityLevelField,
                    houseNumberField,
                    penNumberField,
                    timeAssignedField,
                    dateAssignedField,
                ];
                const taskCategory = taskCategoryField?.value || "";
                const priorityLevel = priorityLevelField?.value || "";
                const houseNumber = houseNumberField?.value || "";
                const penNumber = penNumberField?.value || "";
                const timeAssigned = timeAssignedField?.value || "";
                const dateAssigned = dateAssignedField?.value || "";
                const detailedTask = detailedTaskField?.value || "";

                // Check required fields
                requiredFields.forEach((field) => {
                    if (!String(field?.value || "").trim()) {
                        field?.closest(".manager-task-form-field")?.classList.add("has-error");
                    }
                });

                if (requiredFields.some((field) => !String(field?.value || "").trim())) {
                    hasRequiredErrors = true;
                    return;
                }

                if (isPastTaskDate(dateAssigned)) {
                    hasScheduleErrors = true;
                    dateAssignedField?.closest(".manager-task-form-field")?.classList.add("has-error");
                    return;
                }

                const toLocalDateTimeString = (dateObj) => {
                    const pad = (value) => String(value).padStart(2, "0");
                    return `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())}` +
                        `T${pad(dateObj.getHours())}:${pad(dateObj.getMinutes())}:${pad(dateObj.getSeconds())}`;
                };

                const timeAssignedNow = toLocalDateTimeString(new Date());
                const finishBy = dateAssigned && timeAssigned ? `${dateAssigned}T${timeAssigned}:00` : null;
                const finishAt = new Date(finishBy);

                if (previousFinishAt && finishAt <= previousFinishAt) {
                    hasSequenceErrors = true;
                    timeAssignedField?.closest(".manager-task-form-field")?.classList.add("has-error");
                    dateAssignedField?.closest(".manager-task-form-field")?.classList.add("has-error");
                    return;
                }

                previousFinishAt = finishAt;

                tasks.push({
                    user_employeeid: workerId,
                    tasktype: taskCategory,
                    prioritylevel: priorityLevel,
                    house_houseid: houseNumber,
                    pennumber: penNumber,
                    timeassigned: timeAssignedNow,
                    finishby: finishBy,
                    detailedtask: detailedTask,
                    status: "Pending",
                });
            });

            if (hasRequiredErrors) {
                showAddTaskFormError(formError, "Please fill in all required fields (marked with *).");
                return;
            }

            if (hasScheduleErrors) {
                showAddTaskFormError(formError, "Date to finish cannot be earlier than today.");
                return;
            }

            if (hasSequenceErrors) {
                showAddTaskFormError(formError, "Each succeeding task must finish later than the task before it.");
                return;
            }

            pendingAddTasks = tasks;

            const confirmText = document.getElementById("confirmAddTaskText");
            if (confirmText) {
                confirmText.textContent = tasks.length === 1
                    ? "Are you sure you want to assign this task?"
                    : `Are you sure you want to assign these ${tasks.length} tasks?`;
            }

            openTaskModal("confirmAddTaskModal");
        };

        form.addEventListener("submit", handleFormSubmit);
    }

    const confirmAddTaskButton = document.getElementById("confirmAddTask");
    if (confirmAddTaskButton && !confirmAddTaskButton._taskConfirmHandlerSet) {
        confirmAddTaskButton._taskConfirmHandlerSet = true;
        confirmAddTaskButton.addEventListener("click", () => submitPendingAddTasks(form, formError, tasksContainer));
    }
}

async function submitPendingAddTasks(form, formError, tasksContainer) {
    if (!pendingAddTasks.length) {
        closeTaskModal("confirmAddTaskModal");
        return;
    }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        for (const task of pendingAddTasks) {
            const response = await fetch("/api/manager/tasks", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": token || "",
                },
                body: JSON.stringify(task),
            });

            if (!response.ok) {
                const responseText = await response.text();
                throw new Error(responseText || `Failed to save task (${response.status}).`);
            }
        }

        pendingAddTasks = [];
        await renderManagerTasks();
        closeTaskModal("confirmAddTaskModal");
        closeTaskModal("addTaskModal");
        form.reset();
        tasksContainer.innerHTML = "";
        setupTaskSelectPlaceholderState();
    } catch (error) {
        console.error("Failed to save tasks:", error);
        closeTaskModal("confirmAddTaskModal");
        showAddTaskFormError(formError, "Unable to save tasks. Please try again.");
    }
}

function validateAddTaskRequiredFields(form, payload) {
    const missingFields = addTaskRequiredFields.filter(({ name }) => {
        return !String(payload[name] ?? "").trim();
    });

    clearAddTaskRequiredFieldHighlights(false);

    missingFields.forEach(({ name }) => {
        const field = form.elements[name];
        field?.closest(".manager-task-form-field")?.classList.add("has-error");
    });

    if (missingFields.length) {
        shouldTrackAddTaskRequiredHighlights = true;
        form.elements[missingFields[0].name]?.focus();
    } else {
        shouldTrackAddTaskRequiredHighlights = false;
    }

    return missingFields;
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
    if (!document.querySelector(".manager-task-modal-backdrop.show")) {
        document.body.style.overflow = "";
    }
}

function showTaskNoticeModal(message, title = "Notice") {
    return new Promise((resolve) => {
        document.getElementById("taskNoticeModal")?.remove();

        const modal = document.createElement("div");
        modal.className = "manager-task-modal-backdrop confirm-modal-top show";
        modal.id = "taskNoticeModal";
        modal.innerHTML = `
            <div class="manager-task-confirm-modal">
                <div class="manager-task-modal-header center">
                    <h2></h2>
                    <div class="manager-task-header-line"></div>
                </div>
                <div class="manager-task-confirm-body">
                    <p></p>
                    <div class="manager-task-confirm-actions">
                        <button type="button" class="manager-task-btn confirm" data-notice-ok>OK</button>
                    </div>
                </div>
            </div>
        `;

        modal.querySelector("h2").textContent = title;
        modal.querySelector("p").textContent = message;

        const close = () => {
            modal.remove();
            if (!document.querySelector(".manager-task-modal-backdrop.show")) {
                document.body.style.overflow = "";
            }
            resolve();
        };

        document.body.style.overflow = "hidden";
        modal.querySelector("[data-notice-ok]").addEventListener("click", close);
        modal.addEventListener("click", (event) => {
            if (event.target === modal) close();
        });

        document.body.appendChild(modal);
    });
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
        const taskType = document.getElementById("taskCategory")?.value || "";
        const response = await fetch(getTaskPensUrl(houseId, taskType));
        const data = await response.json();
        fillSelect(penSelect, data.pens || [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
    } catch (error) {
        console.error("Failed to load pens for selected house.", error);
        fillSelect(penSelect, [], "number", "label", "Select pen");
        setupTaskSelectPlaceholderState();
    }
}

function getTaskPensUrl(houseId, taskType = "") {
    const params = new URLSearchParams();
    if (taskType) {
        params.set("task_type", taskType);
    }

    const query = params.toString();
    return `/api/manager/tasks/houses/${houseId}/pens${query ? `?${query}` : ""}`;
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
        await showTaskNoticeModal(`Task marked as ${newStatus}.`, "Task Updated");
    } catch (error) {
        console.error("Failed to update task status:", error);
        await showTaskNoticeModal(`Error updating task: ${error.message}`, "Unable to Update Task");
    }
}

function fillSelect(select, items, valueKey, labelKey, placeholder) {
    if (!select) return;

    select.innerHTML = `
        <option value="">${escapeHtml(placeholder)}</option>
        ${items.map((item) => {
            const value = item[valueKey];
            const label = item.label ?? item[labelKey];
            const disabled = item.disabled ? 'disabled' : '';
            const note = item.disabled ? ` (${item.disabledReason || 'pending task'})` : '';
            const style = item.disabled ? 'style="color:#999;"' : '';
            return `<option value="${escapeHtml(value)}" ${disabled} ${style}>${escapeHtml(label)}${escapeHtml(note)}</option>`;
        }).join("")}
    `;
}

let editingTaskId = null;
let pendingEditTaskPayload = null;

function setupEditTaskModal() {
    document.addEventListener('click', (event) => {
        const editBtn = event.target.closest('.manager-task-edit-btn');
        if (!editBtn) return;

        editingTaskId = editBtn.dataset.taskId;
        openEditTaskModal(editingTaskId);
    });

    const editForm = document.getElementById('editTaskForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditTaskSubmit);
    }

    const confirmEditTaskButton = document.getElementById('confirmEditTask');
    if (confirmEditTaskButton && !confirmEditTaskButton._taskEditConfirmHandlerSet) {
        confirmEditTaskButton._taskEditConfirmHandlerSet = true;
        confirmEditTaskButton.addEventListener('click', submitPendingEditTask);
    }

    setupEditTaskHouseChange();
}

function openEditTaskModal(taskId) {
    const allTasks = [...taskDataCache.pending, ...taskDataCache.for_approval, ...taskDataCache.completed];
    const task = allTasks.find(t => t.id == taskId);

    if (!task) {
        console.error('Task not found');
        return;
    }

    populateEditTaskForm(task);
    openTaskModal('editTaskModal');
}

function populateEditTaskForm(task) {
    const workerNameInput = document.querySelector('#editTaskModal #editTaskWorkerName');
    const taskCategorySelect = document.querySelector('#editTaskModal .task-category-select');
    const prioritySelect = document.querySelector('#editTaskModal .task-priority-select');
    const houseSelect = document.querySelector('#editTaskModal .task-house-select');
    const penSelect = document.querySelector('#editTaskModal .task-pen-select');
    const timeInput = document.querySelector('#editTaskModal .task-time-input');
    const dateInput = document.querySelector('#editTaskModal .task-date-input');
    const detailedTaskTextarea = document.querySelector('#editTaskModal .task-detailed-textarea');

    applyTaskDateMinimum(dateInput);

    // Populate worker name (read-only)
    if (workerNameInput) {
        workerNameInput.value = task.name || 'Unknown';
    }

    if (formOptionsCache) {
        // Ensure the current task type is included in the options
        const taskCategories = formOptionsCache.task_categories || [];
        const currentTaskType = (task.task_assigned || '').trim();

        if (currentTaskType && !taskCategories.includes(currentTaskType)) {
            taskCategories.unshift(currentTaskType);
        }

        fillSimpleSelect(taskCategorySelect, taskCategories, 'Select task category');
        fillSimpleSelect(prioritySelect, formOptionsCache.priority_levels || [], 'Select priority');
        fillSelect(houseSelect, formOptionsCache.houses || [], 'id', 'number', 'Select house');
    }

    // Set task category and priority by matching the values
    taskCategorySelect.value = (task.task_assigned || '').trim();
    prioritySelect.value = (task.priority || '').trim();

    // Set house dropdown value
    houseSelect.value = task.house_id || '';

    if (task.finish_by) {
        const [date, time] = task.finish_by.split(' ');
        dateInput.value = date;
        if (time) {
            timeInput.value = time.slice(0, 5);
        }
    }

    detailedTaskTextarea.value = task.detailed_task || '';

    // Update select placeholder state for task category and priority
    setupTaskSelectPlaceholderState();

    // Load pens for the selected house
    loadPensForEditForm(task.house_id, task.pen_id, taskCategorySelect.value);
}

async function loadPensForEditForm(houseId, penId, taskType = '') {
    const penSelect = document.querySelector('#editTaskModal .task-pen-select');

    if (!houseId) {
        fillSelect(penSelect, [], 'number', 'label', 'Select pen');
        setupTaskSelectPlaceholderState();
        return;
    }

    try {
        const response = await fetch(getTaskPensUrl(houseId, taskType));
        const data = await response.json();
        fillSelect(penSelect, data.pens || [], 'number', 'label', 'Select pen');

        // Pre-select the pen
        if (penId) {
            penSelect.value = penId;
        }

        setupTaskSelectPlaceholderState();
    } catch (error) {
        console.error('Failed to load pens:', error);
        fillSelect(penSelect, [], 'number', 'label', 'Select pen');
        setupTaskSelectPlaceholderState();
    }
}

function setupEditTaskHouseChange() {
    const houseSelect = document.querySelector('#editTaskModal .task-house-select');
    const taskCategorySelect = document.querySelector('#editTaskModal .task-category-select');
    const penSelect = document.querySelector('#editTaskModal .task-pen-select');
    if (!houseSelect) return;

    const refreshPens = async () => {
        const selectedHouseId = houseSelect.value;

        if (!selectedHouseId) {
            fillSelect(penSelect, [], 'number', 'label', 'Select pen');
            return;
        }

        try {
            const response = await fetch(getTaskPensUrl(selectedHouseId, taskCategorySelect?.value));
            const data = await response.json();
            fillSelect(penSelect, data.pens || [], 'number', 'label', 'Select pen');
            setupTaskSelectPlaceholderState();
        } catch (error) {
            console.error('Failed to load pens:', error);
        }
    };

    houseSelect.addEventListener('change', refreshPens);
    taskCategorySelect?.addEventListener('change', refreshPens);
}

async function handleEditTaskSubmit(event) {
    event.preventDefault();

    if (!editingTaskId) {
        await showTaskNoticeModal('Error: Task ID not found.', "Unable to Edit Task");
        return;
    }

    const form = event.target;
    const formError = document.getElementById('editTaskFormError');
    const taskCategory = form.querySelector('[name="task_category"]').value;
    const priorityLevel = form.querySelector('[name="priority_level"]').value;
    const houseNumber = form.querySelector('[name="house_number"]').value;
    const penNumber = form.querySelector('[name="pen_number"]').value;
    const timeAssigned = form.querySelector('[name="time_assigned"]').value;
    const dateAssigned = form.querySelector('[name="date_assigned"]').value;
    const detailedTask = form.querySelector('[name="detailed_task"]').value;

    if (!taskCategory || !priorityLevel || !houseNumber || !penNumber || !timeAssigned || !dateAssigned) {
        formError.textContent = 'Please fill in all required fields.';
        formError.classList.add('show');
        return;
    }

    if (isPastTaskDate(dateAssigned)) {
        const dateField = form.querySelector('[name="date_assigned"]');
        dateField?.closest('.manager-task-form-field')?.classList.add('has-error');
        formError.textContent = 'Date to finish cannot be earlier than today.';
        formError.classList.add('show');
        return;
    }

    const finishBy = dateAssigned && timeAssigned ? `${dateAssigned} ${timeAssigned}:00` : null;

    pendingEditTaskPayload = {
        taskId: editingTaskId,
        formError,
        tasktype: taskCategory,
        prioritylevel: priorityLevel,
        house_houseid: houseNumber,
        pennumber: penNumber,
        finishby: finishBy,
        detailedtask: detailedTask,
    };

    const confirmText = document.getElementById('confirmEditTaskText');
    if (confirmText) {
        confirmText.textContent = `Save changes to ${taskCategory}?`;
    }

    openTaskModal('confirmEditTaskModal');
}

async function submitPendingEditTask() {
    if (!pendingEditTaskPayload) {
        closeTaskModal('confirmEditTaskModal');
        return;
    }

    const confirmButton = document.getElementById('confirmEditTask');
    const payload = pendingEditTaskPayload;

    try {
        confirmButton.disabled = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch(`/api/manager/tasks/${payload.taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify({
                tasktype: payload.tasktype,
                prioritylevel: payload.prioritylevel,
                house_houseid: payload.house_houseid,
                pennumber: payload.pennumber,
                finishby: payload.finishby,
                detailedtask: payload.detailedtask,
            }),
        });

        if (!response.ok) {
            const responseText = await response.text();
            throw new Error(responseText || `Failed to update task (${response.status}).`);
        }

        await renderManagerTasks();
        closeTaskModal('confirmEditTaskModal');
        closeTaskModal('editTaskModal');
        editingTaskId = null;
        pendingEditTaskPayload = null;
        payload.formError.classList.remove('show');
        payload.formError.textContent = '';
    } catch (error) {
        console.error('Failed to update task:', error);
        closeTaskModal('confirmEditTaskModal');
        payload.formError.textContent = 'Unable to update task. Please try again.';
        payload.formError.classList.add('show');
    } finally {
        confirmButton.disabled = false;
    }
}

let deletingTaskId = null;

function setupDeleteTaskModal() {
    document.addEventListener('click', (event) => {
        const deleteBtn = event.target.closest('.manager-task-delete-btn');
        if (!deleteBtn) return;

        deletingTaskId = deleteBtn.dataset.taskId;
        openTaskModal('deleteTaskModal');
    });

    const confirmButton = document.getElementById('confirmTaskDelete');
    if (confirmButton) {
        confirmButton.addEventListener('click', handleDeleteTaskConfirm);
    }
}

async function handleDeleteTaskConfirm() {
    if (!deletingTaskId) {
        await showTaskNoticeModal('Error: Task ID not found.', "Unable to Delete Task");
        return;
    }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch(`/api/manager/tasks/${deletingTaskId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token || '',
            },
        });

        if (!response.ok) {
            const responseText = await response.text();
            throw new Error(responseText || `Failed to delete task (${response.status}).`);
        }

        await renderManagerTasks();
        closeTaskModal('deleteTaskModal');
        deletingTaskId = null;
    } catch (error) {
        console.error('Failed to delete task:', error);
        await showTaskNoticeModal('Unable to delete task. Please try again.', "Unable to Delete Task");
    }
}
