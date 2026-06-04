document.addEventListener("DOMContentLoaded", () => {
    setupProfileModal();
    setupInventoryRecordTabs();
});

const RECORD_ROWS_PER_PAGE = 10;

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
    const transactionDateFilter = document.getElementById("transactionDateFilter");
    const initialStockDateFilter = document.getElementById("initialStockDateFilter");
    const clearRecordDateFilters = document.getElementById("clearRecordDateFilters");
    const tableHead = document.getElementById("recordTableHead");
    const tableBody = document.getElementById("recordTableBody");
    const pagination = document.querySelector("[data-record-pagination]");
    const prevButton = document.querySelector("[data-record-prev]");
    const nextButton = document.querySelector("[data-record-next]");
    const dots = document.querySelector("[data-record-dots]");

    let currentType = "feed";
    let renderVersion = 0;
    let currentPage = 0;
    let allRows = [];
    let currentRows = [];

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
        updatePagination(0);
    }

    function renderRows(rows, emptyMessage = "No records found") {
        currentRows = Array.isArray(rows) ? rows : [];
        const totalPages = Math.max(1, Math.ceil(currentRows.length / RECORD_ROWS_PER_PAGE));
        currentPage = Math.min(currentPage, totalPages - 1);

        if (!currentRows.length) {
            tableBody.innerHTML = `<tr><td colspan="6">${emptyMessage}</td></tr>`;
            updatePagination(0);
            return;
        }

        const start = currentPage * RECORD_ROWS_PER_PAGE;
        const pageRows = currentRows.slice(start, start + RECORD_ROWS_PER_PAGE);
        const placeholderRows = RECORD_ROWS_PER_PAGE - pageRows.length;

        tableBody.innerHTML = pageRows.map((row, index) => {
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

        if (placeholderRows > 0) {
            tableBody.innerHTML += Array.from({ length: placeholderRows }, () => `
                <tr class="record-placeholder-row" aria-hidden="true">
                    <td colspan="6">&nbsp;</td>
                </tr>
            `).join("");
        }

        updatePagination(currentRows.length);
    }

    function applyFiltersAndRender() {
        const filteredRows = applyDateFilters(allRows);
        const hasDateFilter = Boolean(transactionDateFilter?.value || initialStockDateFilter?.value);

        renderRows(
            filteredRows,
            hasDateFilter ? "No records match the selected dates" : "No records found"
        );
    }

    async function renderTable() {
        const version = ++renderVersion;
        const selectedItem = recordFilter.value;

        renderLoading();

        const data = await fetchRecords(currentType, selectedItem);

        if (version !== renderVersion) return;

        allRows = Array.isArray(data) ? data : [];
        applyFiltersAndRender();
    }

    /* ===== FEED TAB ===== */
    async function renderFeed() {
        currentType = "feed";
        currentPage = 0;
        const version = ++renderVersion;

        // UPDATED CLASSES
        feedTab.classList.add("active", "feed-active");
        feedTab.classList.remove("vitamins-active");

        vitaminsTab.classList.remove("active", "vitamins-active", "feed-active");

        tableHead.innerHTML = `
            <tr>
                <th>Item</th>
                <th>Transaction</th>
                <th>Quantity</th>
                <th>Transaction Date</th>
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
        resetDateFilters();
        allRows = Array.isArray(data) ? data : [];
        applyFiltersAndRender();
    }

    /* ===== VITAMIN TAB ===== */
    async function renderVitamins() {
        currentType = "vitamin";
        currentPage = 0;
        const version = ++renderVersion;

        // UPDATED CLASSES
        vitaminsTab.classList.add("active", "vitamins-active");
        vitaminsTab.classList.remove("feed-active");

        feedTab.classList.remove("active", "feed-active", "vitamins-active");

        tableHead.innerHTML = `
            <tr>
                <th>Item</th>
                <th>Transaction</th>
                <th>Quantity</th>
                <th>Transaction Date</th>
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
        resetDateFilters();
        allRows = Array.isArray(data) ? data : [];
        applyFiltersAndRender();
    }

    /* ===== EVENTS ===== */
    if (feedTab) feedTab.addEventListener("click", renderFeed);
    if (vitaminsTab) vitaminsTab.addEventListener("click", renderVitamins);
    if (recordFilter) {
        recordFilter.addEventListener("change", () => {
            currentPage = 0;
            renderTable();
        });
    }
    [transactionDateFilter, initialStockDateFilter].forEach((filter) => {
        filter?.addEventListener("change", () => {
            currentPage = 0;
            applyFiltersAndRender();
        });
    });
    clearRecordDateFilters?.addEventListener("click", () => {
        resetDateFilters();
        currentPage = 0;
        applyFiltersAndRender();
    });

    setupPagination();

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

    function applyDateFilters(rows) {
        const transactionDate = transactionDateFilter?.value || "";
        const initialStockDate = initialStockDateFilter?.value || "";

        if (!transactionDate && !initialStockDate) {
            return rows;
        }

        return rows.filter((row) => {
            const movement = getMovement(row);
            const rowTransactionDate = getDateFilterValue(movement.date);
            const rowInitialStockDate = getDateFilterValue(row.initial_purchase_date);

            return (!transactionDate || rowTransactionDate === transactionDate)
                && (!initialStockDate || rowInitialStockDate === initialStockDate);
        });
    }

    function getDateFilterValue(dateString) {
        if (!dateString) return "";

        const stringValue = String(dateString);
        const simpleDateMatch = stringValue.match(/^\d{4}-\d{2}-\d{2}/);

        if (simpleDateMatch) {
            return simpleDateMatch[0];
        }

        const parsedDate = new Date(stringValue);

        if (Number.isNaN(parsedDate.getTime())) {
            return "";
        }

        return parsedDate.toISOString().slice(0, 10);
    }

    function resetDateFilters() {
        if (transactionDateFilter) transactionDateFilter.value = "";
        if (initialStockDateFilter) initialStockDateFilter.value = "";
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function updatePagination(totalRows) {
        const totalPages = Math.max(1, Math.ceil(totalRows / RECORD_ROWS_PER_PAGE));

        if (pagination) {
            pagination.classList.toggle("is-hidden", totalRows <= RECORD_ROWS_PER_PAGE);
        }

        if (prevButton) {
            prevButton.disabled = currentPage === 0;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= totalPages - 1;
        }

        if (dots) {
            dots.innerHTML = Array.from({ length: totalPages }, (_, index) => `
                <button
                    type="button"
                    class="record-page-dot ${index === currentPage ? "active" : ""}"
                    data-record-page="${index}"
                    aria-label="Go to page ${index + 1}"
                    aria-current="${index === currentPage ? "page" : "false"}"
                ></button>
            `).join("");
        }
    }

    function renderCurrentPage() {
        renderRows(currentRows);
    }

    function setupPagination() {
        prevButton?.addEventListener("click", () => {
            currentPage = Math.max(0, currentPage - 1);
            renderCurrentPage();
        });

        nextButton?.addEventListener("click", () => {
            const totalPages = Math.max(1, Math.ceil(currentRows.length / RECORD_ROWS_PER_PAGE));
            currentPage = Math.min(totalPages - 1, currentPage + 1);
            renderCurrentPage();
        });

        dots?.addEventListener("click", (event) => {
            const dot = event.target.closest("[data-record-page]");
            if (!dot) return;

            currentPage = Number(dot.dataset.recordPage || 0);
            renderCurrentPage();
        });
    }
}
