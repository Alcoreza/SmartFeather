<div class="manager-management-modal-backdrop" id="createTaskTypeModal">
    <div class="manager-management-modal-card">
        <div class="manager-management-modal-header">
            <div>
                <p class="manager-management-modal-eyebrow">Management</p>
                <h2>Create Task</h2>
            </div>

            <button type="button" class="manager-management-modal-close" id="closeCreateTaskTypeModal" aria-label="Close create task modal">×</button>
        </div>

        <form id="createTaskTypeForm" class="manager-management-modal-form">
            <label for="newTaskType">New task type</label>
            <input
                type="text"
                id="newTaskType"
                name="task_type"
                placeholder="Enter new task type"
                autocomplete="off"
            >

            <p class="manager-management-modal-help">This adds a new task type to the task list.</p>
            <p class="manager-management-modal-message" id="createTaskTypeMessage" aria-live="polite"></p>

            <div class="manager-management-modal-actions">
                <button type="button" class="manager-management-modal-secondary-btn" id="cancelCreateTaskTypeModal">Cancel</button>
                <button type="submit" class="manager-management-modal-primary-btn">Save</button>
            </div>
        </form>
    </div>
</div>
