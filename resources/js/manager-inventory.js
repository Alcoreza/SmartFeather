async function apiRequest(url, method, data = null) {
    const res = await fetch(url, {
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: data ? JSON.stringify(data) : null
    });

    return res.json();
}

document.addEventListener("DOMContentLoaded", () => {
    setupInventoryModals();
    setupProfileModal();
});

function setupInventoryModals() {
    const feedModal = document.getElementById("feedEditModal");
    const vitaminModal = document.getElementById("vitaminEditModal");
    const feedAddModal = document.getElementById("feedAddModal");
    const vitaminAddModal = document.getElementById("vitaminAddModal");

    const openFeedBtn = document.getElementById("openFeedEditModal");
    const openVitaminBtn = document.getElementById("openVitaminEditModal");
    const openFeedAddBtn = document.getElementById("openFeedAddModal");
    const openVitaminAddBtn = document.getElementById("openVitaminAddModal");

    const closeFeedBtn = document.getElementById("closeFeedEditModal");
    const closeVitaminBtn = document.getElementById("closeVitaminEditModal");
    const closeFeedAddBtn = document.getElementById("closeFeedAddModal");
    const closeVitaminAddBtn = document.getElementById("closeVitaminAddModal");

    const feedEntriesSelector = ".inventory-feed-entry";
    const vitaminEntriesSelector = ".inventory-vitamin-entry";

    const feedInitialStock = document.getElementById("feedInitialStock");
    const feedRemainingStock = document.getElementById("feedRemainingStock");
    const feedPurchaseDate = document.getElementById("feedPurchaseDate");

    const vitaminInitialStock = document.getElementById("vitaminInitialStock");
    const vitaminRemainingStock = document.getElementById("vitaminRemainingStock");
    const vitaminType = document.getElementById("vitaminType");
    const vitaminPurchaseDate = document.getElementById("vitaminPurchaseDate");

    const addFeedName = document.getElementById("addFeedName");
    const addFeedUnit = document.getElementById("addFeedUnit");
    const addFeedInitialStock = document.getElementById("addFeedInitialStock");
    const addFeedRemainingStock = document.getElementById("addFeedRemainingStock");
    const addFeedPurchaseDate = document.getElementById("addFeedPurchaseDate");

    const addVitaminName = document.getElementById("addVitaminName");
    const addVitaminUnit = document.getElementById("addVitaminUnit");
    const addVitaminInitialStock = document.getElementById("addVitaminInitialStock");
    const addVitaminRemainingStock = document.getElementById("addVitaminRemainingStock");
    const addVitaminPurchaseDate = document.getElementById("addVitaminPurchaseDate");

    const feedForm = document.getElementById("feedEditForm");
    const vitaminForm = document.getElementById("vitaminEditForm");
    const feedAddForm = document.getElementById("feedAddForm");
    const vitaminAddForm = document.getElementById("vitaminAddForm");

    const feedList = document.getElementById("feedInventoryList");
    const vitaminList = document.getElementById("vitaminInventoryList");

    let selectedFeedEntry = null;
    let selectedVitaminEntry = null;

    function bindSelection() {
        const feedEntries = document.querySelectorAll(feedEntriesSelector);
        const vitaminEntries = document.querySelectorAll(vitaminEntriesSelector);

        if (!selectedFeedEntry && feedEntries.length > 0) {
            feedEntries[0].classList.add("selected");
            selectedFeedEntry = feedEntries[0];
        }

        if (!selectedVitaminEntry && vitaminEntries.length > 0) {
            vitaminEntries[0].classList.add("selected");
            selectedVitaminEntry = vitaminEntries[0];
        }

        feedEntries.forEach((entry) => {
            if (entry.dataset.bound === "true") return;

            entry.addEventListener("click", () => {
                document.querySelectorAll(feedEntriesSelector).forEach((item) => {
                    item.classList.remove("selected");
                });

                entry.classList.add("selected");
                selectedFeedEntry = entry;
            });

            entry.dataset.bound = "true";
        });

        vitaminEntries.forEach((entry) => {
            if (entry.dataset.bound === "true") return;

            entry.addEventListener("click", () => {
                document.querySelectorAll(vitaminEntriesSelector).forEach((item) => {
                    item.classList.remove("selected");
                });

                entry.classList.add("selected");
                selectedVitaminEntry = entry;
            });

            entry.dataset.bound = "true";
        });
    }

    // ✅ UPDATED createInventoryEntry
    function createInventoryEntry({ type, id, itemName, initialStock, remainingStock, purchaseDate, unit }) {
        const percentage = getInventoryPercentage(remainingStock, initialStock);
        const status = getInventoryStatus(percentage);

        const commonAttributes = `
            data-id="${id}"
            data-item-name="${itemName}"
            data-initial-stock="${initialStock}"
            data-remaining-stock="${remainingStock}"
            data-purchase-date="${purchaseDate}"
            data-unit="${unit}"
        `;

        if (type === "feed") {
            return `
                <div class="inventory-entry inventory-feed-entry" ${commonAttributes}>
                    <h3 class="inventory-item-name">${itemName}</h3>

                    <div class="inventory-item-block">
                        <div class="inventory-card inventory-stock-card">
                            <div class="inventory-ring ${status.className}" style="--percent: ${percentage};">
                                <div class="inventory-ring-inner"></div>
                            </div>

                            <div class="inventory-stock-details">
                                <span class="inventory-status-badge ${status.className}">
                                    ${status.label}
                                </span>

                                <p><strong>Initial Stock:</strong> ${initialStock} ${unit}</p>
                                <p><strong>Remaining:</strong> ${remainingStock} ${unit}</p>
                            </div>
                        </div>

                        <div class="inventory-card inventory-date-card">
                            <div class="inventory-date-icon">🗓</div>
                            <p><strong>Purchase Date:</strong> ${purchaseDate}</p>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="inventory-entry inventory-vitamin-entry" ${commonAttributes}>
                <h3 class="inventory-item-name">${itemName}</h3>

                <div class="inventory-item-block">
                    <div class="inventory-card inventory-stock-card">
                        <div class="inventory-ring ${status.className}" style="--percent: ${percentage};">
                            <div class="inventory-ring-inner"></div>
                        </div>

                        <div class="inventory-stock-details">
                            <span class="inventory-status-badge ${status.className}">
                                ${status.label}
                            </span>

                            <p><strong>Initial Stock:</strong> ${initialStock} ${unit}</p>
                            <p><strong>Remaining:</strong> ${remainingStock} ${unit}</p>
                        </div>
                    </div>

                    <div class="inventory-card inventory-date-card">
                        <div class="inventory-date-icon">🗓</div>
                        <p><strong>Purchase Date:</strong> ${purchaseDate}</p>
                    </div>
                </div>
            </div>
        `;
    }

    bindSelection();

    openFeedAddBtn?.addEventListener("click", () => {
        feedAddForm?.reset();
        feedAddModal.classList.add("show");
    });

    openVitaminAddBtn?.addEventListener("click", () => {
        vitaminAddForm?.reset();
        vitaminAddModal.classList.add("show");
    });

    closeFeedAddBtn?.addEventListener("click", () => feedAddModal.classList.remove("show"));
    closeVitaminAddBtn?.addEventListener("click", () => vitaminAddModal.classList.remove("show"));

    // ADD FEED
    feedAddForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        const payload = {
            item_name: addFeedName.value,
            type: "feed",
            unit: addFeedUnit.value,
            initial_stock: Number(addFeedInitialStock.value),
            remaining_stock: Number(addFeedRemainingStock.value),
            purchase_date: addFeedPurchaseDate.value
        };

        const res = await apiRequest("/api/manager/inventory", "POST", payload);

        if (res.success) {
            const newEntry = createInventoryEntry({
                type: "feed",
                id: res.data?.id,
                itemName: payload.item_name,
                initialStock: payload.initial_stock,
                remainingStock: payload.remaining_stock,
                purchaseDate: payload.purchase_date,
                unit: payload.unit
            });

            feedList.insertAdjacentHTML("beforeend", newEntry);
            bindSelection();
            feedAddModal.classList.remove("show");
        }
    });

    // ADD VITAMIN
    vitaminAddForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        const payload = {
            item_name: addVitaminName.value,
            type: "vitamin",
            unit: addVitaminUnit.value,
            initial_stock: Number(addVitaminInitialStock.value),
            remaining_stock: Number(addVitaminRemainingStock.value),
            purchase_date: addVitaminPurchaseDate.value
        };

        const res = await apiRequest("/api/manager/inventory", "POST", payload);

        if (res.success) {
            const newEntry = createInventoryEntry({
                type: "vitamin",
                id: res.data?.id,
                itemName: payload.item_name,
                initialStock: payload.initial_stock,
                remainingStock: payload.remaining_stock,
                purchaseDate: payload.purchase_date,
                unit: payload.unit
            });

            vitaminList.insertAdjacentHTML("beforeend", newEntry);
            bindSelection();
            vitaminAddModal.classList.remove("show");
        }
    });

    // OPEN EDIT
    openFeedBtn?.addEventListener("click", () => {
        if (!selectedFeedEntry) return;

        feedInitialStock.value = selectedFeedEntry.dataset.initialStock;
        feedRemainingStock.value = selectedFeedEntry.dataset.remainingStock;
        feedPurchaseDate.value = selectedFeedEntry.dataset.purchaseDate;

        feedModal.classList.add("show");
    });

    openVitaminBtn?.addEventListener("click", () => {
        if (!selectedVitaminEntry) return;

        vitaminInitialStock.value = selectedVitaminEntry.dataset.initialStock;
        vitaminRemainingStock.value = selectedVitaminEntry.dataset.remainingStock;
        vitaminType.value = selectedVitaminEntry.dataset.itemName;
        vitaminPurchaseDate.value = selectedVitaminEntry.dataset.purchaseDate;

        vitaminModal.classList.add("show");
    });

    closeFeedBtn?.addEventListener("click", () => feedModal.classList.remove("show"));
    closeVitaminBtn?.addEventListener("click", () => vitaminModal.classList.remove("show"));

    [feedModal, vitaminModal, feedAddModal, vitaminAddModal].forEach((modal) => {
        modal?.addEventListener("click", (e) => {
            if (e.target === modal) modal.classList.remove("show");
        });
    });

    // ✅ UPDATED FEED UPDATE
    feedForm?.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!selectedFeedEntry) return;

        const id = selectedFeedEntry.dataset.id;

        const payload = {
            item_name: selectedFeedEntry.dataset.itemName,
            initial_stock: Number(feedInitialStock.value),
            remaining_stock: Number(feedRemainingStock.value),
            purchase_date: feedPurchaseDate.value
        };

        const res = await apiRequest(`/api/manager/inventory/${id}`, "PUT", payload);

        if (res.success) {
            updateInventoryEntry(selectedFeedEntry, {
                itemName: payload.item_name,
                initialStock: payload.initial_stock,
                remainingStock: payload.remaining_stock,
                purchaseDate: payload.purchase_date,
                unit: selectedFeedEntry.dataset.unit
            });

            feedModal.classList.remove("show");
        }
    });

    // ✅ UPDATED VITAMIN UPDATE
    vitaminForm?.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!selectedVitaminEntry) return;

        const id = selectedVitaminEntry.dataset.id;

        const payload = {
            item_name: vitaminType.value,
            initial_stock: Number(vitaminInitialStock.value),
            remaining_stock: Number(vitaminRemainingStock.value),
            purchase_date: vitaminPurchaseDate.value
        };

        const res = await apiRequest(`/api/manager/inventory/${id}`, "PUT", payload);

        if (res.success) {
            updateInventoryEntry(selectedVitaminEntry, {
                itemName: payload.item_name,
                initialStock: payload.initial_stock,
                remainingStock: payload.remaining_stock,
                purchaseDate: payload.purchase_date,
                unit: selectedVitaminEntry.dataset.unit
            });

            vitaminModal.classList.remove("show");
        }
    });
}

