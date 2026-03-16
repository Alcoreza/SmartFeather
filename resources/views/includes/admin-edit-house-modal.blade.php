<div class="modal-overlay" id="editHouseModal">
    <div class="modal-card edit-data-modal">
        <div class="modal-header">
            <h2>Edit Data</h2>
            <button type="button" class="modal-close" id="closeEditHouseModal">&times;</button>
        </div>

        <form id="editHouseForm">
            <div class="modal-row">
                <div class="modal-group">
                    <label for="editBatchId">Batch ID</label>
                    <input type="text" id="editBatchId">
                </div>

                <div class="modal-group">
                    <label for="editStartDate">Start Date</label>
                    <input type="text" id="editStartDate">
                </div>
            </div>

            <div class="modal-row">
                <div class="modal-group">
                    <label for="editHouseName">House</label>
                    <input type="text" id="editHouseName">
                </div>

                <div class="modal-group">
                    <label for="editPen">Pen</label>
                    <input type="text" id="editPen">
                </div>
            </div>

            <div class="modal-row">
                <div class="modal-group">
                    <label for="editCapacity">Capacity</label>
                    <input type="number" id="editCapacity">
                </div>

                <div class="modal-group">
                    <label for="editPopulation">Population</label>
                    <input type="number" id="editPopulation">
                </div>
            </div>

            <div class="modal-row">
                <div class="modal-group">
                    <label for="editEggsHatched">Eggs Hatched</label>
                    <input type="number" id="editEggsHatched">
                </div>

                <div class="modal-group">
                    <label for="editMortality">Mortality</label>
                    <input type="number" id="editMortality">
                </div>
            </div>

            <div class="modal-group modal-group-full">
                <label for="editCondition">Condition</label>
                <input type="text" id="editCondition">
            </div>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="cancelEditHouseModal">Cancel</button>
                <button type="submit" class="save-btn">Save</button>
            </div>
        </form>
    </div>
</div>