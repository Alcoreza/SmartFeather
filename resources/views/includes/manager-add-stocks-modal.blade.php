<div class="inventory-modal-overlay" id="feedAddModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Add Feed Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="feedAddForm">
            <input type="hidden" name="type" value="feed">

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addFeedName">Feed Name</label>
                    <select id="addFeedName" name="item_name" required>
                        <option value="">Select feed type</option>
                        @foreach ($feedTypeOptions as $type)
                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="addFeedUnit">Unit</label>
                    <input
                        type="text"
                        id="addFeedUnit"
                        value="kg"
                        readonly
                    >
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addFeedStockToAdd">Stock to Add</label>
                    <input
                        type="number"
                        id="addFeedStockToAdd"
                        name="stock_to_add"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="inventory-form-group">
                    <label for="addFeedPurchaseDate">Recent Purchase Date</label>
                    <input
                        type="date"
                        id="addFeedPurchaseDate"
                        name="purchase_date"
                        max="{{ now()->format('Y-m-d') }}"
                        required
                    >
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
            <input type="hidden" name="type" value="vitamin">

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addVitaminName">Type of Vitamin</label>
                    <select id="addVitaminName" name="item_name" required>
                        <option value="">Select vitamin type</option>
                        @foreach ($vitaminTypeOptions as $type)
                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="addVitaminUnit">Unit</label>
                    <input
                        type="text"
                        id="addVitaminUnit"
                        value="bottle"
                        readonly
                    >
                </div>
            </div>

            <div class="inventory-form-row">
                <div class="inventory-form-group">
                    <label for="addVitaminStockToAdd">Stock to Add</label>
                    <input
                        type="number"
                        id="addVitaminStockToAdd"
                        name="stock_to_add"
                        min="0"
                        step="1"
                        required
                    >
                </div>

                <div class="inventory-form-group">
                    <label for="addVitaminPurchaseDate">Recent Purchase Date</label>
                    <input
                        type="date"
                        id="addVitaminPurchaseDate"
                        name="purchase_date"
                        max="{{ now()->format('Y-m-d') }}"
                        required
                    >
                </div>
            </div>

            <div class="inventory-modal-actions">
                <button type="button" class="inventory-cancel-btn" id="closeVitaminAddModal">Cancel</button>
                <button type="submit" class="inventory-save-btn">Save</button>
            </div>
        </form>
    </div>
</div>