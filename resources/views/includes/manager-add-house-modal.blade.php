<div class="modal-overlay" id="addHouseModal">
    <div class="modal-card">
        <div class="modal-header">
            <h2>Add House</h2>
            <button type="button" class="modal-close" id="closeAddHouseModal">&times;</button>
        </div>

        <form id="addHouseForm">
            <div class="modal-group">
                <label for="houseName">House Name</label>
                <input type="text" id="houseName" placeholder="Enter house name" required>
            </div>

            <div class="modal-group">
                <label for="houseStatusInput">Status</label>
                <select id="houseStatusInput">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="modal-group">
                <label for="houseBatchInput">Batch</label>
                <input type="text" id="houseBatchInput" placeholder="Enter batch">
            </div>

            <div class="modal-group">
                <label for="housePenCount">Number of Pens</label>
                <input type="number" id="housePenCount" min="1" value="1">
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="cancelAddHouseModal">Cancel</button>
                <button type="submit" class="save-btn">Save House</button>
            </div>
        </form>
    </div>
</div>