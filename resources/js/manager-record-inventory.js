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
    let renderVersion = 0;

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
    async function populateFilter(type, providedItems = null) {
        if (!filterWrap || !recordFilter) return;

        const items = providedItems || await fetchItems(type);

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
    function renderLoading() {
        tableBody.innerHTML = `<tr><td colspan="6">Loading...</td></tr>`;
    }

    function renderRows(data) {
        if (!data.length) {
            tableBody.innerHTML = `<tr><td colspan="6">No records found</td></tr>`;
            return;
        }

        tableBody.innerHTML = data.map((row, index) => {
            const movement = getMovement(row);

            return `
                <tr style="--row-delay: ${Math.min(index * 0.055, 0.55)}s;">
                    <td>${escapeHtml(row.item_name)}</td>
                    <td>
                        <span class="record-movement-badge ${movement.className}">
                            ${movement.label}
                        </span>
                    </td>
                    <td class="record-quantity ${movement.className}">
                        ${movement.quantity}
                    </td>
                    <td>${formatDate(movement.date)}</td>
                    <td>${formatDate(row.initial_purchase_date)}</td>
                    <td>${formatNumber(row.remaining_stock)}</td>
                </tr>
            `;
        }).join("");
    }

    async function renderTable() {
        const version = ++renderVersion;
        const selectedItem = recordFilter.value;

        renderLoading();

        const data = await fetchRecords(currentType, selectedItem);

        if (version !== renderVersion) return;

        renderRows(data);
    }

    /* ===== FEED TAB ===== */
    async function renderFeed() {
        currentType = "feed";
        const version = ++renderVersion;

        // UPDATED CLASSES
        feedTab.classList.add("active", "feed-active");
        feedTab.classList.remove("vitamins-active");

        vitaminsTab.classList.remove("active", "vitamins-active", "feed-active");

        tableHead.innerHTML = `
            <tr>
                <th>Item</th>
                <th>Movement</th>
                <th>Quantity</th>
                <th>Movement Date</th>
                <th>Initial Purchase Date</th>
                <th>Remaining (kg)</th>
            </tr>
        `;

        renderLoading();

        const [items, data] = await Promise.all([
            fetchItems("feed"),
            fetchRecords("feed"),
        ]);

        if (version !== renderVersion) return;

        await populateFilter("feed", items);
        renderRows(data);
    }

    /* ===== VITAMIN TAB ===== */
    async function renderVitamins() {
        currentType = "vitamin";
        const version = ++renderVersion;

        // UPDATED CLASSES
        vitaminsTab.classList.add("active", "vitamins-active");
        vitaminsTab.classList.remove("feed-active");

        feedTab.classList.remove("active", "feed-active", "vitamins-active");

        tableHead.innerHTML = `
            <tr>
                <th>Item</th>
                <th>Movement</th>
                <th>Quantity</th>
                <th>Movement Date</th>
                <th>Initial Purchase Date</th>
                <th>Remaining (bottles)</th>
            </tr>
        `;

        renderLoading();

        const [items, data] = await Promise.all([
            fetchItems("vitamin"),
            fetchRecords("vitamin"),
        ]);

        if (version !== renderVersion) return;

        await populateFilter("vitamin", items);
        renderRows(data);
    }

    /* ===== EVENTS ===== */
    if (feedTab) feedTab.addEventListener("click", renderFeed);
    if (vitaminsTab) vitaminsTab.addEventListener("click", renderVitamins);
    if (recordFilter) recordFilter.addEventListener("change", renderTable);

    /* ===== DEFAULT LOAD ===== */
    renderFeed();

    /* ===== HELPERS ===== */
    function formatDate(dateString) {
        if (!dateString) return "--";
        return new Date(dateString).toLocaleDateString();
    }

    function formatNumber(value) {
        const number = Number(value ?? 0);

        return Number.isInteger(number)
            ? number.toString()
            : number.toFixed(2);
    }

    function getMovement(row) {
        const added = Number(row.added ?? 0);
        const deducted = Number(row.deducted ?? 0);

        if (added > 0) {
            return {
                label: "Added Stock",
                className: "movement-added",
                quantity: `+${formatNumber(added)}`,
                date: row.recent_purchase_date,
            };
        }

        if (deducted > 0) {
            return {
                label: "Reduced Stock",
                className: "movement-reduced",
                quantity: `-${formatNumber(deducted)}`,
                date: row.reduced_date,
            };
        }

        return {
            label: "Initial Stock",
            className: "movement-initial",
            quantity: formatNumber(row.initial_stock),
            date: row.initial_purchase_date || row.monitoring_date,
        };
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}
