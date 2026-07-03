function showPopup(message, type = "success", callback = null) {
    const oldPopup = document.getElementById("inventoryPopupOverlay");
    if (oldPopup) oldPopup.remove();

    const isError = type === "error";
    const overlay = document.createElement("div");
    overlay.id = "inventoryPopupOverlay";
    overlay.className = "inventory-modal-overlay inventory-confirm-overlay confirm-modal-top show";

    const box = document.createElement("div");
    box.className = "inventory-modal-card inventory-confirm-card";

    box.innerHTML = `
        <div class="inventory-modal-header">
            <div>
                <h2></h2>
                <div class="inventory-modal-line"></div>
            </div>
        </div>

        <p class="inventory-confirm-text" id="inventoryPopupMessage"></p>

        <div class="inventory-modal-actions inventory-confirm-actions">
            <button type="button" class="inventory-save-btn ${isError ? "inventory-confirm-danger" : ""}" id="inventoryPopupOkBtn">
                OK
            </button>
        </div>
    `;

    box.querySelector("h2").textContent = isError ? "Error" : "Success";
    box.querySelector("#inventoryPopupMessage").textContent = message || "";

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    const close = () => {
        overlay.remove();

        if (typeof callback === "function") {
            callback();
        }
    };

    document.getElementById("inventoryPopupOkBtn").addEventListener("click", close);

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            close();
        }
    });
}

function showConfirmPopup(message, callback, options = {}) {
    const oldPopup = document.getElementById("confirmPopupOverlay");
    if (oldPopup) oldPopup.remove();

    const title = options.title || "Confirm Archive";
    const confirmText = options.confirmText || "Archive";
    const isDanger =
        options.variant === "danger" ||
        confirmText.toLowerCase().includes("archive") ||
        options.confirmColor === "#b42318";

    const overlay = document.createElement("div");
    overlay.id = "confirmPopupOverlay";
    overlay.className = "inventory-modal-overlay inventory-confirm-overlay confirm-modal-top show";

    const box = document.createElement("div");
    box.className = "inventory-modal-card inventory-confirm-card";

    box.innerHTML = `
        <div class="inventory-modal-header">
            <div>
                <h2></h2>
                <div class="inventory-modal-line"></div>
            </div>
        </div>

        <p class="inventory-confirm-text"></p>

        <div class="inventory-modal-actions inventory-confirm-actions">
            <button type="button" class="inventory-cancel-btn" id="cancelArchiveBtn">
                Cancel
            </button>
            <button type="button" class="inventory-save-btn ${isDanger ? "inventory-confirm-danger" : ""}" id="confirmArchiveBtn">
            </button>
        </div>
    `;

    box.querySelector("h2").textContent = title;
    box.querySelector(".inventory-confirm-text").textContent = message;
    box.querySelector("#confirmArchiveBtn").textContent = confirmText;
    overlay.appendChild(box);
    document.body.appendChild(overlay);

    const close = (confirmed) => {
        overlay.remove();

        if (typeof callback === "function") {
            callback(confirmed);
        }
    };

    document.getElementById("confirmArchiveBtn").addEventListener("click", () => close(true));
    document.getElementById("cancelArchiveBtn").addEventListener("click", () => close(false));

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            close(false);
        }
    });
}

async function apiRequest(url, method, data = null) {
    const res = await fetch(url, {
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            "Accept": "application/json"
        },
        body: data ? JSON.stringify(data) : null
    });

    const result = await res.json().catch(() => {
        return {
            success: false,
            message: "Invalid server response."
        };
    });

    if (!res.ok) {
        return {
            success: false,
            error: result.error || result.message || "Request failed."
        };
    }

    return result;
}

document.addEventListener("DOMContentLoaded", () => {
    setupCreateInventoryTypeModal();
    setupInventoryModals();
    setupGenerateAnalysisModal();
    setupProfileModal();
    setupInventoryStockSync();
});

