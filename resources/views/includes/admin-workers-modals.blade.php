<!-- ADD/EDIT MODAL -->
<div class="admin-worker-modal-backdrop" id="workerModal" style="display:none;">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2 id="workerModalTitle">Add Employee</h2>
            <div class="admin-worker-header-line"></div>
        </div>

        <form id="workerForm" class="admin-worker-modal-body">
            <input type="hidden" id="EmployeeId">

            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>First Name</label>
                    <input type="text" id="FirstName" required>
                </div>
                <div class="admin-worker-field">
                    <label>Middle Name</label>
                    <input type="text" id="MiddleName">
                </div>
                <div class="admin-worker-field">
                    <label>Last Name</label>
                    <input type="text" id="LastName" required>
                </div>
                <div class="admin-worker-field">
                    <label>Suffix</label>
                    <input type="text" id="Suffix">
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Role</label>
                <select id="Role" required>
                    <option value="Manager">Manager</option>
                    <option value="Admin">Admin</option>
                    <option value="Flockman">Flockman</option>
                </select>
            </div>

            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>Phone Number</label>
                    <input type="text" id="PhoneNumber">
                </div>
                <div class="admin-worker-field">
                    <label>Birthday</label>
                    <input type="date" id="Birthday">
                </div>
                <div class="admin-worker-field">
                    <label>Gender</label>
                    <select id="Gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Address</label>
                <input type="text" id="Address">
            </div>

            <div class="admin-worker-field">
                <label>Password</label>
                <input type="password" id="Password">
            </div>

            <div class="admin-worker-modal-actions">
                <button type="button" class="admin-worker-btn cancel" data-close-admin-modal="workerModal">Cancel</button>
                <button type="submit" class="admin-worker-btn save">Save</button>
            </div>
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

<!-- DELETE MODAL -->
<div class="admin-worker-modal-backdrop" id="deleteWorkerModal" style="display:none;">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2>Delete Employee</h2>
            <div class="admin-worker-header-line"></div>
        </div>
        <div class="admin-worker-modal-body">
            <p>Are you sure you want to delete <span id="deleteWorkerName"></span>?</p>
            <div class="admin-worker-modal-actions">
                <button type="button" class="admin-worker-btn cancel" data-close-admin-modal="deleteWorkerModal">Cancel</button>
                <button type="button" class="admin-worker-btn save" id="confirmDeleteWorkerBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
