<div class="inventory-modal-overlay" id="feedEditStockModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2 id="feedModalTitle">Edit Feed Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="feedEditStockForm">
            <input type="hidden" id="feedStockAction" name="action" value="">
            <input type="hidden" id="feedInventoryIdUnified" name="inventory_id">

            <!-- Inventory Type and Action Selection -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="feedModalTypeSelect">Inventory Type</label>
                    <select id="feedModalTypeSelect" required>
                        <option value="feed">Feed</option>
                        <option value="vitamin">Vitamins</option>
                    </select>
                </div>
                <div class="inventory-form-group">
                    <label for="feedActionDropdown">Select Action</label>
                    <select id="feedActionDropdown" required>
                        <option value="">Choose action</option>
                        <option value="add">Add Stock</option>
                        <option value="reduce">Reduce Stock</option>
                    </select>
                </div>
            </div>

            <!-- Item Selection -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="feedEditStockSelect">Select Feed</label>
                    <select id="feedEditStockSelect" name="selected_item" required>
                        <option value="">Choose feed item</option>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="feedStockUnit">Unit</label>
                    <input type="text" id="feedStockUnit" value="kg" readonly>
                </div>
            </div>

            <!-- Quantity Input -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="feedStockQuantity" id="feedQuantityLabel">
                        Quantity
                    </label>
                    <input
                        type="number"
                        id="feedStockQuantity"
                        name="stock_quantity"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="inventory-form-group" id="feedCurrentStockGroup" style="display: none;">
                    <label for="feedCurrentStock">
                        Current Stock
                    </label>
                    <input
                        type="number"
                        id="feedCurrentStock"
                        readonly
                    >
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="feedStockPurchaseDate" id="feedPurchaseDateLabel">Recent Purchase Date</label>
                    <input
                        type="date"
                        id="feedStockPurchaseDate"
                        name="purchase_date"
                        max="{{ now()->format('Y-m-d') }}"
                        required
                    >
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button
                    type="button"
                    class="inventory-cancel-btn"
                    id="closeFeedEditStockModal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inventory-save-btn"
                    id="feedSaveBtn"
                >
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<div class="inventory-modal-overlay" id="vitaminEditStockModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2 id="vitaminModalTitle">Edit Vitamin Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="vitaminEditStockForm">
            <input type="hidden" id="vitaminStockAction" name="action" value="">
            <input type="hidden" id="vitaminInventoryIdUnified" name="inventory_id">

            <!-- Inventory Type and Action Selection -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="vitaminModalTypeSelect">Inventory Type</label>
                    <select id="vitaminModalTypeSelect" required>
                        <option value="feed">Feed</option>
                        <option value="vitamin">Vitamins</option>
                    </select>
                </div>
                <div class="inventory-form-group">
                    <label for="vitaminActionDropdown">Select Action</label>
                    <select id="vitaminActionDropdown" required>
                        <option value="">Choose action</option>
                        <option value="add">Add Stock</option>
                        <option value="reduce">Reduce Stock</option>
                    </select>
                </div>
            </div>

            <!-- Item Selection -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="vitaminEditStockSelect">Select Vitamin</label>
                    <select id="vitaminEditStockSelect" name="selected_item" required>
                        <option value="">Choose vitamin item</option>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="vitaminStockUnit">Unit</label>
                    <input type="text" id="vitaminStockUnit" value="bottle" readonly>
                </div>
            </div>

            <!-- Quantity Input -->
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="vitaminStockQuantity" id="vitaminQuantityLabel">
                        Quantity
                    </label>
                    <input
                        type="number"
                        id="vitaminStockQuantity"
                        name="stock_quantity"
                        min="0"
                        step="1"
                        required
                    >
                </div>

                <div class="inventory-form-group" id="vitaminCurrentStockGroup" style="display: none;">
                    <label for="vitaminCurrentStock">
                        Current Stock
                    </label>
                    <input
                        type="number"
                        id="vitaminCurrentStock"
                        readonly
                    >
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="vitaminStockPurchaseDate" id="vitaminPurchaseDateLabel">Recent Purchase Date</label>
                    <input
                        type="date"
                        id="vitaminStockPurchaseDate"
                        name="purchase_date"
                        max="{{ now()->format('Y-m-d') }}"
                        required
                    >
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button
                    type="button"
                    class="inventory-cancel-btn"
                    id="closeVitaminEditStockModal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inventory-save-btn"
                    id="vitaminSaveBtn"
                >
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
