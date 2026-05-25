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

    const feedInitialStock = document.getElementById("feedInitialStock");
    const feedRemainingStock = document.getElementById("feedRemainingStock");
    const feedCriticalStock = document.getElementById("feedCriticalStock");
    const feedPurchaseDate = document.getElementById("feedPurchaseDate");
    const feedEditSelect = document.getElementById("feedEditSelect");

    const vitaminInitialStock = document.getElementById("vitaminInitialStock");
    const vitaminRemainingStock = document.getElementById("vitaminRemainingStock");
    const vitaminCriticalStock = document.getElementById("vitaminCriticalStock");
    const vitaminPurchaseDate = document.getElementById("vitaminPurchaseDate");
    const vitaminEditSelect = document.getElementById("vitaminEditSelect");

    const addFeedName = document.getElementById("addFeedName");
    const addFeedUnit = document.getElementById("addFeedUnit");
    const addFeedInitialStock = document.getElementById("addFeedInitialStock");
    const addFeedRemainingStock = document.getElementById("addFeedRemainingStock");
    const addFeedCriticalStock = document.getElementById("addFeedCriticalStock");
    const addFeedPurchaseDate = document.getElementById("addFeedPurchaseDate");

    const addVitaminName = document.getElementById("addVitaminName");
    const addVitaminUnit = document.getElementById("addVitaminUnit");
    const addVitaminInitialStock = document.getElementById("addVitaminInitialStock");
    const addVitaminRemainingStock = document.getElementById("addVitaminRemainingStock");
    const addVitaminCriticalStock = document.getElementById("addVitaminCriticalStock");
    const addVitaminPurchaseDate = document.getElementById("addVitaminPurchaseDate");

    const feedForm = document.getElementById("feedEditForm");
    const vitaminForm = document.getElementById("vitaminEditForm");
    const feedAddForm = document.getElementById("feedAddForm");
    const vitaminAddForm = document.getElementById("vitaminAddForm");

    const feedList = document.getElementById("feedInventoryList");
    const vitaminList = document.getElementById("vitaminInventoryList");

    let selectedFeedEntry = null;
    let selectedVitaminEntry = null;

    function createInventoryEntry({ type, id, itemName, initialStock, remainingStock, critical, purchaseDate, unit }) {
        const percentage = getInventoryPercentage(remainingStock, initialStock);
        const status = getInventoryStatus(remainingStock, critical, percentage);

        const commonAttributes = `
            data-id="${id}"
            data-item-name="${itemName}"
            data-initial-stock="${initialStock}"
            data-remaining-stock="${remainingStock}"
            data-critical="${critical}"
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
                                <p><strong>Critical Level:</strong> ${critical} ${unit}</p>
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
                            <p><strong>Critical Level:</strong> ${critical} ${unit}</p>
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

    function getEntriesByType(type) {
        if (type === "feed") {
            return Array.from(document.querySelectorAll(".inventory-feed-entry"));
        }

        return Array.from(document.querySelectorAll(".inventory-vitamin-entry"));
    }

    function populateDropdown(type) {
        const select = type === "feed" ? feedEditSelect : vitaminEditSelect;
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

    function fillFeedForm(entry) {
        if (!entry) return;

        selectedFeedEntry = entry;
        if (feedInitialStock) feedInitialStock.value = entry.dataset.initialStock;
        if (feedRemainingStock) feedRemainingStock.value = entry.dataset.remainingStock;
        if (feedCriticalStock) feedCriticalStock.value = entry.dataset.critical;
        if (feedPurchaseDate) feedPurchaseDate.value = entry.dataset.purchaseDate;
    }

    function fillVitaminForm(entry) {
        if (!entry) return;

        selectedVitaminEntry = entry;
        if (vitaminInitialStock) vitaminInitialStock.value = entry.dataset.initialStock;
        if (vitaminRemainingStock) vitaminRemainingStock.value = entry.dataset.remainingStock;
        if (vitaminCriticalStock) vitaminCriticalStock.value = entry.dataset.critical;
        if (vitaminPurchaseDate) vitaminPurchaseDate.value = entry.dataset.purchaseDate;
    }

    openFeedBtn?.addEventListener("click", () => {
        populateDropdown("feed");
        if (feedEditSelect) {
            feedEditSelect.value = "";
        }
        if (feedInitialStock) feedInitialStock.value = "";
        if (feedRemainingStock) feedRemainingStock.value = "";
        if (feedCriticalStock) feedCriticalStock.value = "";
        if (feedPurchaseDate) feedPurchaseDate.value = "";

        selectedFeedEntry = null;
        feedModal.classList.add("show");
    });

    openVitaminBtn?.addEventListener("click", () => {
        populateDropdown("vitamin");
        if (vitaminEditSelect) {
            vitaminEditSelect.value = "";
        }
        if (vitaminInitialStock) vitaminInitialStock.value = "";
        if (vitaminRemainingStock) vitaminRemainingStock.value = "";
        if (vitaminCriticalStock) vitaminCriticalStock.value = "";
        if (vitaminPurchaseDate) vitaminPurchaseDate.value = "";

        selectedVitaminEntry = null;
        vitaminModal.classList.add("show");
    });

    feedEditSelect?.addEventListener("change", () => {
        const selectedId = feedEditSelect.value;
        const entry = document.querySelector(`.inventory-feed-entry[data-id="${selectedId}"]`);
        fillFeedForm(entry);
    });

    vitaminEditSelect?.addEventListener("change", () => {
        const selectedId = vitaminEditSelect.value;
        const entry = document.querySelector(`.inventory-vitamin-entry[data-id="${selectedId}"]`);
        fillVitaminForm(entry);
    });

    openFeedAddBtn?.addEventListener("click", () => {
        feedAddForm?.reset();
        if (addFeedUnit) addFeedUnit.value = "";
        feedAddModal.classList.add("show");
    });

    openVitaminAddBtn?.addEventListener("click", () => {
        vitaminAddForm?.reset();
        if (addVitaminUnit) addVitaminUnit.value = "bottles";
        vitaminAddModal.classList.add("show");
    });

    closeFeedBtn?.addEventListener("click", () => feedModal.classList.remove("show"));
    closeVitaminBtn?.addEventListener("click", () => vitaminModal.classList.remove("show"));
    closeFeedAddBtn?.addEventListener("click", () => feedAddModal.classList.remove("show"));
    closeVitaminAddBtn?.addEventListener("click", () => vitaminAddModal.classList.remove("show"));

    [feedModal, vitaminModal, feedAddModal, vitaminAddModal].forEach((modal) => {
        modal?.addEventListener("click", (e) => {
            if (e.target === modal) modal.classList.remove("show");
        });
    });

    feedAddForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        const selectedFeedName = addFeedName.value.trim();
        const addedStock = Number(addFeedInitialStock.value);
        const currentRemaining = Number(addFeedRemainingStock.value || 0);

        const payload = {
            item_name: selectedFeedName,
            type: "feed",
            unit: addFeedUnit.value.trim() || "kg",
            initial_stock: addedStock,
            remaining_stock: currentRemaining,
            critical: Number(addFeedCriticalStock.value),
            purchase_date: addFeedPurchaseDate.value
        };

        const res = await apiRequest("/api/manager/inventory", "POST", payload);

        if (res.success) {
            const item = res.item;

            const existingEntry = getExistingFeedEntryByName(item.item_name);

            if (existingEntry) {
                updateInventoryEntry(existingEntry, {
                    itemName: item.item_name,
                    initialStock: item.initial_stock,
                    remainingStock: item.remaining_stock,
                    critical: item.critical,
                    purchaseDate: item.purchase_date,
                    unit: item.unit
                });
            } else {
                const newEntry = createInventoryEntry({
                    type: "feed",
                    id: item.id,
                    itemName: item.item_name,
                    initialStock: item.initial_stock,
                    remainingStock: item.remaining_stock,
                    critical: item.critical,
                    purchaseDate: item.purchase_date,
                    unit: item.unit
                });

                feedList.insertAdjacentHTML("beforeend", newEntry);
            }

            populateDropdown("feed");
            feedAddForm.reset();
            addFeedRemainingStock.value = "";
            addFeedUnit.value = "kg";
            feedAddModal.classList.remove("show");
        } else {
            alert(res.message || "Failed to save feed stock.");
        }
    });

    vitaminAddForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        const selectedVitaminName = addVitaminName.value.trim();
        const addedStock = Number(addVitaminInitialStock.value);
        const currentRemaining = Number(addVitaminRemainingStock.value || 0);

        const payload = {
            item_name: selectedVitaminName,
            type: "vitamin",
            unit: addVitaminUnit.value.trim() || "bottle",
            initial_stock: addedStock,
            remaining_stock: currentRemaining,
            critical: Number(addVitaminCriticalStock.value),
            purchase_date: addVitaminPurchaseDate.value
        };

        const res = await apiRequest("/api/manager/inventory", "POST", payload);

        if (res.success) {
            const item = res.item;

            const existingEntry = Array.from(document.querySelectorAll(".inventory-vitamin-entry"))
                .find(entry =>
                    entry.dataset.itemName?.trim().toLowerCase() === item.item_name.trim().toLowerCase()
                );

            if (existingEntry) {
                updateInventoryEntry(existingEntry, {
                    itemName: item.item_name,
                    initialStock: item.initial_stock,
                    remainingStock: item.remaining_stock,
                    critical: item.critical,
                    purchaseDate: item.purchase_date,
                    unit: item.unit
                });
            } else {
                const newEntry = createInventoryEntry({
                    type: "vitamin",
                    id: item.id,
                    itemName: item.item_name,
                    initialStock: item.initial_stock,
                    remainingStock: item.remaining_stock,
                    critical: item.critical,
                    purchaseDate: item.purchase_date,
                    unit: item.unit
                });

                vitaminList.insertAdjacentHTML("beforeend", newEntry);
            }

            populateDropdown("vitamin");
            vitaminAddForm.reset();
            addVitaminRemainingStock.value = "";
            addVitaminUnit.value = "bottle";
            vitaminAddModal.classList.remove("show");
        } else {
            alert(res.message || "Failed to save vitamin stock.");
        }
    });

    feedForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!selectedFeedEntry) return;

        const id = selectedFeedEntry.dataset.id;

        const payload = {
            item_name: selectedFeedEntry.dataset.itemName,
            initial_stock: Number(feedInitialStock.value),
            remaining_stock: Number(feedRemainingStock.value),
            critical: Number(feedCriticalStock.value),
            purchase_date: feedPurchaseDate.value
        };

        const res = await apiRequest(`/api/manager/inventory/${id}`, "PUT", payload);

        if (res.success) {
            updateInventoryEntry(selectedFeedEntry, {
                itemName: selectedFeedEntry.dataset.itemName,
                initialStock: payload.initial_stock,
                remainingStock: payload.remaining_stock,
                critical: payload.critical,
                purchaseDate: payload.purchase_date,
                unit: selectedFeedEntry.dataset.unit
            });

            populateDropdown("feed");
            feedModal.classList.remove("show");
        }
    });

    vitaminForm?.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (!selectedVitaminEntry) return;

        const id = selectedVitaminEntry.dataset.id;

        const payload = {
            item_name: selectedVitaminEntry.dataset.itemName,
            initial_stock: Number(vitaminInitialStock.value),
            remaining_stock: Number(vitaminRemainingStock.value),
            critical: Number(vitaminCriticalStock.value),
            purchase_date: vitaminPurchaseDate.value
        };

        const res = await apiRequest(`/api/manager/inventory/${id}`, "PUT", payload);

        if (res.success) {
            updateInventoryEntry(selectedVitaminEntry, {
                itemName: selectedVitaminEntry.dataset.itemName,
                initialStock: payload.initial_stock,
                remainingStock: payload.remaining_stock,
                critical: payload.critical,
                purchaseDate: payload.purchase_date,
                unit: "bottles"
            });

            populateDropdown("vitamin");
            vitaminModal.classList.remove("show");
        }
    });
}

function updateInventoryEntry(entry, data) {
    const unit = data.unit || "";

    const percentage = getInventoryPercentage(data.remainingStock, data.initialStock);
    const status = getInventoryStatus(data.remainingStock, data.critical, percentage);

    entry.dataset.itemName = data.itemName;
    entry.dataset.initialStock = data.initialStock;
    entry.dataset.remainingStock = data.remainingStock;
    entry.dataset.critical = data.critical;
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
    if (paragraphs[2]) {
        paragraphs[2].innerHTML = `<strong>Critical Level:</strong> ${data.critical} ${unit}`;
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

function getInventoryStatus(remaining, critical, percentage) {
    if (Number(remaining) <= Number(critical)) {
        return { label: "Critical", className: "critical" };
    }

    if (percentage >= 70) {
        return { label: "High", className: "high" };
    }

    return { label: "Moderate", className: "moderate" };
}

async function populateProfileModal() {
    try {
        const response = await fetch('/api/user');
        if (!response.ok) throw new Error('Failed to fetch user info');
        const user = await response.json();
        document.getElementById('profileFirstName').value = user.FirstName || '';
        document.getElementById('profileMiddleName').value = user.MiddleName || '';
        document.getElementById('profileLastName').value = user.LastName || '';
        document.getElementById('profileSuffix').value = user.Suffix || '';
        document.getElementById('profileRole').value = user.Role || '';
        document.getElementById('profilePhone').value = user.PhoneNumber || '';
        document.getElementById('profileId').value = user.EmployeeId || '';
        document.getElementById('profileBirthday').value = user.Birthday || '';
        document.getElementById('profileGender').value = user.Gender || '';
        document.getElementById('profileAddress').value = user.Address || '';
    } catch (e) {
        // Optionally show error
    }
}

function setupProfileModal() {
    const profileModal = document.getElementById("profileModal");
    const openProfileModalBtn = document.getElementById("openProfileModal");
    const closeProfileModalBtn = document.getElementById("closeProfileModal");

    openProfileModalBtn?.addEventListener("click", async () => {
        await populateProfileModal();
        profileModal.classList.add("show");
    });
    closeProfileModalBtn?.addEventListener("click", () => profileModal.classList.remove("show"));

    profileModal?.addEventListener("click", (event) => {
        if (event.target === profileModal) {
            profileModal.classList.remove("show");
        }
    });
}

    function getExistingFeedEntryByName(feedName) {
        return Array.from(document.querySelectorAll(".inventory-feed-entry"))
            .find(entry =>
                entry.dataset.itemName?.trim().toLowerCase() === feedName.trim().toLowerCase()
            );
    }

    if (addFeedRemainingStock) {
        addFeedRemainingStock.readOnly = true;
    }

    addFeedName?.addEventListener("change", () => {
        const selectedFeedName = addFeedName.value.trim();
        const existingFeed = getExistingFeedEntryByName(selectedFeedName);

        if (existingFeed) {
            addFeedRemainingStock.value = existingFeed.dataset.remainingStock || 0;
            addFeedUnit.value = existingFeed.dataset.unit || "kg";
        } else {
            addFeedRemainingStock.value = 0;
            addFeedUnit.value = "kg";
        }
    });

    function getExistingVitaminEntryByName(vitaminName) {
        return Array.from(document.querySelectorAll(".inventory-vitamin-entry"))
            .find(entry =>
                entry.dataset.itemName?.trim().toLowerCase() === vitaminName.trim().toLowerCase()
            );
    }

    if (addVitaminRemainingStock) {
        addVitaminRemainingStock.readOnly = true;
    }

    addVitaminName?.addEventListener("change", () => {
        const selectedVitaminName = addVitaminName.value.trim();
        const existingVitamin = getExistingVitaminEntryByName(selectedVitaminName);

        if (existingVitamin) {
            addVitaminRemainingStock.value = existingVitamin.dataset.remainingStock || 0;
            addVitaminUnit.value = existingVitamin.dataset.unit || "bottle";
        } else {
            addVitaminRemainingStock.value = 0;
            addVitaminUnit.value = "bottle";
        }
    });