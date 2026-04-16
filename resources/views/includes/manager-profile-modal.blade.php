<div class="profile-modal-backdrop" id="profileModal">
    <div class="profile-modal-card">
        <div class="profile-modal-header">
            <h2>Profile</h2>
            <div class="profile-header-line"></div>
        </div>

        <form class="profile-form">
            <div class="profile-grid two-col">
                <div class="profile-field">
                    <label>First Name</label>
                    <input type="text" id="profileFirstName" readonly>
                </div>

                <div class="profile-field">
                    <label>Middle Name</label>
                    <input type="text" id="profileMiddleName" readonly>
                </div>

                <div class="profile-field">
                    <label>Last Name</label>
                    <input type="text" id="profileLastName" readonly>
                </div>

                <div class="profile-field">
                    <label>Suffix</label>
                    <input type="text" id="profileSuffix" readonly>
                </div>
            </div>

            <div class="profile-field">
                <label>Role</label>
                <input type="text" id="profileRole" readonly>
            </div>

            <div class="profile-grid two-col">
                <div class="profile-field">
                    <label>Phone Number</label>
                    <input type="text" id="profilePhone" readonly>
                </div>

                <div class="profile-field">
                    <label>ID</label>
                    <input type="text" id="profileId" readonly>
                </div>

                <div class="profile-field">
                    <label>Birthday</label>
                    <input type="text" id="profileBirthday" readonly>
                </div>

                <div class="profile-field">
                    <label>Gender</label>
                    <input type="text" id="profileGender" readonly>
                </div>
            </div>

            <div class="profile-field">
                <label>Address</label>
                <input type="text" id="profileAddress" readonly>
            </div>

            <div class="profile-actions">
                <button type="button" class="profile-btn cancel-btn" id="closeProfileModal">Cancel</button>
                <button type="button" class="profile-btn logout-btn" onclick="window.location.href='{{ route('logout') }}'">Logout</button>
            </div>
        </form>
    </div>
</div>