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

    const feedEditSelect = document.getElementById("feedEditSelect");
    const vitaminEditSelect = document.getElementById("vitaminEditSelect");

    const feedInventoryId = document.getElementById("feedInventoryId");
    const vitaminInventoryId = document.getElementById("vitaminInventoryId");

    const feedStockToReduce = document.getElementById("feedStockToReduce");
    const vitaminStockToReduce = document.getElementById("vitaminStockToReduce");

    const feedCurrentRemaining = document.getElementById("feedCurrentRemaining");
    const vitaminCurrentRemaining = document.getElementById("vitaminCurrentRemaining");

    const addFeedName = document.getElementById("addFeedName");
    const addVitaminName = document.getElementById("addVitaminName");

    const addFeedUnit = document.getElementById("addFeedUnit");
    const addVitaminUnit = document.getElementById("addVitaminUnit");

    const addFeedStockToAdd = document.getElementById("addFeedStockToAdd");
    const addVitaminStockToAdd = document.getElementById("addVitaminStockToAdd");

    const addFeedPurchaseDate = document.getElementById("addFeedPurchaseDate");
    const addVitaminPurchaseDate = document.getElementById("addVitaminPurchaseDate");

    const feedForm = document.getElementById("feedEditForm");
    const vitaminForm = document.getElementById("vitaminEditForm");
    const feedAddForm = document.getElementById("feedAddForm");
    const vitaminAddForm = document.getElementById("vitaminAddForm");

    let selectedFeedEntry = null;
    let selectedVitaminEntry = null;

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

        if (feedInventoryId) {
            feedInventoryId.value = entry.dataset.id;
        }

        if (feedCurrentRemaining) {
            feedCurrentRemaining.value = entry.dataset.remainingStock;
        }

        if (feedStockToReduce) {
            feedStockToReduce.value = "";
        }
    }

    function fillVitaminForm(entry) {
        if (!entry) return;

        selectedVitaminEntry = entry;

        if (vitaminInventoryId) {
            vitaminInventoryId.value = entry.dataset.id;
        }

        if (vitaminCurrentRemaining) {
            vitaminCurrentRemaining.value = entry.dataset.remainingStock;
        }

        if (vitaminStockToReduce) {
            vitaminStockToReduce.value = "";
        }
    }

    openFeedBtn?.addEventListener("click", () => {
        populateDropdown("feed");

        if (feedEditSelect) feedEditSelect.value = "";
        if (feedInventoryId) feedInventoryId.value = "";
        if (feedStockToReduce) feedStockToReduce.value = "";
        if (feedCurrentRemaining) feedCurrentRemaining.value = "";

        selectedFeedEntry = null;
        feedModal?.classList.add("show");
    });

    openVitaminBtn?.addEventListener("click", () => {
        populateDropdown("vitamin");

        if (vitaminEditSelect) vitaminEditSelect.value = "";
        if (vitaminInventoryId) vitaminInventoryId.value = "";
        if (vitaminStockToReduce) vitaminStockToReduce.value = "";
        if (vitaminCurrentRemaining) vitaminCurrentRemaining.value = "";

        selectedVitaminEntry = null;
        vitaminModal?.classList.add("show");
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

        if (addFeedUnit) {
            addFeedUnit.value = "kg";
        }

        feedAddModal?.classList.add("show");
    });

    openVitaminAddBtn?.addEventListener("click", () => {
        vitaminAddForm?.reset();

        if (addVitaminUnit) {
            addVitaminUnit.value = "bottle";
        }

        vitaminAddModal?.classList.add("show");
    });

    closeFeedBtn?.addEventListener("click", () => feedModal?.classList.remove("show"));
    closeVitaminBtn?.addEventListener("click", () => vitaminModal?.classList.remove("show"));
    closeFeedAddBtn?.addEventListener("click", () => feedAddModal?.classList.remove("show"));
    closeVitaminAddBtn?.addEventListener("click", () => vitaminAddModal?.classList.remove("show"));

    [feedModal, vitaminModal, feedAddModal, vitaminAddModal].forEach((modal) => {
        modal?.addEventListener("click", (e) => {
            if (e.target === modal) {
                modal.classList.remove("show");
            }
        });
    });

    feedAddForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        try {
            const payload = {
                item_name: addFeedName.value.trim(),
                type: "feed",
                stock_to_add: Number(addFeedStockToAdd.value),
                purchase_date: addFeedPurchaseDate.value
            };

            const res = await apiRequest("/api/manager/inventory", "POST", payload);

            if (res.success) {
                showPopup(res.message || "Feed stock added successfully.", "success", () => {
                    location.reload();
                });
            } else {
                showPopup(res.error || res.message || "Failed to save feed stock.", "error");
            }
        } catch (error) {
            console.error(error);
            showPopup("Something went wrong while saving feed stock.", "error");
        }
    });

    vitaminAddForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        try {
            const payload = {
                item_name: addVitaminName.value.trim(),
                type: "vitamin",
                stock_to_add: Number(addVitaminStockToAdd.value),
                purchase_date: addVitaminPurchaseDate.value
            };

            const res = await apiRequest("/api/manager/inventory", "POST", payload);

            if (res.success) {
                showPopup(res.message || "Vitamin stock added successfully.", "success", () => {
                    location.reload();
                });
            } else {
                showPopup(res.error || res.message || "Failed to save vitamin stock.", "error");
            }
        } catch (error) {
            console.error(error);
            showPopup("Something went wrong while saving vitamin stock.", "error");
        }
    });

    feedForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!selectedFeedEntry) {
            showPopup("Please select a feed item.", "error");
            return;
        }

        try {
            const id = selectedFeedEntry.dataset.id;

            const payload = {
                stock_to_reduce: Number(feedStockToReduce.value)
            };

            const res = await apiRequest(`/api/manager/inventory/${id}`, "PUT", payload);

            if (res.success) {
                showPopup(res.message || "Feed stock reduced successfully.", "success", () => {
                    location.reload();
                });
            } else {
                showPopup(res.error || res.message || "Failed to reduce feed stock.", "error");
            }
        } catch (error) {
            console.error(error);
            showPopup("Something went wrong while updating feed stock.", "error");
        }
    });

    vitaminForm?.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!selectedVitaminEntry) {
            showPopup("Please select a vitamin item.", "error");
            return;
        }

        try {
            const id = selectedVitaminEntry.dataset.id;

            const payload = {
                stock_to_reduce: Number(vitaminStockToReduce.value)
            };

            const res = await apiRequest(`/api/manager/inventory/${id}`, "PUT", payload);

            if (res.success) {
                showPopup(res.message || "Vitamin stock reduced successfully.", "success", () => {
                    location.reload();
                });
            } else {
                showPopup(res.error || res.message || "Failed to reduce vitamin stock.", "error");
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