<div class="modal-overlay" id="editHouseModal">
    <div class="modal-card">
        <div class="modal-header">
            <h2>Edit House</h2>
            <button type="button" class="modal-close" id="closeEditHouseModal">&times;</button>
        </div>

        <form id="editHouseForm">
            <div class="modal-group">
                <label for="editHouseName">House Name</label>
                <input type="text" id="editHouseName" placeholder="Enter house name" required>
            </div>

            <div class="modal-group">
                <label for="editHouseStatus">Status</label>
                <select id="editHouseStatus">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="modal-group">
                <label for="editHouseBatch">Batch</label>
                <input type="text" id="editHouseBatch" placeholder="Enter batch">
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="cancelEditHouseModal">Cancel</button>
                <button type="submit" class="save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>