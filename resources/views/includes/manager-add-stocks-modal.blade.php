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
                    <input type="text" id="addFeedName" name="item_name" required>
                </div>

                <div class="inventory-form-group">
                    <label for="addFeedUnit">Unit</label>
                    <input type="text" id="addFeedUnit" name="unit" placeholder="kg" required>
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addFeedInitialStock">Initial Stock</label>
                    <input type="number" id="addFeedInitialStock" name="initial_stock" min="0" step="0.01" required>
                </div>

                <div class="inventory-form-group">
                    <label for="addFeedRemainingStock">Remaining</label>
                    <input type="number" id="addFeedRemainingStock" name="remaining_stock" min="0" step="0.01" required>
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="addFeedCriticalStock">Critical Level</label>
                    <input type="number" id="addFeedCriticalStock" name="critical" min="0" step="0.01" required>
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="addFeedPurchaseDate">Purchase Date</label>
                    <input type="date" id="addFeedPurchaseDate" name="purchase_date" max="{{ now()->format('Y-m-d') }}" required>
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
            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="addVitaminName">Type of Vitamin</label>
                    <input type="text" id="addVitaminName" name="item_name" required>
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="addVitaminUnit">Unit</label>
                    <input type="text" id="addVitaminUnit" name="unit" value="bottles" readonly>
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addVitaminInitialStock">Initial Stock</label>
                    <input type="number" id="addVitaminInitialStock" name="initial_stock" min="0" step="1" required>
                </div>

                <div class="inventory-form-group">
                    <label for="addVitaminRemainingStock">Remaining</label>
                    <input type="number" id="addVitaminRemainingStock" name="remaining_stock" min="0" step="1" required>
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="addVitaminCriticalStock">Critical Level</label>
                    <input type="number" id="addVitaminCriticalStock" name="critical" min="0" step="1" required>
                </div>
            </div>

            <div class="inventory-form-row inventory-form-row-single">
                <div class="inventory-form-group">
                    <label for="addVitaminPurchaseDate">Purchase Date</label>
                    <input type="date" id="addVitaminPurchaseDate" name="purchase_date" max="{{ now()->format('Y-m-d') }}" required>
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="closeVitaminAddModal">Cancel</button>
                <button type="submit" class="inventory-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>