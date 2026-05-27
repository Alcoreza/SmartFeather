<div class="inventory-modal-overlay" id="feedEditModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Reduce Feed Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="feedEditForm">

            <input
                type="hidden"
                id="feedInventoryId"
                name="inventory_id"
            >

            <div class="inventory-form-row">

                <div class="inventory-form-group">
                    <label for="feedEditSelect">Select Feed</label>

                    <select
                        id="feedEditSelect"
                        name="selected_item"
                        required
                    >
                        <option value="">Choose feed item</option>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="feedUnit">Unit</label>

                    <input
                        type="text"
                        id="feedUnit"
                        value="kg"
                        readonly
                    >
                </div>

            </div>

            <div class="inventory-form-row">

                <div class="inventory-form-group">
                    <label for="feedStockToReduce">
                        Stock to Reduce
                    </label>

                    <input
                        type="number"
                        id="feedStockToReduce"
                        name="stock_to_reduce"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="inventory-form-group">
                    <label for="feedCurrentRemaining">
                        Current Remaining
                    </label>

                    <input
                        type="number"
                        id="feedCurrentRemaining"
                        readonly
                    >
                </div>

            </div>

            <div class="inventory-modal-actions">
                <button
                    type="button"
                    class="inventory-cancel-btn"
                    id="closeFeedEditModal"
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

<div class="inventory-modal-overlay" id="vitaminEditModal">
    <div class="inventory-modal-card">
        <div class="inventory-modal-header">
            <h2>Reduce Vitamin Stock</h2>
            <div class="inventory-modal-line"></div>
        </div>

        <form class="inventory-modal-form" id="vitaminEditForm">

            <input
                type="hidden"
                id="vitaminInventoryId"
                name="inventory_id"
            >

            <div class="inventory-form-row">

                <div class="inventory-form-group">
                    <label for="vitaminEditSelect">
                        Select Vitamin
                    </label>

                    <select
                        id="vitaminEditSelect"
                        name="selected_item"
                        required
                    >
                        <option value="">
                            Choose vitamin item
                        </option>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="vitaminUnit">Unit</label>

                    <input
                        type="text"
                        id="vitaminUnit"
                        value="bottle"
                        readonly
                    >
                </div>

            </div>

            <div class="inventory-form-row">

                <div class="inventory-form-group">
                    <label for="vitaminStockToReduce">
                        Stock to Reduce
                    </label>

                    <input
                        type="number"
                        id="vitaminStockToReduce"
                        name="stock_to_reduce"
                        min="0"
                        step="1"
                        required
                    >
                </div>

                <div class="inventory-form-group">
                    <label for="vitaminCurrentRemaining">
                        Current Remaining
                    </label>

                    <input
                        type="number"
                        id="vitaminCurrentRemaining"
                        readonly
                    >
                </div>

            </div>

            <div class="inventory-modal-actions">

                <button
                    type="button"
                    class="inventory-cancel-btn"
                    id="closeVitaminEditModal"
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