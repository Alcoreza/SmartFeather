document.addEventListener('DOMContentLoaded', async () => {
    let houses = [];
    const tabsContainer = document.getElementById('recordHouseTabs');
    const tableBody = document.getElementById('recordTableBody');

    // Fetch records from API
    async function fetchPenRecords() {
        try {
            const response = await fetch('/api/houses/records/pens');
            if (!response.ok) {
                throw new Error('Failed to fetch records');
            }
            const result = await response.json();
            return result.data || [];
        } catch (error) {
            console.error('Error fetching pen records:', error);
            return [];
        }
    }

    function buildRows(pens) {
        if (!pens || pens.length === 0) {
            return `
                <tr>
                    <td colspan="7" class="record-empty">No records found.</td>
                </tr>
            `;
        }

        return pens.map((pen) => {
            const recordedDate = pen.recorded_at ? new Date(pen.recorded_at).toLocaleDateString() : 'N/A';
            return `
                <tr>
                    <td>${pen.house_name || 'N/A'}</td>
                    <td>${pen.pen_name || 'N/A'}</td>
                    <td>${pen.capacity || 0}</td>
                    <td>${pen.population || 0}</td>
                    <td>${pen.eggs_hatched || 0}</td>
                    <td>${pen.mortality || 0}</td>
                    <td>${recordedDate}</td>
                </tr>
            `;
        }).join('');
    }

    function buildTabs(houses) {
        if (!tabsContainer) return;

        const tabs = houses.map((house, index) => `
            <button class="record-house-tab ${index === 0 ? 'active' : ''}" data-house-index="${index}">
                ${house.name}
            </button>
        `).join('');

        tabsContainer.innerHTML = tabs;

        // Add event listeners to tabs
        document.querySelectorAll('.record-house-tab').forEach((tab) => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.record-house-tab').forEach((item) => item.classList.remove('active'));
                tab.classList.add('active');

                const houseIndex = Number(tab.dataset.houseIndex);
                renderHouse(houseIndex);
            });
        });
    }

    function renderHouse(index) {
        const currentHouse = houses[index];
        if (!currentHouse || !tableBody) return;

        tableBody.innerHTML = buildRows(currentHouse.pens || []);
    }

    // Load data and initialize
    houses = await fetchPenRecords();
    
    if (houses.length > 0) {
        buildTabs(houses);
        renderHouse(0);
    } else {
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="record-empty">No houses or records found.</td>
                </tr>
            `;
        }
    }
});