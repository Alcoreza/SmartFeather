<div class="admin-worker-modal-backdrop" id="adminViewWorkerModal">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2>View Data</h2>
            <div class="admin-worker-header-line"></div>
        </div>

        <div class="admin-worker-modal-body">
            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>First Name</label>
                    <input type="text" id="adminViewFirstName" readonly>
                </div>

                <div class="admin-worker-field">
                    <label>Middle Name</label>
                    <input type="text" id="adminViewMiddleName" readonly>
                </div>

                <div class="admin-worker-field">
                    <label>Last Name</label>
                    <input type="text" id="adminViewLastName" readonly>
                </div>

                <div class="admin-worker-field">
                    <label>Suffix</label>
                    <input type="text" id="adminViewSuffix" readonly>
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Role</label>
                <input type="text" id="adminViewRole" readonly>
            </div>

            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>Phone Number</label>
                    <input type="text" id="adminViewPhone" readonly>
                </div>

                <div class="admin-worker-field">
                    <label>ID</label>
                    <input type="text" id="adminViewId" readonly>
                </div>

                <div class="admin-worker-field">
                    <label>Birthday</label>
                    <input type="text" id="adminViewBirthday" readonly>
                </div>

                <div class="admin-worker-field">
                    <label>Gender</label>
                    <input type="text" id="adminViewGender" readonly>
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Address</label>
                <input type="text" id="adminViewAddress" readonly>
            </div>

            <div class="admin-worker-modal-actions single">
                <button type="button" class="admin-worker-btn cancel"
                    data-close-admin-modal="adminViewWorkerModal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="admin-worker-modal-backdrop" id="adminEditWorkerModal">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2>Edit Data</h2>
            <div class="admin-worker-header-line"></div>
        </div>

        <form class="admin-worker-modal-body">
            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>First Name</label>
                    <input type="text" id="adminEditFirstName">
                </div>

                <div class="admin-worker-field">
                    <label>Middle Name</label>
                    <input type="text" id="adminEditMiddleName">
                </div>

                <div class="admin-worker-field">
                    <label>Last Name</label>
                    <input type="text" id="adminEditLastName">
                </div>

                <div class="admin-worker-field">
                    <label>Suffix</label>
                    <input type="text" id="adminEditSuffix">
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Role</label>
                <input type="text" id="adminEditRole">
            </div>

            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>Phone Number</label>
                    <input type="text" id="adminEditPhone">
                </div>

                <div class="admin-worker-field">
                    <label>ID</label>
                    <input type="text" id="adminEditId">
                </div>

                <div class="admin-worker-field">
                    <label>Birthday</label>
                    <input type="text" id="adminEditBirthday">
                </div>

                <div class="admin-worker-field">
                    <label>Gender</label>
                    <input type="text" id="adminEditGender">
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Address</label>
                <input type="text" id="adminEditAddress">
            </div>

            <div class="admin-worker-field">
                <label>New Password</label>
                <input type="password" id="adminEditPassword">
            </div>

            <div class="admin-worker-modal-actions">
                <button type="button" class="admin-worker-btn cancel"
                    data-close-admin-modal="adminEditWorkerModal">Cancel</button>
                <button type="button" class="admin-worker-btn save">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="admin-worker-modal-backdrop" id="adminAddWorkerModal">
    <div class="admin-worker-modal-card">
        <div class="admin-worker-modal-header">
            <h2>Add Employee</h2>
            <div class="admin-worker-header-line"></div>
        </div>

        <form class="admin-worker-modal-body">
            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>First Name</label>
                    <input type="text" id="adminAddFirstName">
                </div>

                <div class="admin-worker-field">
                    <label>Middle Name</label>
                    <input type="text" id="adminAddMiddleName">
                </div>

                <div class="admin-worker-field">
                    <label>Last Name</label>
                    <input type="text" id="adminAddLastName">
                </div>

                <div class="admin-worker-field">
                    <label>Suffix</label>
                    <input type="text" id="adminAddSuffix">
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Role</label>
                <input type="text" id="adminAddRole">
            </div>

            <div class="admin-worker-form-grid">
                <div class="admin-worker-field">
                    <label>Phone Number</label>
                    <input type="text" id="adminAddPhone">
                </div>

                <div class="admin-worker-field">
                    <label>ID</label>
                    <input type="text" id="adminAddId">
                </div>

                <div class="admin-worker-field">
                    <label>Birthday</label>
                    <input type="text" id="adminAddBirthday">
                </div>

                <div class="admin-worker-field">
                    <label>Gender</label>
                    <input type="text" id="adminAddGender">
                </div>
            </div>

            <div class="admin-worker-field">
                <label>Address</label>
                <input type="text" id="adminAddAddress">
            </div>

            <div class="admin-worker-field">
                <label>Password</label>
                <input type="password" id="adminAddPassword">
            </div>

            <div class="admin-worker-modal-actions">
                <button type="button" class="admin-worker-btn cancel"
                    data-close-admin-modal="adminAddWorkerModal">Cancel</button>
                <button type="button" class="admin-worker-btn save">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="admin-worker-modal-backdrop" id="adminDeleteWorkerModal">
    <div class="admin-worker-modal-card admin-delete-worker-card">
        <div class="admin-worker-modal-header">
            <h2>Delete Employee</h2>
            <div class="admin-worker-header-line"></div>
        </div>

        <div class="admin-worker-modal-body">
            <p class="admin-delete-worker-text" id="adminDeleteWorkerText">
                Do you want to delete this employee?
            </p>

            <div class="admin-worker-modal-actions">
                <button type="button" class="admin-worker-btn cancel"
                    data-close-admin-modal="adminDeleteWorkerModal">Cancel</button>
                <button type="button" class="admin-worker-btn delete" id="confirmAdminDeleteWorker">Delete</button>
            </div>
        </div>
    </div>
</div>