function setupGenerateAnalysisModal() {
    const modal = document.getElementById("generateAnalysisModal");
    const openBtn = document.getElementById("openGenerateAnalysisModal");
    const closeBtn = document.getElementById("closeGenerateAnalysisModal");
    const houseSelect = document.getElementById("analysisHouseSelect");

    function openModal() {
        if (houseSelect) {
            houseSelect.value = "";
        }

        modal?.classList.add("show");
    }

    function closeModal() {
        modal?.classList.remove("show");
    }

    openBtn?.addEventListener("click", openModal);
    closeBtn?.addEventListener("click", closeModal);

    modal?.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal?.classList.contains("show")) {
            closeModal();
        }
    });
}

function setupInventoryStockSync() {
    const entries = document.querySelectorAll(".inventory-entry[data-id]");

    if (!entries.length) return;

    let isSyncing = false;

    async function syncInventoryStocks() {
        if (document.hidden || isSyncing) return;

        isSyncing = true;

        try {
            const response = await fetch("/api/manager/inventory/snapshot", {
                headers: {
                    "Accept": "application/json",
                },
            });

            if (!response.ok) return;

            const data = await response.json().catch(() => ({}));
            const items = Array.isArray(data.items) ? data.items : [];

            items.forEach(updateInventoryEntry);
        } catch (error) {
            console.error("Inventory stock sync failed:", error);
        } finally {
            isSyncing = false;
        }
    }

    function updateInventoryEntry(item) {
        const entry = document.querySelector(`.inventory-entry[data-id="${item.id}"]`);

        if (!entry) return;

        const nextRemaining = formatStockNumber(item.remaining_stock);
        const previousRemaining = formatStockNumber(entry.dataset.remainingStock);

        entry.dataset.initialStock = item.initial_stock;
        entry.dataset.remainingStock = item.remaining_stock;
        entry.dataset.critical = item.critical;
        entry.dataset.unit = item.unit;

        const amount = entry.querySelector(".inventory-stock-amount");
        const progressTrack = entry.querySelector(".inventory-progress-track");
        const progressLabel = entry.querySelector(".inventory-progress-label");
        const currentStockInput = getCurrentStockInput(entry);

        if (amount) {
            amount.innerHTML = `${nextRemaining} <span class="inventory-stock-unit">${unitLabel(item.unit)}</span>`;
        }

        if (progressTrack) {
            progressTrack.style.setProperty("--percent", Number(item.percentage ?? 0));
            progressTrack.classList.remove("high", "moderate", "critical");
            progressTrack.classList.add(item.status_class || "high");
        }

        if (progressLabel) {
            progressLabel.textContent = item.status || "";
        }

        if (currentStockInput) {
            currentStockInput.value = item.remaining_stock;
        }

        if (nextRemaining !== previousRemaining) {
            entry.classList.remove("stock-updated");
            void entry.offsetWidth;
            entry.classList.add("stock-updated");
        }
    }

    function getCurrentStockInput(entry) {
        const isFeed = entry.classList.contains("inventory-feed-entry");
        const selectedInput = isFeed
            ? document.getElementById("feedInventoryIdUnified")
            : document.getElementById("vitaminInventoryIdUnified");
        const currentStockInput = isFeed
            ? document.getElementById("feedCurrentStock")
            : document.getElementById("vitaminCurrentStock");

        return selectedInput?.value === String(entry.dataset.id)
            ? currentStockInput
            : null;
    }

    function formatStockNumber(value) {
        const number = Number(value ?? 0);

        return Number.isInteger(number)
            ? number.toString()
            : number.toFixed(2);
    }

    function unitLabel(unit) {
        return unit === "bottles" || unit === "bottle" ? "btls" : unit;
    }

    syncInventoryStocks();
    setInterval(syncInventoryStocks, 5000);
    document.addEventListener("visibilitychange", syncInventoryStocks);
}

