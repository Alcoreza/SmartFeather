document.addEventListener('DOMContentLoaded', () => {
    const houses = window.houseRecordData || [];
    const tabs = document.querySelectorAll('.record-house-tab');
    const tableBody = document.getElementById('recordTableBody');

    function buildRows(records) {
        if (!records || records.length === 0) {
            return `
                <tr>
                    <td colspan="9" class="record-empty">No records found.</td>
                </tr>
            `;
        }

        return records.map((record) => `
            <tr>
                <td>${record.batch_id}</td>
                <td>${record.start_date}</td>
                <td>${record.end_date}</td>
                <td>${record.reporting_date}</td>
                <td>${record.pen_no}</td>
                <td>${record.initial_population}</td>
                <td>${record.running_population}</td>
                <td>${record.mortalities}</td>
                <td>${record.eggs_hatched}</td>
            </tr>
        `).join('');
    }

    function renderHouse(index) {
        const currentHouse = houses[index];
        if (!currentHouse || !tableBody) return;

        tableBody.innerHTML = buildRows(currentHouse.records || []);
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((item) => item.classList.remove('active'));
            tab.classList.add('active');

            const houseIndex = Number(tab.dataset.houseIndex);
            renderHouse(houseIndex);
        });
    });

    renderHouse(0);
});