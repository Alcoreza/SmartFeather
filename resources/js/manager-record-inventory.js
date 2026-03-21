document.addEventListener("DOMContentLoaded", () => {
    setupProfileModal();
    setupInventoryRecordTabs();
});

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

function setupInventoryRecordTabs() {
    const feedTab = document.getElementById("feedTab");
    const vitaminsTab = document.getElementById("vitaminsTab");
    const vitaminFilterWrap = document.getElementById("vitaminFilterWrap");
    const vitaminRecordFilter = document.getElementById("vitaminRecordFilter");
    const tableHead = document.getElementById("recordTableHead");
    const tableBody = document.getElementById("recordTableBody");

    if (!feedTab || !vitaminsTab || !tableHead || !tableBody) return;

    const feedRecords = [
        ["2025-07", "07-5-25", "5000", "4295"],
        ["2025-07", "07-4-25", "5000", "4400"],
        ["2025-07", "07-3-25", "5000", "4625"],
    ];

    const vitaminRecords = {
        "Vitamin E": [
            ["2025-07", "07-5-25", "Vitamin E", "1000", "792"],
            ["2025-07", "07-4-25", "Vitamin E", "1000", "924"],
            ["2025-07", "07-3-25", "Vitamin E", "1000", "985"],
        ],
        "Vitamin D3": [
            ["2025-07", "07-5-25", "Vitamin D3", "1000", "610"],
            ["2025-07", "07-4-25", "Vitamin D3", "1000", "740"],
            ["2025-07", "07-3-25", "Vitamin D3", "1000", "860"],
        ],
        "Vitamin B-Complex": [
            ["2025-07", "07-5-25", "Vitamin B-Complex", "1000", "850"],
            ["2025-07", "07-4-25", "Vitamin B-Complex", "1000", "910"],
            ["2025-07", "07-3-25", "Vitamin B-Complex", "1000", "970"],
        ]
    };

    function renderFeedTable() {
        feedTab.classList.add("active", "feed-active");
        feedTab.classList.remove("vitamins-active");

        vitaminsTab.classList.remove("active", "vitamins-active", "feed-active");

        if (vitaminFilterWrap) {
            vitaminFilterWrap.classList.add("hidden");
        }

        tableHead.innerHTML = `
            <tr>
                <th>Purchase Date</th>
                <th>Date of Monitoring</th>
                <th>Initial Stock (kg)</th>
                <th>Remaining Stock (kg)</th>
            </tr>
        `;

        tableBody.innerHTML = feedRecords.map((row) => `
            <tr>
                <td>${row[0]}</td>
                <td>${row[1]}</td>
                <td>${row[2]}</td>
                <td>${row[3]}</td>
            </tr>
        `).join("");
    }

    function renderVitaminTable() {
        vitaminsTab.classList.add("active", "vitamins-active");
        vitaminsTab.classList.remove("feed-active");

        feedTab.classList.remove("active", "feed-active", "vitamins-active");

        if (vitaminFilterWrap) {
            vitaminFilterWrap.classList.remove("hidden");
        }

        const selectedVitamin = vitaminRecordFilter ? vitaminRecordFilter.value : "Vitamin E";
        const rows = vitaminRecords[selectedVitamin] || [];

        tableHead.innerHTML = `
            <tr>
                <th>Purchase Date</th>
                <th>Date of Monitoring</th>
                <th>Type</th>
                <th>Initial Stock (mL)</th>
                <th>Remaining Stock (mL)</th>
            </tr>
        `;

        tableBody.innerHTML = rows.map((row) => `
            <tr>
                <td>${row[0]}</td>
                <td>${row[1]}</td>
                <td>${row[2]}</td>
                <td>${row[3]}</td>
                <td>${row[4]}</td>
            </tr>
        `).join("");

        tableBody.querySelectorAll("tr").forEach((row, index) => {
        row.style.animation = "none";
        row.offsetHeight;
        row.style.animation = `recordRowFade 0.5s ease forwards`;
        row.style.animationDelay = `${0.18 + index * 0.08}s`;
});
    }

    feedTab.addEventListener("click", renderFeedTable);
    vitaminsTab.addEventListener("click", renderVitaminTable);

    if (vitaminRecordFilter) {
        vitaminRecordFilter.addEventListener("change", renderVitaminTable);
    }

    renderFeedTable();
}