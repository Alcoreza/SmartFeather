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

<div class="manager-task-modal-backdrop" id="taskVerifyModal">
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
