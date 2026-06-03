function showPopup(message, type = "success", callback = null) {
    const oldPopup = document.getElementById("inventoryPopupOverlay");
    if (oldPopup) oldPopup.remove();

    const overlay = document.createElement("div");
    overlay.id = "inventoryPopupOverlay";
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
    `;

    const box = document.createElement("div");
    box.style.cssText = `
        background: #ffffff;
        width: 360px;
        max-width: 90%;
        border-radius: 18px;
        padding: 28px;
        text-align: center;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        font-family: inherit;
    `;

    box.innerHTML = `
        <h2 style="margin:0 0 10px;color:${type === "success" ? "#1f7a3f" : "#b42318"};">
            ${type === "success" ? "Success" : "Error"}
        </h2>
        <p style="margin:0 0 22px;color:#333;font-size:15px;">
            ${message}
        </p>
        <button id="inventoryPopupOkBtn" style="
            border: none;
            background: ${type === "success" ? "#1f7a3f" : "#b42318"};
            color: white;
            padding: 10px 28px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
        ">
            OK
        </button>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    document.getElementById("inventoryPopupOkBtn").addEventListener("click", () => {
        overlay.remove();

        if (typeof callback === "function") {
            callback();
        }
    });
}

function showConfirmPopup(message, callback) {
    const oldPopup = document.getElementById("confirmPopupOverlay");
    if (oldPopup) oldPopup.remove();

    const overlay = document.createElement("div");
    overlay.id = "confirmPopupOverlay";
    overlay.style.cssText = `
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
    `;

    const box = document.createElement("div");
    box.style.cssText = `
        background: #ffffff;
        width: 380px;
        max-width: 90%;
        border-radius: 18px;
        padding: 28px;
        text-align: center;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        font-family: inherit;
    `;

    box.innerHTML = `
        <h2 style="margin:0 0 10px;color:#b42318;">
            Confirm Archive
        </h2>
        <p style="margin:0 0 22px;color:#333;font-size:15px;">
            ${message}
        </p>
        <div style="display:flex;justify-content:center;gap:12px;">
            <button id="confirmArchiveBtn" style="
                border: none;
                background: #b42318;
                color: white;
                padding: 10px 24px;
                border-radius: 999px;
                cursor: pointer;
                font-weight: 600;
            ">
                Archive
            </button>
            <button id="cancelArchiveBtn" style="
                border: none;
                background: #d1d5db;
                color: #111827;
                padding: 10px 24px;
                border-radius: 999px;
                cursor: pointer;
                font-weight: 600;
            ">
                Cancel
            </button>
        </div>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    document.getElementById("confirmArchiveBtn").addEventListener("click", () => {
        overlay.remove();

        if (typeof callback === "function") {
            callback(true);
        }
    });

    document.getElementById("cancelArchiveBtn").addEventListener("click", () => {
        overlay.remove();

        if (typeof callback === "function") {
            callback(false);
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
    setupInventoryModals();
    setupProfileModal();
});

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

                payload = {
                    stock_to_reduce: Number(feedStockQuantity.value),
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

                payload = {
                    stock_to_reduce: Number(vitaminStockQuantity.value),
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
