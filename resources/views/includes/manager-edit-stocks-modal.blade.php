<div class="inventory-modal-overlay" id="feedEditModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Edit Feed Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="feedEditForm">
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="feedName">Feed Name</label>
                    <input type="text" id="feedName" name="item_name">
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="feedInitialStock">Initial Stock</label>
                    <input type="text" id="feedInitialStock" name="initial_stock">
                </div>

                <div class="inventory-form-group">
                    <label for="feedRemainingStock">Remaining</label>
                    <input type="text" id="feedRemainingStock" name="remaining_stock">
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="feedPurchaseDate">Purchase Date</label>
                    <input type="text" id="feedPurchaseDate" name="purchase_date">
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="closeFeedEditModal">Cancel</button>
                <button type="submit" class="inventory-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="inventory-modal-overlay" id="vitaminEditModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Edit Vitamin Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="vitaminEditForm">
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="vitaminType">Type of Vitamin</label>
                    <input type="text" id="vitaminType" name="item_name">
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="vitaminInitialStock">Initial Stock</label>
                    <input type="text" id="vitaminInitialStock" name="initial_stock">
                </div>

                <div class="inventory-form-group">
                    <label for="vitaminRemainingStock">Remaining</label>
                    <input type="text" id="vitaminRemainingStock" name="remaining_stock">
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="vitaminPurchaseDate">Purchase Date</label>
                    <input type="text" id="vitaminPurchaseDate" name="purchase_date">
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="closeVitaminEditModal">Cancel</button>
                <button type="submit" class="inventory-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>