function setupCreateInventoryTypeModal() {
    const createInventoryTypeModal = document.getElementById("createInventoryTypeModal");
    const openCreateInventoryTypeModalBtn = document.getElementById("openCreateInventoryTypeModal");
    const closeCreateInventoryTypeModalBtn = document.getElementById("closeCreateInventoryTypeModal");
    const cancelCreateInventoryTypeModalBtn = document.getElementById("cancelCreateInventoryTypeModal");
    const createInventoryTypeForm = document.getElementById("createInventoryTypeForm");
    const inventoryCategory = document.getElementById("inventoryCategory");
    const newInventoryTypeName = document.getElementById("newInventoryTypeName");
    const newInventoryInitialStock = document.getElementById("newInventoryInitialStock");
    const newInventoryCritical = document.getElementById("newInventoryCritical");
    const inventoryTypeNameLabel = document.getElementById("inventoryTypeNameLabel");
    const createInventoryTypeMessage = document.getElementById("createInventoryTypeMessage");

    function setInventoryMessage(message, type = "error") {
        if (!createInventoryTypeMessage) return;

        createInventoryTypeMessage.textContent = message || "";
        createInventoryTypeMessage.className = message
            ? `inventory-modal-message ${type}`
            : "inventory-modal-message";
    }

    function resetCreateInventoryTypeModal() {
        if (inventoryCategory) inventoryCategory.value = "";
        if (newInventoryTypeName) {
            newInventoryTypeName.value = "";
            newInventoryTypeName.placeholder = "Enter inventory item";
        }
        if (newInventoryInitialStock) newInventoryInitialStock.value = "";
        if (newInventoryCritical) newInventoryCritical.value = "";
        if (inventoryTypeNameLabel) inventoryTypeNameLabel.textContent = "Type of Vitamins";

        setInventoryMessage("");
    }

    function openCreateInventoryTypeModal() {
        resetCreateInventoryTypeModal();
        createInventoryTypeModal?.classList.add("show");

        setTimeout(() => {
            inventoryCategory?.focus();
        }, 60);
    }

    function closeCreateInventoryTypeModal() {
        resetCreateInventoryTypeModal();
        createInventoryTypeModal?.classList.remove("show");
    }

    inventoryCategory?.addEventListener("change", () => {
        if (inventoryCategory.value === "feed") {
            if (inventoryTypeNameLabel) inventoryTypeNameLabel.textContent = "Type of Feed";
            if (newInventoryTypeName) newInventoryTypeName.placeholder = "Enter feed type";
            return;
        }

        if (inventoryTypeNameLabel) inventoryTypeNameLabel.textContent = "Type of Vitamins";
        if (newInventoryTypeName) newInventoryTypeName.placeholder = "Enter vitamin type";
    });

    openCreateInventoryTypeModalBtn?.addEventListener("click", openCreateInventoryTypeModal);
    closeCreateInventoryTypeModalBtn?.addEventListener("click", closeCreateInventoryTypeModal);
    cancelCreateInventoryTypeModalBtn?.addEventListener("click", closeCreateInventoryTypeModal);

    createInventoryTypeModal?.addEventListener("click", (event) => {
        if (event.target === createInventoryTypeModal) {
            closeCreateInventoryTypeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && createInventoryTypeModal?.classList.contains("show")) {
            closeCreateInventoryTypeModal();
        }
    });

    createInventoryTypeForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        const category = inventoryCategory?.value || "";
        const name = newInventoryTypeName?.value.trim() || "";
        const initialStock = newInventoryInitialStock?.value || "";
        const critical = newInventoryCritical?.value || "";

        if (!category || !name || initialStock === "" || critical === "") {
            showPopup("Please complete all fields.", "error");
            return;
        }

        showConfirmPopup(`Create "${name}" as a new ${category} inventory item?`, async (confirmed) => {
            if (!confirmed) {
                return;
            }

            try {
            const response = await fetch("/api/manager/inventory/types", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
                },
                body: JSON.stringify({
                    category,
                    name,
                    initial_stock: Number(initialStock),
                    critical: Number(critical),
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : "";
                showPopup(firstError || data.error || data.message || "Unable to save inventory item.", "error");
                return;
            }

            showPopup(`Added "${data.name || name}" successfully.`, "success", () => {
                closeCreateInventoryTypeModal();
                location.reload();
            });
        } catch (error) {
            console.error(error);
            showPopup(error.message || "Unable to save inventory item.", "error");
        }
        }, {
            title: "Confirm Inventory Item",
            confirmText: "Confirm",
            confirmColor: "#1f7a3f",
        });
    });
}

