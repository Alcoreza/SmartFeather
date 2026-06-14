<div class="manager-task-modal-backdrop confirm-modal-top" id="deleteTaskModal">
    <div class="manager-task-confirm-modal">
        <div class="manager-task-modal-header center">
            <h2>Delete Task</h2>
            <div class="manager-task-header-line"></div>
        </div>

        <div class="manager-task-confirm-body">
            <p id="deleteTaskText">Are you sure you want to delete this task? This action cannot be undone.</p>

            <div class="manager-task-confirm-actions">
                <button type="button" class="manager-task-btn cancel"
                    data-close-task-modal="deleteTaskModal">Cancel</button>
                <button type="button" class="manager-task-btn confirm" id="confirmTaskDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="manager-task-modal-backdrop" id="taskPhotoModal">
    <div class="manager-task-photo-modal">
        <div class="manager-task-modal-header">
            <h2>Proof Photo</h2>
            <button type="button" class="manager-task-close-btn" data-close-task-modal="taskPhotoModal">×</button>
        </div>

        <div class="manager-task-photo-body">
            <img id="taskPhotoPreview" src="" alt="Task proof photo">
        </div>
    </div>
</div>

<div class="manager-task-modal-backdrop confirm-modal-top" id="taskVerifyModal">
    <div class="manager-task-confirm-modal">
        <div class="manager-task-modal-header center">
            <h2>Mark as Complete</h2>
            <div class="manager-task-header-line"></div>
        </div>

        <div class="manager-task-confirm-body">
            <p id="taskVerifyText">Do you want to mark this task as complete?</p>

            <div class="manager-task-confirm-actions">
                <button type="button" class="manager-task-btn cancel"
                    data-close-task-modal="taskVerifyModal">Cancel</button>
                <button type="button" class="manager-task-btn confirm" id="confirmTaskVerify">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="manager-task-modal-backdrop confirm-modal-top" id="confirmAddTaskModal">
    <div class="manager-task-confirm-modal">
        <div class="manager-task-modal-header center">
            <h2>Assign Task</h2>
            <div class="manager-task-header-line"></div>
        </div>

        <div class="manager-task-confirm-body">
            <p id="confirmAddTaskText">Are you sure you want to assign this task?</p>

            <div class="manager-task-confirm-actions">
                <button type="button" class="manager-task-btn cancel"
                    data-close-task-modal="confirmAddTaskModal">Cancel</button>
                <button type="button" class="manager-task-btn confirm" id="confirmAddTask">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="manager-task-modal-backdrop confirm-modal-top" id="confirmEditTaskModal">
    <div class="manager-task-confirm-modal">
        <div class="manager-task-modal-header center">
            <h2>Save Task Changes</h2>
            <div class="manager-task-header-line"></div>
        </div>

        <div class="manager-task-confirm-body">
            <p id="confirmEditTaskText">Save changes to this task?</p>

            <div class="manager-task-confirm-actions">
                <button type="button" class="manager-task-btn cancel"
                    data-close-task-modal="confirmEditTaskModal">Cancel</button>
                <button type="button" class="manager-task-btn confirm" id="confirmEditTask">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="manager-task-modal-backdrop" id="addTaskModal">
    <div class="manager-task-form-modal">
        <div class="manager-task-modal-header center">
            <h2>Assign Task</h2>
            <div class="manager-task-header-line"></div>
        </div>

        <form class="manager-task-form-body" id="addTaskForm">
            <div class="manager-task-form-error" id="addTaskFormError" role="alert" aria-live="polite"></div>

            <!-- Worker Selection Section -->
            <div class="manager-task-form-section">
                <h3 class="manager-task-form-section-title">Select Flockman</h3>
                <div class="manager-task-form-field full">
                    <label for="taskWorkerName">Assign Flockman*</label>
                    <select id="taskWorkerName" name="worker_name" class="task-select-placeholder"></select>
                </div>
            </div>

            <!-- Tasks Section -->
            <div class="manager-task-form-section">
                <div class="manager-task-tasks-header">
                    <h3 class="manager-task-form-section-title">Tasks</h3>
                    <span class="manager-task-count-badge" id="taskCountBadge">0</span>
                </div>
                
                <div id="tasksContainer" class="manager-task-rows-container">
                    <!-- Task rows will be dynamically added here -->
                </div>

                <button type="button" class="manager-task-add-btn" id="addTaskRowBtn">
                    <span class="manager-task-add-btn-icon">+</span>
                    Add Another Task
                </button>
            </div>

            <!-- Actions Section -->
            <div class="manager-task-form-actions">
                <button type="button" class="manager-task-btn cancel"
                    data-close-task-modal="addTaskModal">Cancel</button>
                <button type="submit" class="manager-task-btn confirm">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="manager-task-modal-backdrop" id="editTaskModal">
    <div class="manager-task-form-modal">
        <div class="manager-task-modal-header center">
            <h2>Edit Assigned Task</h2>
            <div class="manager-task-header-line"></div>
        </div>

        <form class="manager-task-form-body" id="editTaskForm">
            <div class="manager-task-form-error" id="editTaskFormError" role="alert" aria-live="polite"></div>

            <!-- Worker Info Section (Read-only) -->
            <div class="manager-task-form-section">
                <h3 class="manager-task-form-section-title">Task Information</h3>
                <div class="manager-task-form-field full">
                    <label>Assigned to</label>
                    <input type="text" id="editTaskWorkerName" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                </div>
            </div>

            <div class="manager-task-form-grid">
                <div class="manager-task-form-field">
                    <label>Task*</label>
                    <select name="task_category" class="task-select-placeholder task-category-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Priority Level*</label>
                    <select name="priority_level" class="task-select-placeholder task-priority-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>House Number*</label>
                    <select name="house_number" class="task-select-placeholder task-house-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Pen Number*</label>
                    <select name="pen_number" class="task-select-placeholder task-pen-select"></select>
                </div>
                <div class="manager-task-form-field">
                    <label>Time to finish*</label>
                    <input type="time" name="time_assigned" class="task-time-input">
                </div>
                <div class="manager-task-form-field">
                    <label>Date to finish*</label>
                    <input type="date" name="date_assigned" class="task-date-input">
                </div>
            </div>
            <div class="manager-task-form-field full">
                <label>Detailed Task</label>
                <textarea name="detailed_task" class="task-detailed-textarea" rows="3" placeholder="Write a clear and specific task instruction here."></textarea>
            </div>

            <!-- Actions Section -->
            <div class="manager-task-form-actions">
                <button type="button" class="manager-task-btn cancel"
                    data-close-task-modal="editTaskModal">Cancel</button>
                <button type="submit" class="manager-task-btn confirm">Save Changes</button>
            </div>
        </form>
    </div>
</div>
