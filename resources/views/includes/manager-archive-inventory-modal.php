<div class="inventory-modal-overlay" id="archiveInventoryModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Archive Inventory</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="archiveInventoryForm">
            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="archiveInventoryType">
                        Inventory Type
                    </label>

                    <select id="archiveInventoryType" required>
                        <option value="">Select type</option>
                        <option value="feed">Feed</option>
                        <option value="vitamin">Vitamins</option>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="archiveInventoryItem">
                        Inventory Item
                    </label>

                    <select id="archiveInventoryItem" required>
                        <option value="">Select item</option>
                    </select>
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button
                    type="button"
                    class="inventory-cancel-btn"
                    id="closeArchiveInventoryModal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inventory-save-btn"
                >
                    Archive
                </button>
            </div>
        </form>
    </div>
</div>