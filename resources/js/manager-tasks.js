document.addEventListener("DOMContentLoaded", async () => {
    await Promise.all([renderManagerTasks(), loadTaskFormOptions()]);

    setupTaskFilters();
    setupTaskRowPaginationControls();
    setupTaskSelectPlaceholderState();
    setupManagerTaskModals();
    setupAddTaskModal();
    setupManagerProfileModal();
    animateTaskSections();
});

const ALL_HOUSES_OPTION = "All houses";
const ALL_PRIORITY_OPTION = "All priority";
const PRIORITY_ORDER = ["Low", "Medium", "High"];
const TASK_ROWS_PER_PAGE = 5;

let taskPendingVerify = null;
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
    pending: { house: ALL_HOUSES_OPTION, priority: ALL_PRIORITY_OPTION },
    for_approval: { house: ALL_HOUSES_OPTION, priority: ALL_PRIORITY_OPTION },
    completed: { house: ALL_HOUSES_OPTION, priority: ALL_PRIORITY_OPTION },
};

async function renderManagerTasks() {
    try {
        const response = await fetch("/api/manager/tasks");
        const data = await response.json();

        taskDataCache = {
            pending: data.pending || [],
            for_approval: data.for_approval || [],
            completed: data.completed || [],
        };

        renderPendingTasks(taskDataCache.pending);
        renderApprovalTasks(taskDataCache.for_approval);
        renderCompletedTasks(taskDataCache.completed);
        refreshTaskFilterOptions();
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

        fillSelect(
            document.getElementById("taskWorkerName"),
            data.workers || [],
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
        <option value="">${placeholder}</option>
        ${items.map((item) => `<option value="${item}">${item}</option>`).join("")}
    `;
}

function setupTaskFilters() {
    document.querySelectorAll(".manager-task-filter-select").forEach((select) => {
        select.addEventListener("change", () => {
            const section = select.dataset.taskSection;
            const filterType = select.dataset.filterType;

            if (!section || !filterType) {
                return;
            }

            taskFilters[section] = {
                ...taskFilters[section],
                [filterType]: select.value,
            };

            renderTaskSection(section);
        });
    });
}

function setupTaskRowPaginationControls() {
    const sections = ["pending", "for_approval", "completed"];

    sections.forEach((section) => {
        const prevBtn = document.querySelector(`[data-task-row-prev="${section}"]`);
        const nextBtn = document.querySelector(`[data-task-row-next="${section}"]`);
        const dotsContainer = document.querySelector(`[data-task-row-dots="${section}"]`);

        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                taskRowPages[section] = Math.max(0, taskRowPages[section] - 1);
                renderTaskSection(section);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                taskRowPages[section] += 1;
                renderTaskSection(section);
            });
        }

        if (dotsContainer) {
            dotsContainer.addEventListener("click", (event) => {
                const dot = event.target.closest("[data-task-row-page]");
                if (!dot) return;
                taskRowPages[section] = Number(dot.dataset.taskRowPage || 0);
                renderTaskSection(section);
            });
        }
    });
}

function renderTaskSection(section) {
    if (section === "pending") {
        renderPendingTasks(taskDataCache.pending);
        return;
    }

    if (section === "for_approval") {
        renderApprovalTasks(taskDataCache.for_approval);
        return;
    }

    if (section === "completed") {
        renderCompletedTasks(taskDataCache.completed);
    }
}

function updateTaskRowPagination(section, totalItems) {
    const filteredItems = section === "pending" 
        ? applyTaskFilters(totalItems, taskFilters.pending)
        : section === "for_approval"
        ? applyTaskFilters(totalItems, taskFilters.for_approval)
        : applyTaskFilters(totalItems, taskFilters.completed);

    const totalPages = Math.max(1, Math.ceil(filteredItems.length / TASK_ROWS_PER_PAGE));
    taskRowPages[section] = Math.min(taskRowPages[section], totalPages - 1);

    const prevBtn = document.querySelector(`[data-task-row-prev="${section}"]`);
    const nextBtn = document.querySelector(`[data-task-row-next="${section}"]`);
    const dotsContainer = document.querySelector(`[data-task-row-dots="${section}"]`);

    if (prevBtn) {
        prevBtn.disabled = taskRowPages[section] === 0;
    }

    if (nextBtn) {
        nextBtn.disabled = taskRowPages[section] >= totalPages - 1;
    }

    if (dotsContainer) {
        dotsContainer.innerHTML = Array.from({ length: totalPages }, (_, index) => `
            <button
                type="button"
                class="manager-task-page-dot ${index === taskRowPages[section] ? 'active' : ''}"
                data-task-row-page="${index}"
                aria-label="Go to page ${index + 1}"
            ></button>
        `).join("");
    }
}

function refreshTaskFilterOptions() {
    const sections = ["pending", "for_approval", "completed"];

    sections.forEach((section) => {
        const houseOptions = buildHouseFilterOptions();
        const priorityOptions = buildPriorityFilterOptions();

        updateTaskFilterSelect(`taskHouseFilter-${section}`, houseOptions, taskFilters[section].house);
        updateTaskFilterSelect(`taskPriorityFilter-${section}`, priorityOptions, taskFilters[section].priority);
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

function renderPendingTasks(items) {
    const tbody = document.getElementById("pendingTasksTable");
    if (!tbody) return;

    const filteredItems = applyTaskFilters(items, taskFilters.pending);
    const totalPages = Math.max(1, Math.ceil(filteredItems.length / TASK_ROWS_PER_PAGE));
    taskRowPages.pending = Math.min(taskRowPages.pending, totalPages - 1);

    if (!filteredItems.length) {
        tbody.innerHTML = `<tr><td colspan="8">No tasks match the selected filters.</td></tr>`;
        animateTaskRows();
        updateTaskRowPagination("pending", items);
        return;
    }

    const start = taskRowPages.pending * TASK_ROWS_PER_PAGE;
    const pageItems = filteredItems.slice(start, start + TASK_ROWS_PER_PAGE);

    tbody.innerHTML = pageItems
        .map(
            (item) => `
        <tr>
            <td>${item.name}</td>
            <td>${item.task_assigned}</td>
            <td>${item.house_number}</td>
            <td>${item.pen_number}</td>
            <td>${item.detailed_task}</td>
            <td>${item.priority}</td>
            <td>${item.time_assigned}</td>
            <td>${item.finish_by}</td>
        </tr>
    `,
        )
        .join("");

    updateTaskRowPagination("pending", items);
    animateTaskRows();
}

function renderApprovalTasks(items) {
    const tbody = document.getElementById("approvalTasksTable");
    if (!tbody) return;

    const filteredItems = applyTaskFilters(items, taskFilters.for_approval);
    const totalPages = Math.max(1, Math.ceil(filteredItems.length / TASK_ROWS_PER_PAGE));
    taskRowPages.for_approval = Math.min(taskRowPages.for_approval, totalPages - 1);

    if (!filteredItems.length) {
        tbody.innerHTML = `<tr><td colspan="10">No tasks match the selected filters.</td></tr>`;
        animateTaskRows();
        updateTaskRowPagination("for_approval", items);
        return;
    }

    const start = taskRowPages.for_approval * TASK_ROWS_PER_PAGE;
    const pageItems = filteredItems.slice(start, start + TASK_ROWS_PER_PAGE);

    tbody.innerHTML = pageItems
        .map(
            (item) => `
        <tr>
            <td>${item.name}</td>
            <td>${item.task_assigned}</td>
            <td>${item.house_number}</td>
            <td>${item.pen_number}</td>
            <td>${item.detailed_task}</td>
            <td>
                ${item.photo_url
                    ? `<button
                        type="button"
                        class="manager-task-photo-link"
                        data-photo-name="${item.photo_name}"
                        data-photo-url="${item.photo_url}"
                    >
                        <img src="${item.photo_url}" alt="${item.photo_name}" style="max-width:120px; max-height:80px; object-fit:cover; border-radius:6px;">
                    </button>`
                    : 'No photo'}
            </td>
            <td>${item.priority}</td>
            <td>${item.time_assigned}</td>
            <td>${item.finish_by}</td>
            <td>
                <button
                    type="button"
                    class="manager-task-verify-btn"
                    data-verify-task='${encodeTaskPayload(item)}'
                >
                    Verify
                </button>
            </td>
        </tr>
    `,
        )
        .join("");

    updateTaskRowPagination("for_approval", items);
    bindPhotoButtons();
    bindVerifyButtons();
    animateTaskRows();
}

function renderCompletedTasks(items) {
    const tbody = document.getElementById("completedTasksTable");
    if (!tbody) return;

    const filteredItems = applyTaskFilters(items, taskFilters.completed);
    const totalPages = Math.max(1, Math.ceil(filteredItems.length / TASK_ROWS_PER_PAGE));
    taskRowPages.completed = Math.min(taskRowPages.completed, totalPages - 1);

    if (!filteredItems.length) {
        tbody.innerHTML = `<tr><td colspan="11">No tasks match the selected filters.</td></tr>`;
        animateTaskRows();
        updateTaskRowPagination("completed", items);
        return;
    }

    const start = taskRowPages.completed * TASK_ROWS_PER_PAGE;
    const pageItems = filteredItems.slice(start, start + TASK_ROWS_PER_PAGE);

    tbody.innerHTML = pageItems
        .map(
            (item) => `
        <tr>
            <td>${item.name}</td>
            <td>${item.task_assigned}</td>
            <td>${item.house_number}</td>
            <td>${item.pen_number}</td>
            <td>${item.detailed_task}</td>
            <td>
                ${item.photo_url
                    ? `<button
                        type="button"
                        class="manager-task-photo-link"
                        data-photo-name="${item.photo_name}"
                        data-photo-url="${item.photo_url}"
                    >
                        <img src="${item.photo_url}" alt="${item.photo_name}" style="max-width:120px; max-height:80px; object-fit:cover; border-radius:6px;">
                    </button>`
                    : 'No photo'}
            </td>
            <td>${item.priority}</td>
            <td>${item.notes}</td>
            <td>${item.time_assigned}</td>
            <td>${item.finish_by}</td>
            <td>${item.time_completed}</td>
        </tr>
    `,
        )
        .join("");

    updateTaskRowPagination("completed", items);
    bindPhotoButtons();
    animateTaskRows();
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
                ${rowIndex > 0 ? `<button type="button" class="manager-task-row-remove-btn remove-task-row-btn" data-row-index="${rowIndex}" title="Remove task">×</button>` : ''}
            </div>
            <div class="manager-task-form-grid">
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
                    <label>Time to finish*</label>
                    <input type="time" name="time_assigned_${rowIndex}" class="task-time-input">
                </div>
                <div class="manager-task-form-field">
                    <label>Date to finish*</label>
                    <input type="date" name="date_assigned_${rowIndex}" class="task-date-input">
                </div>
            </div>
            <div class="manager-task-form-field full">
                <label>Detailed Task</label>
                <textarea name="detailed_task_${rowIndex}" class="task-detailed-textarea" rows="3" placeholder="Write a clear and specific task instruction here."></textarea>
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

    // Set up house change event
    if (houseSelect) {
        houseSelect.addEventListener("change", async (event) => {
            const selectedHouseId = event.target.value;
            if (!selectedHouseId) {
                fillSelect(penSelect, [], "number", "label", "Select pen");
                return;
            }

            try {
                const response = await fetch(`/api/manager/tasks/houses/${selectedHouseId}/pens`);
                const data = await response.json();
                fillSelect(penSelect, data.pens || [], "number", "label", "Select pen");
            } catch (error) {
                console.error("Failed to load pens:", error);
            }
        });
    }
}

function setupAddTaskModal() {
    const openButton = document.getElementById("openAddTaskModal");
    const form = document.getElementById("addTaskForm");
    const formError = document.getElementById("addTaskFormError");
    const tasksContainer = document.getElementById("tasksContainer");
    const addTaskRowBtn = document.getElementById("addTaskRowBtn");
    let taskRowCount = 0;

    if (openButton) {
        openButton.addEventListener("click", async () => {
            await loadTaskFormOptions();
            clearAddTaskFormError();
            
            // Initialize with one task row
            taskRowCount = 0;
            tasksContainer.innerHTML = "";
            addNewTaskRow();
            
            openTaskModal("addTaskModal");
        });
    }

    function addNewTaskRow() {
        // Use current task count as the row index to ensure consistent numbering
        const rowIndex = tasksContainer.querySelectorAll(".manager-task-row").length;
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
        row.querySelectorAll("input, select, textarea").forEach((field) => {
            field.addEventListener("input", () => clearTaskFieldError(field));
            field.addEventListener("change", () => clearTaskFieldError(field));
        });

        updateTaskCountBadge();
    }

    function updateTaskCountBadge() {
        const countBadge = document.getElementById("taskCountBadge");
        const taskCount = tasksContainer.querySelectorAll(".manager-task-row").length;
        if (countBadge) {
            countBadge.textContent = taskCount;
        }
    }

    if (addTaskRowBtn) {
        addTaskRowBtn.addEventListener("click", (e) => {
            e.preventDefault();
            addNewTaskRow();
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
            const taskRows = tasksContainer.querySelectorAll(".task-row");
            if (taskRows.length === 0) {
                showAddTaskFormError(formError, "Please add at least one task.");
                return;
            }

            // Validate all tasks
            const tasks = [];
            let hasErrors = false;

            taskRows.forEach((row) => {
                const rowIndex = row.dataset.rowIndex;
                const taskCategory = row.querySelector(`[name="task_category_${rowIndex}"]`).value;
                const priorityLevel = row.querySelector(`[name="priority_level_${rowIndex}"]`).value;
                const houseNumber = row.querySelector(`[name="house_number_${rowIndex}"]`).value;
                const penNumber = row.querySelector(`[name="pen_number_${rowIndex}"]`).value;
                const timeAssigned = row.querySelector(`[name="time_assigned_${rowIndex}"]`).value;
                const dateAssigned = row.querySelector(`[name="date_assigned_${rowIndex}"]`).value;
                const detailedTask = row.querySelector(`[name="detailed_task_${rowIndex}"]`).value;

                // Check required fields
                if (!taskCategory || !priorityLevel || !houseNumber || !penNumber) {
                    hasErrors = true;
                    row.style.backgroundColor = "#ffebee";
                    return;
                }

                row.style.backgroundColor = "#f9f9f9";

                const toLocalDateTimeString = (dateObj) => {
                    const pad = (value) => String(value).padStart(2, "0");
                    return `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())}` +
                        `T${pad(dateObj.getHours())}:${pad(dateObj.getMinutes())}:${pad(dateObj.getSeconds())}`;
                };

                const timeAssignedNow = toLocalDateTimeString(new Date());
                const finishBy = dateAssigned && timeAssigned ? `${dateAssigned}T${timeAssigned}:00` : null;

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

            if (hasErrors) {
                showAddTaskFormError(formError, "Please fill in all required fields (marked with *).");
                return;
            }

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                
                // Submit each task
                for (const task of tasks) {
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

                await renderManagerTasks();
                closeTaskModal("addTaskModal");
                form.reset();
                tasksContainer.innerHTML = "";
                setupTaskSelectPlaceholderState();
            } catch (error) {
                console.error("Failed to save tasks:", error);
                showAddTaskFormError(formError, "Unable to save tasks. Please try again.");
            }
        };

        form.addEventListener("submit", handleFormSubmit);
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
            const note = item.disabled ? ` (${item.disabledReason || 'pending task'})` : '';
            const style = item.disabled ? 'style="color:#999;"' : '';
            return `<option value="${value}" ${disabled} ${style}>${label}${note}</option>`;
        }).join("")}
    `;
}
