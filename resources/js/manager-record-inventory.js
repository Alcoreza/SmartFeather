document.addEventListener("DOMContentLoaded", () => {
    setupProfileModal();
    setupInventoryRecordTabs();
});

/* ================= PROFILE MODAL ================= */
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

    if (openProfileModalBtn && profileModal) {
        openProfileModalBtn.addEventListener("click", async () => {
            await populateProfileModal();
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

/* ================= INVENTORY RECORDS ================= */
function setupInventoryRecordTabs() {
    const feedTab = document.getElementById("feedTab");
    const vitaminsTab = document.getElementById("vitaminsTab");
    const filterWrap = document.getElementById("filterWrap");
    const recordFilter = document.getElementById("recordFilter");
    const tableHead = document.getElementById("recordTableHead");
    const tableBody = document.getElementById("recordTableBody");

    let currentType = "feed";

    /* ===== FETCH ITEMS (for dropdown) ===== */
    async function fetchItems(type) {
        try {
            const res = await fetch(`/api/inventory-items?type=${type}`);
            return await res.json();
        } catch (e) {
            console.error("Fetch items error:", e);
            return [];
        }
    }

    /* ===== POPULATE DROPDOWN ===== */
    async function populateFilter(type) {
        if (!filterWrap || !recordFilter) return;

        const items = await fetchItems(type);

        recordFilter.innerHTML = `
            <option value="">All ${type}</option>
        ` + items.map(item => `
            <option value="${item}">${item}</option>
        `).join("");

        filterWrap.classList.remove("hidden");
    }

    /* ===== FETCH RECORDS ===== */
    async function fetchRecords(type, itemName = null) {
        let url = `/api/inventory-records?type=${type}`;

        if (itemName) {
            url += `&item_name=${encodeURIComponent(itemName)}`;
        }

        try {
            const response = await fetch(url);
            return await response.json();
        } catch (error) {
            console.error("Fetch error:", error);
            return [];
        }
    }

    /* ===== RENDER TABLE ===== */
    async function renderTable() {
        const selectedItem = recordFilter.value;

        tableBody.innerHTML = `<tr><td colspan="4">Loading...</td></tr>`;

        const data = await fetchRecords(currentType, selectedItem);

        if (!data.length) {
            tableBody.innerHTML = `<tr><td colspan="4">No records found</td></tr>`;
            return;
        }

        tableBody.innerHTML = data.map(row => `
            <tr>
                <td>${row.item_name}</td>
                <td>${formatDate(row.monitoring_date)}</td>
                <td>${row.deducted ?? 0}</td>
                <td>${row.remaining_stock ?? ''}</td>
            </tr>
        `).join("");
    }

    /* ===== FEED TAB ===== */
    async function renderFeed() {
        currentType = "feed";

        // UPDATED CLASSES
        feedTab.classList.add("active", "feed-active");
        feedTab.classList.remove("vitamins-active");

        vitaminsTab.classList.remove("active", "vitamins-active", "feed-active");

        tableHead.innerHTML = `
            <tr>
                <th>Item</th>
                <th>Date</th>
                <th>Deducted (kg)</th>
                <th>Remaining (kg)</th>
            </tr>
        `;

        await populateFilter("feed");
        await renderTable();
    }

    /* ===== VITAMIN TAB ===== */
    async function renderVitamins() {
        currentType = "vitamin";

        // UPDATED CLASSES
        vitaminsTab.classList.add("active", "vitamins-active");
        vitaminsTab.classList.remove("feed-active");

        feedTab.classList.remove("active", "feed-active", "vitamins-active");

        tableHead.innerHTML = `
            <tr>
                <th>Item</th>
                <th>Date</th>
                <th>Deducted (bottles)</th>
                <th>Remaining (bottles)</th>
            </tr>
        `;

        await populateFilter("vitamin");
        await renderTable();
    }

    /* ===== EVENTS ===== */
    if (feedTab) feedTab.addEventListener("click", renderFeed);
    if (vitaminsTab) vitaminsTab.addEventListener("click", renderVitamins);
    if (recordFilter) recordFilter.addEventListener("change", renderTable);

    /* ===== DEFAULT LOAD ===== */
    renderFeed();

    /* ===== HELPERS ===== */
    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    }
}