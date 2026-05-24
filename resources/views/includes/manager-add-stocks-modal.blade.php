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
                    <select id="addFeedName" name="item_name" required>
                        <option value="">Select feed type</option>
                        <option value="Starter Feed">Starter Feed</option>
                        <option value="Grower Feed">Grower Feed</option>
                        <option value="Finisher Feed">Finisher Feed</option>
                    </select>
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

            <!-- Vitamin Type + Unit -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addVitaminName">Type of Vitamin</label>
                    <select id="addVitaminName" name="item_name" required>
                        <option value="">Select vitamin type</option>
                        <option value="Vitamin K">Vitamin K</option>
                        <option value="Vitamin D3">Vitamin D3</option>
                        <option value="Vitamin B-Complex">Vitamin B-Complex</option>
                        <option value="Vitamin C">Vitamin C</option>
                        <option value="Vitamin A">Vitamin A</option>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="addVitaminUnit">Unit</label>
                    <input
                        type="text"
                        id="addVitaminUnit"
                        name="unit"
                        value="bottle"
                        readonly
                    >
                </div>
            </div>

            <!-- Initial Stock + Remaining -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addVitaminInitialStock">Initial Stock</label>
                    <input
                        type="number"
                        id="addVitaminInitialStock"
                        name="initial_stock"
                        min="0"
                        step="1"
                        required
                    >
                </div>

                <div class="inventory-form-group">
                    <label for="addVitaminRemainingStock">Remaining</label>
                    <input
                        type="number"
                        id="addVitaminRemainingStock"
                        name="remaining_stock"
                        min="0"
                        step="1"
                        readonly
                        required
                    >
                </div>
            </div>

            <!-- Critical Level -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addVitaminCriticalStock">Critical Level</label>
                    <input type="number" id="addVitaminCriticalStock" name="critical" min="0" step="1" required>
                </div>

                <div class="inventory-form-group">
                    <label for="addVitaminPurchaseDate">Purchase Date</label>
                    <input type="date" id="addVitaminPurchaseDate" name="purchase_date" max="{{ now()->format('Y-m-d') }}" required>
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button
                    type="button"
                    class="inventory-cancel-btn"
                    id="closeVitaminAddModal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inventory-save-btn"
                >
                    Save
                </button>
            </div>

        </form>
    </div>
</div>