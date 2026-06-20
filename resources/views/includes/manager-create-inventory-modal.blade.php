<div class="inventory-modal-overlay" id="createInventoryTypeModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <div>
                <h2>Add Inventory</h2>
                <div class="inventory-modal-line"></div>
            </div>
        </div>

        <form id="createInventoryTypeForm" class="inventory-modal-form">
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="inventoryCategory">Inventory Type</label>
                    <select id="inventoryCategory" name="category" required>
                        <option value="">Select type</option>
                        <option value="feed">Feed</option>
                        <option value="vitamin">Vitamins</option>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="newInventoryTypeName" id="inventoryTypeNameLabel">Type of Vitamins</label>
                    <input
                        type="text"
                        id="newInventoryTypeName"
                        name="name"
                        placeholder="Enter inventory item"
                        autocomplete="off"
                        required
                    >
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="newInventoryInitialStock">Initial Stock</label>
                    <input
                        type="number"
                        id="newInventoryInitialStock"
                        name="initial_stock"
                        placeholder="Enter initial stock"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="inventory-form-group">
                    <label for="newInventoryCritical">Critical Level</label>
                    <input
                        type="number"
                        id="newInventoryCritical"
                        name="critical"
                        placeholder="Enter critical level"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>
            </div>

            <p class="inventory-modal-help">
                This adds a new inventory item to the inventory list.
                Initial stock and critical level will be managed from this item.
            </p>

            <p class="inventory-modal-message" id="createInventoryTypeMessage"></p>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="cancelCreateInventoryTypeModal">
                    Cancel
                </button>

                <button type="submit" class="inventory-save-btn">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
