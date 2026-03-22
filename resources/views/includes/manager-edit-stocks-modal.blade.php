<div class="inventory-modal-overlay" id="feedEditModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Edit Feed Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="feedEditForm">
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="feedInitialStock">Initial Stock</label>
                    <input type="number" id="feedInitialStock" name="initial_stock">
                </div>

                <div class="inventory-form-group">
                    <label for="feedRemainingStock">Remaining</label>
                    <input type="number" id="feedRemainingStock" name="remaining_stock">
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="feedPurchaseDate">Purchase Date</label>
                    <input type="date" id="feedPurchaseDate" name="purchase_date" max="<?= date('Y-m-d') ?>">
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

            <!-- Type of Vitamin on top -->
            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="vitaminType">Type of Vitamin</label>
                    <input type="text" id="vitaminType" name="item_name">
                </div>
            </div>

            <!-- Initial Stock and Remaining side by side -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="vitaminInitialStock">Initial Stock</label>
                    <input type="number" id="vitaminInitialStock" name="initial_stock">
                </div>

                <div class="inventory-form-group">
                    <label for="vitaminRemainingStock">Remaining</label>
                    <input type="number" id="vitaminRemainingStock" name="remaining_stock">
                </div>
            </div>

            <!-- Purchase Date at the bottom -->
            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="vitaminPurchaseDate">Purchase Date</label>
                    <input type="date" id="vitaminPurchaseDate" name="purchase_date" max="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="closeVitaminEditModal">Cancel</button>
                <button type="submit" class="inventory-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>