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
            <h2>Add Task</h2>
            <div class="manager-task-header-line"></div>
        </div>

        <form class="manager-task-form-body" id="addTaskForm">
            <div class="manager-task-form-field full">
                <label for="taskWorkerName">Name</label>
                <select id="taskWorkerName" name="worker_name" class="task-select-placeholder"></select>
            </div>

            <div class="manager-task-form-grid">
                <div class="manager-task-form-field">
                    <label for="taskCategory">Assign Task</label>
                    <select id="taskCategory" name="task_category" class="task-select-placeholder"></select>
                </div>

                <div class="manager-task-form-field">
                    <label for="taskPriority">Priority Level</label>
                    <select id="taskPriority" name="priority_level" class="task-select-placeholder"></select>
                </div>

                <div class="manager-task-form-field">
                    <label for="taskHouseNumber">House Number</label>
                    <select id="taskHouseNumber" name="house_number" class="task-select-placeholder"></select>
                </div>

                <div class="manager-task-form-field">
                    <label for="taskPenNumber">Pen Number</label>
                    <select id="taskPenNumber" name="pen_number" class="task-select-placeholder"></select>
                </div>


                <div class="manager-task-form-field">
                    <label for="taskTimeAssigned">Time</label>
                    <input type="time" id="taskTimeAssigned" name="time_assigned">
                </div>

                <div class="manager-task-form-field">
                    <label for="taskDateAssigned">Date</label>
                    <input type="date" id="taskDateAssigned" name="date_assigned">
                </div>
            </div>

            <div class="manager-task-form-field full">
                <label for="taskDetailedDescription">Detailed Task</label>
                <textarea id="taskDetailedDescription" name="detailed_task" rows="5"
                    placeholder="Write a clear and specific task instruction here. Include what needs to be checked, where it should be done, and any expected outcome or notes the worker should follow."></textarea>
            </div>

            <div class="manager-task-confirm-actions">
                <button type="button" class="manager-task-btn cancel"
                    data-close-task-modal="addTaskModal">Cancel</button>
                <button type="submit" class="manager-task-btn confirm">Save</button>
            </div>
        </form>
    </div>
</div>