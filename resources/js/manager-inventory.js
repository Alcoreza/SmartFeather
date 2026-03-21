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

    function createInventoryEntry({ type, itemName, initialStock, remainingStock, purchaseDate, unit }) {
        const percentage = getInventoryPercentage(remainingStock, initialStock);
        const status = getInventoryStatus(percentage);

        if (type === "feed") {
            return `
                <div class="inventory-entry inventory-feed-entry"
                    data-item-name="${itemName}"
                    data-initial-stock="${initialStock}"
                    data-remaining-stock="${remainingStock}"
                    data-purchase-date="${purchaseDate}"
                    data-unit="${unit}">
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
            <div class="inventory-entry inventory-vitamin-entry"
                data-item-name="${itemName}"
                data-initial-stock="${initialStock}"
                data-remaining-stock="${remainingStock}"
                data-purchase-date="${purchaseDate}"
                data-unit="${unit}">
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

    if (openFeedAddBtn && feedAddModal) {
        openFeedAddBtn.addEventListener("click", () => {
            feedAddForm?.reset();
            feedAddModal.classList.add("show");
        });
    }

    if (openVitaminAddBtn && vitaminAddModal) {
        openVitaminAddBtn.addEventListener("click", () => {
            vitaminAddForm?.reset();
            vitaminAddModal.classList.add("show");
        });
    }

    if (closeFeedAddBtn && feedAddModal) {
        closeFeedAddBtn.addEventListener("click", () => {
            feedAddModal.classList.remove("show");
        });
    }

    if (closeVitaminAddBtn && vitaminAddModal) {
        closeVitaminAddBtn.addEventListener("click", () => {
            vitaminAddModal.classList.remove("show");
        });
    }

    if (feedAddForm && feedAddModal) {
        feedAddForm.addEventListener("submit", (event) => {
            event.preventDefault();

            const itemName = addFeedName.value || "Feed Stock";
            const unit = addFeedUnit.value || "kg";
            const initialStock = Number(addFeedInitialStock.value) || 0;
            const remainingStock = Number(addFeedRemainingStock.value) || 0;
            const purchaseDate = addFeedPurchaseDate.value || "";

            const newEntry = createInventoryEntry({
                type: "feed",
                itemName,
                initialStock,
                remainingStock,
                purchaseDate,
                unit
            });

            if (feedList) {
                feedList.insertAdjacentHTML("beforeend", newEntry);
                bindSelection();
            }

            feedAddModal.classList.remove("show");
        });
    }

    if (vitaminAddForm && vitaminAddModal) {
        vitaminAddForm.addEventListener("submit", (event) => {
            event.preventDefault();

            const itemName = addVitaminName.value || "Vitamin Stock";
            const unit = addVitaminUnit.value || "mL";
            const initialStock = Number(addVitaminInitialStock.value) || 0;
            const remainingStock = Number(addVitaminRemainingStock.value) || 0;
            const purchaseDate = addVitaminPurchaseDate.value || "";

            const newEntry = createInventoryEntry({
                type: "vitamin",
                itemName,
                initialStock,
                remainingStock,
                purchaseDate,
                unit
            });

            if (vitaminList) {
                vitaminList.insertAdjacentHTML("beforeend", newEntry);
                bindSelection();
            }

            vitaminAddModal.classList.remove("show");
        });
    }

    if (openFeedBtn && feedModal) {
        openFeedBtn.addEventListener("click", () => {
            if (!selectedFeedEntry) return;

            feedInitialStock.value = selectedFeedEntry.dataset.initialStock || "";
            feedRemainingStock.value = selectedFeedEntry.dataset.remainingStock || "";
            feedPurchaseDate.value = selectedFeedEntry.dataset.purchaseDate || "";

            feedModal.classList.add("show");
        });
    }

    if (openVitaminBtn && vitaminModal) {
        openVitaminBtn.addEventListener("click", () => {
            if (!selectedVitaminEntry) return;

            vitaminInitialStock.value = selectedVitaminEntry.dataset.initialStock || "";
            vitaminRemainingStock.value = selectedVitaminEntry.dataset.remainingStock || "";
            vitaminType.value = selectedVitaminEntry.dataset.itemName || "";
            vitaminPurchaseDate.value = selectedVitaminEntry.dataset.purchaseDate || "";

            vitaminModal.classList.add("show");
        });
    }

    if (closeFeedBtn && feedModal) {
        closeFeedBtn.addEventListener("click", () => {
            feedModal.classList.remove("show");
        });
    }

    if (closeVitaminBtn && vitaminModal) {
        closeVitaminBtn.addEventListener("click", () => {
            vitaminModal.classList.remove("show");
        });
    }

    [feedModal, vitaminModal, feedAddModal, vitaminAddModal].forEach((modal) => {
        if (!modal) return;

        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.classList.remove("show");
            }
        });
    });

    if (feedForm && feedModal) {
        feedForm.addEventListener("submit", (event) => {
            event.preventDefault();
            if (!selectedFeedEntry) return;

            const initial = Number(feedInitialStock.value) || 0;
            const remaining = Number(feedRemainingStock.value) || 0;
            const purchaseDate = feedPurchaseDate.value || "";

            updateInventoryEntry(selectedFeedEntry, {
                itemName: selectedFeedEntry.dataset.itemName || "Feed Stock",
                initialStock: initial,
                remainingStock: remaining,
                purchaseDate: purchaseDate,
                unit: selectedFeedEntry.dataset.unit || "",
            });

            feedModal.classList.remove("show");
        });
    }

    if (vitaminForm && vitaminModal) {
        vitaminForm.addEventListener("submit", (event) => {
            event.preventDefault();
            if (!selectedVitaminEntry) return;

            const initial = Number(vitaminInitialStock.value) || 0;
            const remaining = Number(vitaminRemainingStock.value) || 0;
            const itemName = vitaminType.value || "";
            const purchaseDate = vitaminPurchaseDate.value || "";

            updateInventoryEntry(selectedVitaminEntry, {
                itemName: itemName,
                initialStock: initial,
                remainingStock: remaining,
                purchaseDate: purchaseDate,
                unit: selectedVitaminEntry.dataset.unit || "",
            });

            vitaminModal.classList.remove("show");
        });
    }
}

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
    if (itemNameEl) {
        itemNameEl.textContent = data.itemName;
    }

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
        badge.classList.remove("high", "moderate", "critical");
        badge.classList.add(status.className);
    }

    const ring = entry.querySelector(".inventory-ring");
    if (ring) {
        ring.style.setProperty("--percent", percentage);
        ring.classList.remove("high", "moderate", "critical");
        ring.classList.add(status.className);
    }
}

function getInventoryPercentage(remaining, initial) {
    if (!initial || initial <= 0) return 0;
    return Math.round((remaining / initial) * 100);
}

function getInventoryStatus(percentage) {
    if (percentage >= 70) {
        return { label: "High", className: "high" };
    }

    if (percentage >= 30) {
        return { label: "Moderate", className: "moderate" };
    }

    return { label: "Critical", className: "critical" };
}

function setupProfileModal() {
    const profileModal = document.getElementById("profileModal");
    const openProfileModalBtn = document.getElementById("openProfileModal");
    const closeProfileModalBtn = document.getElementById("closeProfileModal");

    if (openProfileModalBtn && profileModal) {
        openProfileModalBtn.addEventListener("click", () => {
            profileModal.classList.add("show");
        });
    }

    if (closeProfileModalBtn && profileModal) {
        closeProfileModalBtn.addEventListener("click", () => {
            profileModal.classList.remove("show");
        });
    }

    if (profileModal) {
        profileModal.addEventListener("click", (event) => {
            if (event.target === profileModal) {
                profileModal.classList.remove("show");
            }
        });
    }
}