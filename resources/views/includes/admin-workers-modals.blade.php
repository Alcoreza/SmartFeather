<!-- Add/Edit Employee Modal -->
<div class="modal" id="workerModal" style="display:none;">
    <div class="modal-content">
        <h2 id="workerModalTitle">Add Employee</h2>
        <form id="workerForm">
            <input type="hidden" id="EmployeeId" name="EmployeeId">
            <label>First Name</label>
            <input type="text" name="FirstName" id="FirstName" required>
            
            <label>Middle Name</label>
            <input type="text" name="MiddleName" id="MiddleName">

            <label>Last Name</label>
            <input type="text" name="LastName" id="LastName" required>

            <label>Suffix</label>
            <input type="text" name="Suffix" id="Suffix">

            <label>Role</label>
            <select name="Role" id="Role" required>
                <option value="Manager">Manager</option>
                <option value="Admin">Admin</option>
                <option value="Flockman">Flockman</option>
            </select>

            <label>Phone Number</label>
            <input type="text" name="PhoneNumber" id="PhoneNumber">

            <label>Birthday</label>
            <input type="date" name="Birthday" id="Birthday">

            <label>Gender</label>
            <select name="Gender" id="Gender">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>

            <label>Address</label>
            <input type="text" name="Address" id="Address">

            <label>Password</label>
            <input type="password" name="Password" id="Password">

            <button type="submit" id="saveWorkerBtn">Save</button>
            <button type="button" id="closeWorkerModal">Cancel</button>
        </form>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="admin-worker-modal-backdrop" id="viewModal" style="display: none;">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2>Employee Details</h2>
            <div class="admin-worker-header-line"></div>
        </div>

        <div class="admin-worker-modal-body" id="viewEmployeeBody">
            <!-- Filled dynamically via JS -->
            <div class="admin-worker-field">
                <label>Name:</label>
                <p id="view_name"></p>
            </div>
            <div class="admin-worker-field">
                <label>Role:</label>
                <p id="view_role"></p>
            </div>
            <div class="admin-worker-field">
                <label>Phone Number:</label>
                <p id="view_phone_number"></p>
            </div>
            <div class="admin-worker-field">
                <label>Birthday:</label>
                <p id="view_birthday"></p>
            </div>
            <div class="admin-worker-field">
                <label>Gender:</label>
                <p id="view_gender"></p>
            </div>
            <div class="admin-worker-field">
                <label>Address:</label>
                <p id="view_address"></p>
            </div>
        </div>

        <div class="admin-worker-modal-actions">
            <button type="button" class="admin-worker-btn cancel" data-close-admin-modal="viewModal">Close</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteWorkerModal" style="display:none;">
    <div class="modal-content">
        <h2>Delete Employee</h2>
        <p>Are you sure you want to delete <span id="deleteWorkerName"></span>?</p>
        <button id="confirmDeleteWorkerBtn">Delete</button>
        <button id="cancelDeleteWorkerBtn">Cancel</button>
    </div>
</div>
