<div class="modal-overlay" id="addHouseModal">
    <div class="modal-card add-house-modal">
        <div class="modal-header">
            <h2>Add House</h2>
            <button type="button" class="modal-close" id="closeAddHouseModal">&times;</button>
        </div>

        <form id="addHouseForm">
            <div class="house-form-error" id="addHouseFormError" role="alert" aria-live="polite"></div>

            <div class="modal-row">
                <div class="modal-group">
                    <label for="houseName">House Number*</label>
                    <input type="text" id="houseName">
                </div>

                <div class="modal-group">
                    <label for="housePenCount">Number of Pens*</label>
                    <input type="number" id="housePenCount" min="0" value="0">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="cancelAddHouseModal">Cancel</button>
                <button type="submit" class="save-btn">Save</button>
            </div>
        </form>
    </div>
</div>