function setupInventoryModals() {
    const feedModal = document.getElementById("feedEditStockModal");
    const vitaminModal = document.getElementById("vitaminEditStockModal");

    const openEditStockBtn = document.getElementById("openEditStockModal");
    const feedModalTypeSelect = document.getElementById("feedModalTypeSelect");
    const vitaminModalTypeSelect = document.getElementById("vitaminModalTypeSelect");

    const feedActionDropdown = document.getElementById("feedActionDropdown");
    const vitaminActionDropdown = document.getElementById("vitaminActionDropdown");

    const closeFeedBtn = document.getElementById("closeFeedEditStockModal");
    const closeVitaminBtn = document.getElementById("closeVitaminEditStockModal");

    const feedEditStockSelect = document.getElementById("feedEditStockSelect");
    const vitaminEditStockSelect = document.getElementById("vitaminEditStockSelect");

    const feedInventoryIdUnified = document.getElementById("feedInventoryIdUnified");
    const vitaminInventoryIdUnified = document.getElementById("vitaminInventoryIdUnified");

    const feedStockAction = document.getElementById("feedStockAction");
    const vitaminStockAction = document.getElementById("vitaminStockAction");

    const feedStockQuantity = document.getElementById("feedStockQuantity");
    const vitaminStockQuantity = document.getElementById("vitaminStockQuantity");

    const feedCurrentStock = document.getElementById("feedCurrentStock");
    const vitaminCurrentStock = document.getElementById("vitaminCurrentStock");

    const feedForm = document.getElementById("feedEditStockForm");
    const vitaminForm = document.getElementById("vitaminEditStockForm");

    const feedModalTitle = document.getElementById("feedModalTitle");
    const vitaminModalTitle = document.getElementById("vitaminModalTitle");

    const feedQuantityLabel = document.getElementById("feedQuantityLabel");
    const vitaminQuantityLabel = document.getElementById("vitaminQuantityLabel");

    const feedCurrentStockGroup = document.getElementById("feedCurrentStockGroup");
    const vitaminCurrentStockGroup = document.getElementById("vitaminCurrentStockGroup");

    const feedStockPurchaseDate = document.getElementById("feedStockPurchaseDate");
    const vitaminStockPurchaseDate = document.getElementById("vitaminStockPurchaseDate");
    const feedPurchaseDateLabel = document.getElementById("feedPurchaseDateLabel");
    const vitaminPurchaseDateLabel = document.getElementById("vitaminPurchaseDateLabel");

    let selectedFeedEntry = null;
    let selectedVitaminEntry = null;
    let currentFeedAction = "";
    let currentVitaminAction = "";

    function getEntriesByType(type) {
        if (type === "feed") {
            return Array.from(document.querySelectorAll(".inventory-feed-entry"));
        }
        return Array.from(document.querySelectorAll(".inventory-vitamin-entry"));
    }

    function populateDropdown(type) {
        const select = type === "feed" ? feedEditStockSelect : vitaminEditStockSelect;
        const entries = getEntriesByType(type);

        if (!select) return;

        select.innerHTML = `
            <option value="">Choose ${type === "feed" ? "feed" : "vitamin"} item</option>
        `;

        entries.forEach((entry) => {
            const option = document.createElement("option");
            option.value = entry.dataset.id;
            option.textContent = entry.dataset.itemName;
            select.appendChild(option);
        });
    }

    function updateFormForAction(type, action) {
        if (type === "feed") {
            currentFeedAction = action;
            if (feedStockAction) feedStockAction.value = action;
            feedModalTitle.textContent = action === "add" ? "Add Feed Stock" : "Reduce Feed Stock";
            feedQuantityLabel.textContent = action === "add" ? "Stock to Add" : "Stock to Reduce";
            feedPurchaseDateLabel.textContent = action === "add" ? "Recent Purchase Date" : "Date Reduced";
            feedCurrentStockGroup.style.display = "flex";
        } else {
            currentVitaminAction = action;
            if (vitaminStockAction) vitaminStockAction.value = action;
            vitaminModalTitle.textContent = action === "add" ? "Add Vitamin Stock" : "Reduce Vitamin Stock";
            vitaminQuantityLabel.textContent = action === "add" ? "Stock to Add" : "Stock to Reduce";
            vitaminPurchaseDateLabel.textContent = action === "add" ? "Recent Purchase Date" : "Date Reduced";
            vitaminCurrentStockGroup.style.display = "flex";
        }
    }

    function openUnifiedEditModal(type) {
        if (type === "feed") {
            vitaminModal?.classList.remove("show");
            populateDropdown("feed");
            if (feedActionDropdown) feedActionDropdown.value = "";
            if (feedEditStockSelect) feedEditStockSelect.value = "";
            if (feedInventoryIdUnified) feedInventoryIdUnified.value = "";
            if (feedStockAction) feedStockAction.value = "";
            if (feedStockQuantity) feedStockQuantity.value = "";
            if (feedCurrentStock) feedCurrentStock.value = "";
            if (feedStockPurchaseDate) feedStockPurchaseDate.value = "";
            feedCurrentStockGroup.style.display = "none";
            if (feedQuantityLabel) feedQuantityLabel.textContent = "Quantity";
            if (feedPurchaseDateLabel) feedPurchaseDateLabel.textContent = "Recent Purchase Date";
            if (feedModalTitle) feedModalTitle.textContent = "Edit Feed Stock";
            if (feedModalTypeSelect) feedModalTypeSelect.value = "feed";
            selectedFeedEntry = null;
            currentFeedAction = "";
            feedModal?.classList.add("show");
        } else {
            feedModal?.classList.remove("show");
            populateDropdown("vitamin");
            if (vitaminActionDropdown) vitaminActionDropdown.value = "";
            if (vitaminEditStockSelect) vitaminEditStockSelect.value = "";
            if (vitaminInventoryIdUnified) vitaminInventoryIdUnified.value = "";
            if (vitaminStockAction) vitaminStockAction.value = "";
            if (vitaminStockQuantity) vitaminStockQuantity.value = "";
            if (vitaminCurrentStock) vitaminCurrentStock.value = "";
            if (vitaminStockPurchaseDate) vitaminStockPurchaseDate.value = "";
            vitaminCurrentStockGroup.style.display = "none";
            if (vitaminQuantityLabel) vitaminQuantityLabel.textContent = "Quantity";
            if (vitaminPurchaseDateLabel) vitaminPurchaseDateLabel.textContent = "Recent Purchase Date";
            if (vitaminModalTitle) vitaminModalTitle.textContent = "Edit Vitamin Stock";
            if (vitaminModalTypeSelect) vitaminModalTypeSelect.value = "vitamin";
            selectedVitaminEntry = null;
            currentVitaminAction = "";
            vitaminModal?.classList.add("show");
        }
    }

    function fillFeedForm(entry) {
        if (!entry) return;
        selectedFeedEntry = entry;

        if (feedInventoryIdUnified) {
            feedInventoryIdUnified.value = entry.dataset.id;
        }

        if (feedCurrentStock) {
            feedCurrentStock.value = entry.dataset.remainingStock;
        }

        if (feedStockQuantity) {
            feedStockQuantity.value = "";
        }
    }

    function fillVitaminForm(entry) {
        if (!entry) return;
        selectedVitaminEntry = entry;

        if (vitaminInventoryIdUnified) {
            vitaminInventoryIdUnified.value = entry.dataset.id;
        }

        if (vitaminCurrentStock) {
            vitaminCurrentStock.value = entry.dataset.remainingStock;
        }

        if (vitaminStockQuantity) {
            vitaminStockQuantity.value = "";
        }
    }

    // Action dropdown change listener for feed
    feedActionDropdown?.addEventListener("change", (e) => {
        const action = e.target.value;
        
        if (!action) {
            // If empty selection, just hide current stock and restore neutral labels
            feedCurrentStockGroup.style.display = "none";
            if (feedCurrentStock) feedCurrentStock.value = "";
            if (feedEditStockSelect) feedEditStockSelect.value = "";
            if (feedInventoryIdUnified) feedInventoryIdUnified.value = "";
            if (feedStockAction) feedStockAction.value = "";
            feedQuantityLabel.textContent = "Quantity";
            feedPurchaseDateLabel.textContent = "Recent Purchase Date";
            feedModalTitle.textContent = "Edit Feed Stock";
            selectedFeedEntry = null;
            return;
        }
        
        updateFormForAction("feed", action);
        
        // Reset form fields
        if (feedEditStockSelect) feedEditStockSelect.value = "";
        if (feedInventoryIdUnified) feedInventoryIdUnified.value = "";
        if (feedStockQuantity) feedStockQuantity.value = "";
        if (feedCurrentStock) feedCurrentStock.value = "";
        if (feedStockPurchaseDate) feedStockPurchaseDate.value = "";
        selectedFeedEntry = null;
    });

    // Action dropdown change listener for vitamin
    vitaminActionDropdown?.addEventListener("change", (e) => {
        const action = e.target.value;
        
        if (!action) {
            // If empty selection, just hide current stock and restore neutral labels
            vitaminCurrentStockGroup.style.display = "none";
            if (vitaminCurrentStock) vitaminCurrentStock.value = "";
            if (vitaminEditStockSelect) vitaminEditStockSelect.value = "";
            if (vitaminInventoryIdUnified) vitaminInventoryIdUnified.value = "";
            if (vitaminStockAction) vitaminStockAction.value = "";
            vitaminQuantityLabel.textContent = "Quantity";
            vitaminPurchaseDateLabel.textContent = "Recent Purchase Date";
            vitaminModalTitle.textContent = "Edit Vitamin Stock";
            selectedVitaminEntry = null;
            return;
        }
        
        updateFormForAction("vitamin", action);
        
        // Reset form fields
        if (vitaminEditStockSelect) vitaminEditStockSelect.value = "";
        if (vitaminInventoryIdUnified) vitaminInventoryIdUnified.value = "";
        if (vitaminStockQuantity) vitaminStockQuantity.value = "";
        if (vitaminCurrentStock) vitaminCurrentStock.value = "";
        if (vitaminStockPurchaseDate) vitaminStockPurchaseDate.value = "";
        selectedVitaminEntry = null;
    });

    // Open button listeners
    openEditStockBtn?.addEventListener("click", () => {
        openUnifiedEditModal("feed");
    });

    feedEditStockSelect?.addEventListener("change", () => {
        const selectedId = feedEditStockSelect.value;
        const entry = document.querySelector(`.inventory-feed-entry[data-id="${selectedId}"]`);
        fillFeedForm(entry);
    });

    vitaminEditStockSelect?.addEventListener("change", () => {
        const selectedId = vitaminEditStockSelect.value;
        const entry = document.querySelector(`.inventory-vitamin-entry[data-id="${selectedId}"]`);
        fillVitaminForm(entry);
    });

    feedModalTypeSelect?.addEventListener("change", (e) => {
        if (e.target.value === "vitamin") {
            openUnifiedEditModal("vitamin");
        }
    });

    vitaminModalTypeSelect?.addEventListener("change", (e) => {
        if (e.target.value === "feed") {
            openUnifiedEditModal("feed");
        }
    });

    closeFeedBtn?.addEventListener("click", () => feedModal?.classList.remove("show"));
    closeVitaminBtn?.addEventListener("click", () => vitaminModal?.classList.remove("show"));

    [feedModal, vitaminModal].forEach((modal) => {
        modal?.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.remove("show");
            }
        });
    });

    feedForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!currentFeedAction) {
            showPopup("Please select an action (Add or Reduce).", "error");
            return;
        }

        if (!feedEditStockSelect.value) {
            showPopup("Please select a feed item.", "error");
            return;
        }

        if (currentFeedAction === "reduce" && !selectedFeedEntry) {
            showPopup("Please select a feed item.", "error");
            return;
        }

        showConfirmPopup(
            `${currentFeedAction === "add" ? "Add" : "Reduce"} feed stock for ${feedEditStockSelect.options[feedEditStockSelect.selectedIndex]?.text || "this item"}?`,
            async (confirmed) => {
                if (!confirmed) {
                    return;
                }

                try {
            let payload;

            if (currentFeedAction === "add") {
                payload = {
                    item_name: selectedFeedEntry
                        ? selectedFeedEntry.dataset.itemName
                        : feedEditStockSelect.options[feedEditStockSelect.selectedIndex]?.text || feedEditStockSelect.value,
                    type: "feed",
                    stock_to_add: Number(feedStockQuantity.value),
                    purchase_date: feedStockPurchaseDate.value
                };

                const res = await apiRequest("/api/manager/inventory", "POST", payload);

                if (res.success) {
                    showPopup(res.message || "Feed stock added successfully.", "success", () => {
                        location.reload();
                    });
                } else {
                    showPopup(res.error || res.message || "Failed to save feed stock.", "error");
                }
            } else {
                const id = selectedFeedEntry.dataset.id;
                const stockToReduce = Number(feedStockQuantity.value);
                const remainingStock = Number(selectedFeedEntry.dataset.remainingStock);

                if (stockToReduce > remainingStock) {
                    showPopup("Stock to reduce cannot be higher than the remaining feed stock.", "error");
                    return;
                }

                payload = {
                    stock_to_reduce: stockToReduce,
                    reduced_date: feedStockPurchaseDate.value
                };

                const res = await apiRequest(`/api/manager/inventory/${id}`, "PUT", payload);

                if (res.success) {
                    showPopup(res.message || "Feed stock reduced successfully.", "success", () => {
                        location.reload();
                    });
                } else {
                    showPopup(res.error || res.message || "Failed to reduce feed stock.", "error");
                }
            }
        } catch (error) {
            console.error(error);
            showPopup("Something went wrong while updating feed stock.", "error");
        }
            },
            {
                title: "Confirm Feed Stock",
                confirmText: "Confirm",
                confirmColor: "#1f7a3f",
            },
        );
    });

    vitaminForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!currentVitaminAction) {
            showPopup("Please select an action (Add or Reduce).", "error");
            return;
        }

        if (!vitaminEditStockSelect.value) {
            showPopup("Please select a vitamin item.", "error");
            return;
        }

        if (currentVitaminAction === "reduce" && !selectedVitaminEntry) {
            showPopup("Please select a vitamin item.", "error");
            return;
        }

        showConfirmPopup(
            `${currentVitaminAction === "add" ? "Add" : "Reduce"} vitamin stock for ${vitaminEditStockSelect.options[vitaminEditStockSelect.selectedIndex]?.text || "this item"}?`,
            async (confirmed) => {
                if (!confirmed) {
                    return;
                }

                try {
            let payload;

            if (currentVitaminAction === "add") {
                payload = {
                    item_name: selectedVitaminEntry
                        ? selectedVitaminEntry.dataset.itemName
                        : vitaminEditStockSelect.options[vitaminEditStockSelect.selectedIndex]?.text || vitaminEditStockSelect.value,
                    type: "vitamin",
                    stock_to_add: Number(vitaminStockQuantity.value),
                    purchase_date: vitaminStockPurchaseDate.value
                };

                const res = await apiRequest("/api/manager/inventory", "POST", payload);

                if (res.success) {
                    showPopup(res.message || "Vitamin stock added successfully.", "success", () => {
                        location.reload();
                    });
                } else {
                    showPopup(res.error || res.message || "Failed to save vitamin stock.", "error");
                }
            } else {
                const id = selectedVitaminEntry.dataset.id;
                const stockToReduce = Number(vitaminStockQuantity.value);
                const remainingStock = Number(selectedVitaminEntry.dataset.remainingStock);

                if (stockToReduce > remainingStock) {
                    showPopup("Stock to reduce cannot be higher than the remaining vitamin stock.", "error");
                    return;
                }

                payload = {
                    stock_to_reduce: stockToReduce,
                    reduced_date: vitaminStockPurchaseDate.value
                };

                const res = await apiRequest(`/api/manager/inventory/${id}`, "PUT", payload);

                if (res.success) {
                    showPopup(res.message || "Vitamin stock reduced successfully.", "success", () => {
                        location.reload();
                    });
                } else {
                    showPopup(res.error || res.message || "Failed to reduce vitamin stock.", "error");
                }
            }
        } catch (error) {
            console.error(error);
            showPopup("Something went wrong while updating vitamin stock.", "error");
        }
            },
            {
                title: "Confirm Vitamin Stock",
                confirmText: "Confirm",
                confirmColor: "#1f7a3f",
            },
        );
    });

    setupArchiveButtons();
}

