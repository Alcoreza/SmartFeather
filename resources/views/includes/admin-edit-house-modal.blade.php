<div class="modal-overlay" id="editHouseModal">
    <div class="modal-card edit-data-modal">
        <div class="modal-header">
            <h2>Edit House & Pen Details</h2>
            <button type="button" class="modal-close" id="closeEditHouseModal">&times;</button>
        </div>

        <form id="editHouseForm">
            <div class="modal-row">
                <div class="modal-group">
                    <label for="editBatchId">Batch ID</label>
                    <input type="text" id="editBatchId" readonly>
                </div>

                <div class="modal-group">
                    <label for="editStartDate">Batch Start Date</label>
                    <input type="text" id="editStartDate">
                </div>
            </div>

            <div class="modal-row">
                <div class="modal-group">
                    <label for="editHouseName">House Name</label>
                    <input type="text" id="editHouseName">
                </div>

                <div class="modal-group">
                    <label for="editPen">Selected Pen</label>
                    <select id="editPen"></select>
                </div>
            </div>

            <div class="modal-row">
                <div class="modal-group">
                    <label for="editCapacity">Pen Capacity</label>
                    <input type="number" id="editCapacity">
                </div>

                <div class="modal-group">
                    <label for="editPopulation">Current Population</label>
                    <input type="number" id="editPopulation">
                </div>
            </div>

            <div class="modal-row">
                <div class="modal-group">
                    <label for="editFeederCount">Number of Feeders</label>
                    <input type="number" id="editFeederCount" min="0" step="1">
                </div>

                <div class="modal-group">
                    <label for="editDrinkerCount">Number of Drinkers</label>
                    <input type="number" id="editDrinkerCount" min="0" step="1">
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

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="cancelEditHouseModal">Cancel</button>
                <button type="submit" class="save-btn">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay confirm-modal-top" id="confirmEditHouseModal">
    <div class="modal-card archive-house-card">
        <div class="modal-header">
            <h2>Confirm Changes</h2>
            <button type="button" class="modal-close" id="closeConfirmEditHouseModal">&times;</button>
        </div>

        <div>
            <p class="archive-house-text" id="confirmEditHouseMessage">
                Save changes to this house and pen?
            </p>

            <div class="modal-actions">
                <button type="button" class="cancel-btn" id="cancelConfirmEditHouseModal">Cancel</button>
                <button type="button" class="save-btn" id="confirmEditHouseSave">Confirm</button>
            </div>
        </div>
    </div>
</div>
