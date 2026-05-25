<div class="inventory-modal-overlay" id="feedEditModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Edit Feed Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="feedEditForm">
            <div class="inventory-form-row">
            <div class="inventory-form-group">
                <label for="feedEditSelect">Select Feed</label>
                <select id="feedEditSelect" name="selected_item" required>
                    <option value="">Choose feed item</option>
                </select>
            </div>

            <div class="inventory-form-group">
                <label for="feedUnit">Unit</label>
                    <input
                        type="text"
                        id="feedUnit"
                        name="unit"
                        value="kg"
                        readonly
                    >
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="feedInitialStock">Stock to Edit</label>
                    <input type="number" id="feedInitialStock" name="initial_stock" min="0" step="0.01" required>
                </div>

                <div class="inventory-form-group">
                    <label for="feedRemainingStock">Remaining</label>
                    <input type="number" id="feedRemainingStock" name="remaining_stock" min="0" step="0.01" required>
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addFeedCriticalStock">Critical Level</label>
                    <input type="number" id="addFeedCriticalStock" name="critical" min="0" step="0.01" required>
                </div>

                <div class="inventory-form-group">
                    <label for="addFeedPurchaseDate">Purchase Date</label>
                    <input type="date" id="addFeedPurchaseDate" name="purchase_date" max="{{ now()->format('Y-m-d') }}" required>
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
                    <label for="vitaminEditSelect">Select Vitamin</label>
                    <select id="vitaminEditSelect" name="selected_item" required>
                        <option value="">Choose vitamin item</option>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="vitaminUnit">Unit</label>
                    <input
                        type="text"
                        id="vitaminUnit"
                        name="unit"
                        value="bottle"
                        readonly
                    >
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="vitaminInitialStock">Stock to Edit</label>
                    <input type="number" id="vitaminInitialStock" name="initial_stock" min="0" step="1" required>
                </div>

                <div class="inventory-form-group">
                    <label for="vitaminRemainingStock">Remaining</label>
                    <input type="number" id="vitaminRemainingStock" name="remaining_stock" min="0" step="1" required>
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addFeedCriticalStock">Critical Level</label>
                    <input type="number" id="addFeedCriticalStock" name="critical" min="0" step="0.01" required>
                </div>

                <div class="inventory-form-group">
                    <label for="addFeedPurchaseDate">Purchase Date</label>
                    <input type="date" id="addFeedPurchaseDate" name="purchase_date" max="{{ now()->format('Y-m-d') }}" required>
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="closeVitaminEditModal">Cancel</button>
                <button type="submit" class="inventory-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>