<!-- ADD/EDIT MODAL -->
<div class="admin-worker-modal-backdrop" id="workerModal" style="display:none;">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2 id="workerModalTitle">Add Employee</h2>
            <div class="admin-worker-header-line"></div>
        </div>

        <form id="workerForm" class="admin-worker-modal-body">
            <input type="hidden" id="EmployeeId">
            <div class="admin-worker-form-error" id="workerFormError" role="alert" aria-live="polite"></div>

            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>First Name*</label>
                    <input type="text" id="FirstName">
                </div>
                <div class="admin-worker-field">
                    <label>Middle Name</label>
                    <input type="text" id="MiddleName">
                </div>
                <div class="admin-worker-field">
                    <label>Last Name*</label>
                    <input type="text" id="LastName">
                </div>
                <div class="admin-worker-field">
                    <label>Suffix</label>
                    <input type="text" id="Suffix">
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Username</label>
                <input type="text" id="Username">
                <small id="usernameHint" style="display:none; color:#4b5563; font-size:0.85rem; margin-top:6px;">Auto-generated from first name, last name, and suffix.</small>
            </div>

            <div class="admin-worker-field">
                <label>Role*</label>
                <select id="Role">
                    <option value="">Select Role</option>
                    <option value="Manager">Manager</option>
                    <option value="Admin">Admin</option>
                    <option value="Flockman">Flockman</option>
                </select>
            </div>

            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>Phone Number*</label>
                    <input type="text" id="PhoneNumber" inputmode="numeric" maxlength="11" autocomplete="tel" placeholder="09XXXXXXXXX">
                </div>
                <div class="admin-worker-field">
                    <label>Birthday*</label>
                    <input type="date" id="Birthday" max="{{ now()->subDay()->toDateString() }}">
                </div>
                <div class="admin-worker-field">
                    <label>Gender*</label>
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

            <div class="admin-worker-form-grid" id="addPasswordFields">
                <div class="admin-worker-field">
                    <label>Password*</label>
                    <input type="password" id="AddPassword" autocomplete="new-password">
                    <small style="display:block; color:#4b5563; font-size:0.85rem; margin-top:6px;">Minimum 8 characters.</small>
                </div>
                <div class="admin-worker-field">
                    <label>Confirm Password*</label>
                    <input type="password" id="AddConfirmPassword" autocomplete="new-password">
                    <small id="addConfirmPasswordMessage" class="admin-worker-field-error" aria-live="polite"></small>
                </div>
            </div>

            <div class="admin-worker-modal-actions">
                <button type="button" id="editPasswordBtn" class="admin-worker-btn password">Edit Password</button>
                <div class="admin-worker-modal-action-group">
                    <button type="button" class="admin-worker-btn cancel" data-close-admin-modal="workerModal">Cancel</button>
                    <button type="submit" class="admin-worker-btn save">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- SAVE CONFIRMATION MODAL -->
<div class="admin-worker-modal-backdrop confirm-modal-top" id="saveWorkerConfirmModal" style="display:none;">
    <div class="admin-worker-modal-card admin-delete-worker-card">
        <div class="admin-worker-modal-header">
            <h2>Save Employee</h2>
            <div class="admin-worker-header-line"></div>
        </div>
        <div class="admin-worker-modal-body">
            <p class="admin-delete-worker-text" id="saveWorkerConfirmMessage">Save this employee?</p>
            <div class="admin-worker-modal-actions">
                <button type="button" class="admin-worker-btn cancel" data-close-admin-modal="saveWorkerConfirmModal">Cancel</button>
                <button type="button" class="admin-worker-btn save" id="confirmSaveWorkerBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- PASSWORD MODAL -->
<div class="admin-worker-modal-backdrop" id="passwordModal" style="display:none;">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2 id="passwordModalTitle">Edit Password</h2>
            <div class="admin-worker-header-line"></div>
        </div>

        <form id="passwordForm" class="admin-worker-modal-body">
            <div class="admin-worker-field" id="passwordModalOldPasswordField" style="display:none;">
                <label>Current Password</label>
                <input type="password" id="passwordModalOldPassword" autocomplete="current-password">
                <small id="oldPasswordMessage" class="admin-worker-field-error" aria-live="polite"></small>
            </div>

            <div class="admin-worker-field">
                <label>New Password</label>
                <input type="password" id="passwordModalPassword" autocomplete="new-password">
            </div>

            <div class="admin-worker-field">
                <label>Confirm Password</label>
                <input type="password" id="passwordModalConfirm" autocomplete="new-password">
                <small id="confirmPasswordMessage" class="admin-worker-field-error" aria-live="polite"></small>
            </div>

            <div class="admin-worker-modal-actions">
                <button type="button" class="admin-worker-btn cancel" data-close-admin-modal="passwordModal">Cancel</button>
                <button type="submit" class="admin-worker-btn save">Save Password</button>
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
                <label>Username:</label>
                <p id="view_username"></p>
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

<!-- DEACTIVATE MODAL -->
<div class="admin-worker-modal-backdrop" id="deleteWorkerModal" style="display:none;">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2>Deactivate Employee</h2>
            <div class="admin-worker-header-line"></div>
        </div>
        <div class="admin-worker-modal-body">
            <p>Deactivate <span id="deleteWorkerName"></span>? This employee will be marked inactive and hidden from active employee lists.</p>
            <div class="admin-worker-modal-actions">
                <button type="button" class="admin-worker-btn cancel" data-close-admin-modal="deleteWorkerModal">Cancel</button>
                <button type="button" class="admin-worker-btn save" id="confirmDeleteWorkerBtn">Deactivate</button>
            </div>
        </div>
    </div>
</div>
