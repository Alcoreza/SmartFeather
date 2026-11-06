document.addEventListener('DOMContentLoaded', async () => {
    let houses = [];
    let currentHouseIndex = 0;
    let currentRows = [];
    let currentPage = 0;
    const tabsContainer = document.getElementById('recordHouseTabs');
    const tableBody = document.getElementById('recordTableBody');
    const penFilter = document.getElementById('penFilter');
    const recordedDateFilter = document.getElementById('recordedDateFilter');
    const clearRecordDateFilters = document.getElementById('clearRecordDateFilters');
    const pagination = document.querySelector('[data-record-pagination]');
    const prevButton = document.querySelector('[data-record-prev]');
    const nextButton = document.querySelector('[data-record-next]');
    const dots = document.querySelector('[data-record-dots]');
    const RECORD_ROWS_PER_PAGE = 10;
    const RECORD_DOT_LIMIT = 5;
    let lastPage = 0;

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

    function buildRows(pens, emptyMessage = 'No records found.') {
        if (!pens || pens.length === 0) {
            return `
                <tr>
                    <td colspan="7">${emptyMessage}</td>
                </tr>
            `;
        }

        const start = currentPage * RECORD_ROWS_PER_PAGE;
        const pageRows = pens.slice(start, start + RECORD_ROWS_PER_PAGE);
        const placeholderRows = RECORD_ROWS_PER_PAGE - pageRows.length;

        let rows = pageRows.map((pen, index) => {
            const recordedDate = pen.recorded_at ? new Date(pen.recorded_at).toLocaleDateString() : 'N/A';
            const capacity = pen.capacity || 0;
            const population = pen.population || 0;
            const eggsHatched = pen.eggs_hatched || 0;
            const mortality = pen.mortality || 0;
            return `
                <tr style="--row-delay: ${Math.min(index * 0.055, 0.55)}s;">
                    <td>
                        <span class="record-house-badge">${escapeHtml(formatHouseName(pen.house_name || 'N/A'))}</span>
                    </td>
                    <td>${escapeHtml(pen.pen_name || 'N/A')}</td>
                    <td>${formatNumber(capacity)}</td>
                    <td>${formatNumber(population)}</td>
                    <td>${formatNumber(eggsHatched)}</td>
                    <td>${formatNumber(mortality)}</td>
                    <td>${recordedDate}</td>
                </tr>
            `;
        }).join('');

        if (placeholderRows > 0) {
            rows += Array.from({ length: placeholderRows }, () => `
                <tr class="record-placeholder-row" aria-hidden="true">
                    <td colspan="7">&nbsp;</td>
                </tr>
            `).join('');
        }

        return rows;
    }

    function buildTabs(houses) {
        if (!tabsContainer) return;

        const tabs = houses.map((house, index) => `
            <button class="record-house-tab ${index === 0 ? 'active' : ''}" data-house-index="${index}">
                ${escapeHtml(formatHouseName(house.name))}
            </button>
        `).join('');

        tabsContainer.innerHTML = tabs;

        // Add event listeners to tabs
        document.querySelectorAll('.record-house-tab').forEach((tab) => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.record-house-tab').forEach((item) => item.classList.remove('active'));
                tab.classList.add('active');
                tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

                const houseIndex = Number(tab.dataset.houseIndex);
                currentHouseIndex = houseIndex;
                currentPage = 0;
                resetFilters();
                renderHouse(houseIndex);
            });
        });
    }

    function renderHouse(index) {
        const currentHouse = houses[index];
        if (!currentHouse || !tableBody) return;

        populatePenFilter(currentHouse.pens || []);
        currentRows = applyFilters(currentHouse.pens || []);
        const hasFilter = Boolean(recordedDateFilter?.value || penFilter?.value);
        const totalPages = Math.max(1, Math.ceil(currentRows.length / RECORD_ROWS_PER_PAGE));
        currentPage = Math.min(currentPage, totalPages - 1);

        tableBody.innerHTML = buildRows(
            currentRows,
            hasFilter ? 'No records match the selected filters.' : 'No records found.'
        );
        updatePagination(currentRows.length);
    }

    function applyFilters(pens) {
        const selectedDate = recordedDateFilter?.value || '';
        const selectedPen = penFilter?.value || '';

        return pens.filter((pen) => {
            const matchesDate = !selectedDate || getDateFilterValue(pen.recorded_at) === selectedDate;
            const matchesPen = !selectedPen || String(pen.pen_name || '') === selectedPen;

            return matchesDate && matchesPen;
        });
    }

    function populatePenFilter(pens) {
        if (!penFilter || penFilter.dataset.houseIndex === String(currentHouseIndex)) return;

        const penNames = [...new Set(
            pens.map((pen) => String(pen.pen_name || '').trim()).filter(Boolean)
        )];

        penFilter.innerHTML = `
            <option value="">All Pens</option>
        ` + penNames.map((penName) => `
            <option value="${escapeHtml(penName)}">${escapeHtml(penName)}</option>
        `).join('');

        penFilter.dataset.houseIndex = String(currentHouseIndex);
    }

    function updatePagination(totalRows) {
        const totalPages = Math.max(1, Math.ceil(totalRows / RECORD_ROWS_PER_PAGE));
        const direction = currentPage > lastPage ? 'next' : currentPage < lastPage ? 'prev' : 'still';
        const visiblePages = getVisibleRecordPages(totalPages, currentPage);

        if (pagination) {
            pagination.classList.toggle('is-hidden', totalRows <= RECORD_ROWS_PER_PAGE);
        }

        if (prevButton) {
            prevButton.disabled = currentPage === 0;
        }

        if (nextButton) {
            nextButton.disabled = currentPage >= totalPages - 1;
        }

        if (dots) {
            dots.dataset.pageDirection = direction;
            dots.innerHTML = visiblePages.map((index) => `
                <button
                    type="button"
                    class="record-page-dot ${index === currentPage ? 'active' : ''}"
                    data-record-page="${index}"
                    aria-label="Go to page ${index + 1}"
                    aria-current="${index === currentPage ? 'page' : 'false'}"
                ></button>
            `).join('');
        }

        lastPage = currentPage;
    }

    function getVisibleRecordPages(totalPages, currentPage) {
        if (totalPages <= RECORD_DOT_LIMIT) {
            return Array.from({ length: totalPages }, (_, index) => index);
        }

        const centerOffset = Math.floor(RECORD_DOT_LIMIT / 2);
        let start = Math.max(0, currentPage - centerOffset);
        let end = start + RECORD_DOT_LIMIT;

        if (end > totalPages) {
            end = totalPages;
            start = Math.max(0, end - RECORD_DOT_LIMIT);
        }

        return Array.from({ length: end - start }, (_, index) => start + index);
    }

    function setupPagination() {
        prevButton?.addEventListener('click', () => {
            currentPage = Math.max(0, currentPage - 1);
            renderHouse(currentHouseIndex);
        });

        nextButton?.addEventListener('click', () => {
            const totalPages = Math.max(1, Math.ceil(currentRows.length / RECORD_ROWS_PER_PAGE));
            currentPage = Math.min(totalPages - 1, currentPage + 1);
            renderHouse(currentHouseIndex);
        });

        dots?.addEventListener('click', (event) => {
            const dot = event.target.closest('[data-record-page]');
            if (!dot) return;

            currentPage = Number(dot.dataset.recordPage || 0);
            renderHouse(currentHouseIndex);
        });
    }

    function resetFilters() {
        if (penFilter) {
            penFilter.value = '';
            penFilter.dataset.houseIndex = '';
        }
        if (recordedDateFilter) recordedDateFilter.value = '';
    }

    function getDateFilterValue(dateString) {
        if (!dateString) return '';

        const stringValue = String(dateString);
        const simpleDateMatch = stringValue.match(/^\d{4}-\d{2}-\d{2}/);

        if (simpleDateMatch) {
            return simpleDateMatch[0];
        }

        const parsedDate = new Date(stringValue);

        if (Number.isNaN(parsedDate.getTime())) {
            return '';
        }

        return parsedDate.toISOString().slice(0, 10);
    }

    function formatNumber(value) {
        const number = Number(value ?? 0);

        return Number.isInteger(number)
            ? number.toString()
            : number.toFixed(2);
    }

    function formatHouseName(value) {
        const name = String(value ?? '').trim();

        return name.replace(/^House\s+House\s+/i, 'House ') || 'N/A';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    recordedDateFilter?.addEventListener('change', () => {
        currentPage = 0;
        renderHouse(currentHouseIndex);
    });

    penFilter?.addEventListener('change', () => {
        currentPage = 0;
        renderHouse(currentHouseIndex);
    });

    clearRecordDateFilters?.addEventListener('click', () => {
        resetFilters();
        currentPage = 0;
        renderHouse(currentHouseIndex);
    });

    setupPagination();

    // Load data and initialize
    houses = await fetchPenRecords();
    
    if (houses.length > 0) {
        buildTabs(houses);
        renderHouse(0);
    } else {
        if (tableBody) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7">No houses or records found.</td>
                </tr>
            `;
        }
        updatePagination(0);
    }
});