function setupArchiveButtons() {
    const archiveModal = document.getElementById("archiveInventoryModal");
    const openArchiveBtn = document.getElementById("openArchiveInventoryModal");
    const closeArchiveBtn = document.getElementById("closeArchiveInventoryModal");
    const archiveForm = document.getElementById("archiveInventoryForm");
    const archiveType = document.getElementById("archiveInventoryType");
    const archiveItem = document.getElementById("archiveInventoryItem");

    openArchiveBtn?.addEventListener("click", () => {
        archiveForm?.reset();

        if (archiveItem) {
            archiveItem.innerHTML = `<option value="">Select item</option>`;
        }

        archiveModal?.classList.add("show");
    });

    closeArchiveBtn?.addEventListener("click", () => {
        archiveModal?.classList.remove("show");
    });

    archiveModal?.addEventListener("click", (event) => {
        if (event.target === archiveModal) {
            archiveModal.classList.remove("show");
        }
    });

    archiveType?.addEventListener("change", () => {
        const type = archiveType.value;

        if (!archiveItem) return;

        archiveItem.innerHTML = `<option value="">Select item</option>`;

        if (!type) return;

        const entries = type === "feed"
            ? document.querySelectorAll(".inventory-feed-entry")
            : document.querySelectorAll(".inventory-vitamin-entry");

        entries.forEach((entry) => {
            const option = document.createElement("option");
            option.value = entry.dataset.id;
            option.textContent = entry.dataset.itemName;
            archiveItem.appendChild(option);
        });
    });

    archiveForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        const id = archiveItem?.value;

        if (!id) {
            showPopup("Please select an item to archive.", "error");
            return;
        }

        showConfirmPopup("Archive this inventory item?", async (confirmed) => {
            if (!confirmed) {
                return;
            }

            try {
                const res = await apiRequest(`/api/manager/inventory/${id}`, "DELETE");

                if (res.success) {
                    showPopup(res.message || "Inventory archived successfully.", "success", () => {
                        location.reload();
                    });
                } else {
                    showPopup(res.error || res.message || "Archive failed.", "error");
                }
            } catch (error) {
                console.error(error);
                showPopup("Something went wrong while archiving inventory.", "error");
            }
        });
    });
}

