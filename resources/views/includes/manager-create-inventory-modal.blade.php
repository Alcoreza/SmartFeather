<div class="manager-management-modal-backdrop" id="createInventoryTypeModal">
    <div class="manager-management-modal-card">
        <div class="manager-management-modal-header">
            <div>
                <p class="manager-management-modal-eyebrow">Management</p>
                <h2>Add Inventory</h2>
            </div>

            <button type="button" class="manager-management-modal-close" id="closeCreateInventoryTypeModal">×</button>
        </div>

        <form id="createInventoryTypeForm" class="manager-management-modal-form">
            <div class="management-form-row">
                <div class="management-form-group">
                    <label for="inventoryCategory">Inventory Type</label>
                    <select id="inventoryCategory" name="category" required>
                        <option value="">Select type</option>
                        <option value="feed">Feed</option>
                        <option value="vitamin">Vitamins</option>
                    </select>
                </div>

                <div class="management-form-group">
                    <label for="newInventoryTypeName" id="inventoryTypeNameLabel">Type of Vitamins</label>
                    <input
                        type="text"
                        id="newInventoryTypeName"
                        name="name"
                        placeholder="Enter inventory type"
                        autocomplete="off"
                        required
                    >
                </div>
            </div>

            <div class="management-form-row">
                <div class="management-form-group">
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

                <div class="management-form-group">
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

            <p class="manager-management-modal-help">
                This adds a new inventory type to the inventory dropdown list.
                Initial stock and critical level will be permanent and managed here.
            </p>

            <p class="manager-management-modal-message" id="createInventoryTypeMessage"></p>

            <div class="manager-management-modal-actions">
                <button type="button" class="manager-management-modal-secondary-btn" id="cancelCreateInventoryTypeModal">
                    Cancel
                </button>

                <button type="submit" class="manager-management-modal-primary-btn">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>