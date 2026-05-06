<div class="modal-overlay" id="endBatchModal">
    <div class="modal-card">
        <div class="modal-header">
            <h2>End Batch</h2>
            <button type="button" class="modal-close" id="closeEndBatchModal">&times;</button>
        </div>

        <form id="endBatchForm">
            <div class="modal-group">
                <label for="endAction">Choose Action:</label>
                <select id="endAction" required>
                    <option value="">Select an action...</option>
                    <option value="house">End Whole House (Delete house and all pens)</option>
                    <option value="pen">End Specific Pen</option>
                </select>
            </div>

            <div class="modal-group" id="penSelectGroup" style="display: none;">
                <label for="endPenSelect">Select Pen:</label>
                <select id="endPenSelect">
                    <option value="">Select a pen...</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="cancelEndBatchModal">Cancel</button>
                <button type="submit" class="delete-btn">End Batch</button>
            </div>
        </form>
    </div>
</div>