async function populateProfileModal() {
    try {
        const response = await fetch("/api/user");

        if (!response.ok) {
            throw new Error("Failed to fetch user info");
        }

        const user = await response.json();

        document.getElementById("profileFirstName").value = user.FirstName || "";
        document.getElementById("profileMiddleName").value = user.MiddleName || "";
        document.getElementById("profileLastName").value = user.LastName || "";
        document.getElementById("profileSuffix").value = user.Suffix || "";
        document.getElementById("profileRole").value = user.Role || "";
        document.getElementById("profilePhone").value = user.PhoneNumber || "";
        document.getElementById("profileId").value = user.EmployeeId || "";
        document.getElementById("profileBirthday").value = user.Birthday || "";
        document.getElementById("profileGender").value = user.Gender || "";
        document.getElementById("profileAddress").value = user.Address || "";
    } catch (e) {
        console.error(e);
    }
}

function setupProfileModal() {
    const profileModal = document.getElementById("profileModal");
    const openProfileModalBtn = document.getElementById("openProfileModal");
    const closeProfileModalBtn = document.getElementById("closeProfileModal");

    openProfileModalBtn?.addEventListener("click", async () => {
        await populateProfileModal();
        profileModal?.classList.add("show");
    });

    closeProfileModalBtn?.addEventListener("click", () => {
        profileModal?.classList.remove("show");
    });

    profileModal?.addEventListener("click", (event) => {
        if (event.target === profileModal) {
            profileModal.classList.remove("show");
        }
    });
}