// ✅ FIXED updateInventoryEntry
function updateInventoryEntry(entry, data) {
    const unit = data.unit || "";

    const percentage = getInventoryPercentage(data.remainingStock, data.initialStock);
    const status = getInventoryStatus(percentage);

    entry.dataset.itemName = data.itemName;
    entry.dataset.initialStock = data.initialStock;
    entry.dataset.remainingStock = data.remainingStock;
    entry.dataset.purchaseDate = data.purchaseDate;
    entry.dataset.unit = unit;

    const itemNameEl = entry.querySelector(".inventory-item-name");
    if (itemNameEl) itemNameEl.textContent = data.itemName;

    const paragraphs = entry.querySelectorAll(".inventory-stock-details p");
    if (paragraphs[0]) {
        paragraphs[0].innerHTML = `<strong>Initial Stock:</strong> ${data.initialStock} ${unit}`;
    }
    if (paragraphs[1]) {
        paragraphs[1].innerHTML = `<strong>Remaining:</strong> ${data.remainingStock} ${unit}`;
    }

    const purchaseDateEl = entry.querySelector(".inventory-date-card p");
    if (purchaseDateEl) {
        purchaseDateEl.innerHTML = `<strong>Purchase Date:</strong> ${data.purchaseDate}`;
    }

    const badge = entry.querySelector(".inventory-status-badge");
    if (badge) {
        badge.textContent = status.label;
        badge.className = `inventory-status-badge ${status.className}`;
    }

    const ring = entry.querySelector(".inventory-ring");
    if (ring) {
        ring.style.setProperty("--percent", percentage);
        ring.className = `inventory-ring ${status.className}`;
    }
}

function getInventoryPercentage(remaining, initial) {
    if (!initial || initial <= 0) return 0;
    return Math.round((remaining / initial) * 100);
}

function getInventoryStatus(percentage) {
    if (percentage >= 70) return { label: "High", className: "high" };
    if (percentage >= 30) return { label: "Moderate", className: "moderate" };
    return { label: "Critical", className: "critical" };
}

function setupProfileModal() {
    const profileModal = document.getElementById("profileModal");
    const openProfileModalBtn = document.getElementById("openProfileModal");
    const closeProfileModalBtn = document.getElementById("closeProfileModal");

    openProfileModalBtn?.addEventListener("click", () => profileModal.classList.add("show"));
    closeProfileModalBtn?.addEventListener("click", () => profileModal.classList.remove("show"));

    profileModal?.addEventListener("click", (event) => {
        if (event.target === profileModal) {
            profileModal.classList.remove("show");
        }
    });
}