<div class="admin-profile-modal-backdrop" id="adminProfileModal">
    <div class="admin-profile-modal-card">
        <div class="admin-profile-modal-header">
            <h2>Profile</h2>
            <div class="admin-profile-header-line"></div>
        </div>

        <form class="admin-profile-form">
            <div class="admin-profile-grid">
                <div class="admin-profile-field">
                    <label>First Name</label>
                    <input type="text" id="profileFirstName" readonly>
                </div>

                <div class="admin-profile-field">
                    <label>Middle Name</label>
                    <input type="text" id="profileMiddleName" readonly>
                </div>

                <div class="admin-profile-field">
                    <label>Last Name</label>
                    <input type="text" id="profileLastName" readonly>
                </div>

                <div class="admin-profile-field">
                    <label>Suffix</label>
                    <input type="text" id="profileSuffix" readonly>
                </div>
            </div>

            <div class="admin-profile-field">
                <label>Role</label>
                <input type="text" id="profileRole" readonly>
            </div>

            <div class="admin-profile-grid">
                <div class="admin-profile-field">
                    <label>Phone Number</label>
                    <input type="text" id="profilePhone" readonly>
                </div>

                <div class="admin-profile-field">
                    <label>ID</label>
                    <input type="text" id="profileId" readonly>
                </div>

                <div class="admin-profile-field">
                    <label>Birthday</label>
                    <input type="text" id="profileBirthday" readonly>
                </div>

                <div class="admin-profile-field">
                    <label>Gender</label>
                    <input type="text" id="profileGender" readonly>
                </div>
            </div>

            <div class="admin-profile-field">
                <label>Address</label>
                <input type="text" id="profileAddress" readonly>
            </div>

            <div class="admin-profile-actions">
                <button type="button" class="admin-profile-btn-action cancel" id="closeAdminProfileModal">Cancel</button>
                <button type="button" class="admin-profile-btn-action logout" data-logout-trigger data-logout-url="{{ route('logout') }}">Logout</button>
            </div>
        </form>
    </div>
</div>
