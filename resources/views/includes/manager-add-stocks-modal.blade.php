<div class="inventory-modal-overlay" id="feedAddModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Add Feed Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="feedAddForm">
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addFeedName">Feed Name</label>
                    <input type="text" id="addFeedName" name="item_name">
                </div>

                <div class="inventory-form-group">
                    <label for="addFeedUnit">Unit</label>
                    <input type="text" id="addFeedUnit" name="unit" placeholder="kg">
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addFeedInitialStock">Initial Stock</label>
                    <input type="number" id="addFeedInitialStock" name="initial_stock">
                </div>

                <div class="inventory-form-group">
                    <label for="addFeedRemainingStock">Remaining</label>
                    <input type="number" id="addFeedRemainingStock" name="remaining_stock">
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="addFeedPurchaseDate">Purchase Date</label>
                    <input type="text" id="addFeedPurchaseDate" name="purchase_date" placeholder="2026-01-25">
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="closeFeedAddModal">Cancel</button>
                <button type="submit" class="inventory-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="inventory-modal-overlay" id="vitaminAddModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Add Vitamin Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="vitaminAddForm">
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addVitaminName">Type of Vitamin</label>
                    <input type="text" id="addVitaminName" name="item_name">
                </div>

                <div class="inventory-form-group">
                    <label for="addVitaminUnit">Unit</label>
                    <input type="text" id="addVitaminUnit" name="unit" placeholder="mL">
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addVitaminInitialStock">Initial Stock</label>
                    <input type="number" id="addVitaminInitialStock" name="initial_stock">
                </div>

                <div class="inventory-form-group">
                    <label for="addVitaminRemainingStock">Remaining</label>
                    <input type="number" id="addVitaminRemainingStock" name="remaining_stock">
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="addVitaminPurchaseDate">Purchase Date</label>
                    <input type="text" id="addVitaminPurchaseDate" name="purchase_date" placeholder="2026-01-25">
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="closeVitaminAddModal">Cancel</button>
                <button type="submit" class="inventory-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>