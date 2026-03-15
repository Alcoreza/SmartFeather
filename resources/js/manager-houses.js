document.addEventListener('DOMContentLoaded', () => {
    let tabs = document.querySelectorAll('.house-tab');
    const houses = window.houseData || [];

    const houseStatus = document.getElementById('houseStatus');
    const houseBatch = document.getElementById('houseBatch');
    const housePen = document.getElementById('housePen');
    const houseTemperature = document.getElementById('houseTemperature');
    const houseAmmonia = document.getElementById('houseAmmonia');
    const infoGrid = document.getElementById('infoGrid');
    const feedRow = document.getElementById('feedRow');
    const waterRow = document.getElementById('waterRow');

    const housesTabsContainer = document.querySelector('.houses-tabs');

    const addHouseButton = document.getElementById('openAddHouseModal');
    const addHouseModal = document.getElementById('addHouseModal');
    const closeAddHouseModal = document.getElementById('closeAddHouseModal');
    const cancelAddHouseModal = document.getElementById('cancelAddHouseModal');
    const addHouseForm = document.getElementById('addHouseForm');

    const editHouseButton = document.getElementById('openEditHouseModal');
    const editHouseModal = document.getElementById('editHouseModal');
    const closeEditHouseModal = document.getElementById('closeEditHouseModal');
    const cancelEditHouseModal = document.getElementById('cancelEditHouseModal');
    const editHouseForm = document.getElementById('editHouseForm');

    const openRecordsModal = document.getElementById('openRecordsModal');
    const recordsModal = document.getElementById('recordsModal');
    const closeRecordsModal = document.getElementById('closeRecordsModal');
    const recordsHouseTitle = document.getElementById('recordsHouseTitle');
    const recordsTableBody = document.getElementById('recordsTableBody');

    let activeHouseIndex = 0;
    let activePenIndex = 0;

    function openAddModal() {
        if (addHouseModal) {
            addHouseModal.classList.add('show');
        }
    }

    function closeAddModal() {
        if (addHouseModal) {
            addHouseModal.classList.remove('show');
        }

        if (addHouseForm) {
            addHouseForm.reset();
        }
    }

    function openEditModal() {
        const currentHouse = houses[activeHouseIndex];
        if (!currentHouse || !editHouseModal) return;

        const editHouseName = document.getElementById('editHouseName');
        const editHouseStatus = document.getElementById('editHouseStatus');
        const editHouseBatch = document.getElementById('editHouseBatch');

        if (editHouseName) editHouseName.value = currentHouse.name;
        if (editHouseStatus) editHouseStatus.value = currentHouse.status;
        if (editHouseBatch) editHouseBatch.value = currentHouse.batch;

        editHouseModal.classList.add('show');
    }

    function closeEditModal() {
        if (editHouseModal) {
            editHouseModal.classList.remove('show');
        }

        if (editHouseForm) {
            editHouseForm.reset();
        }
    }

    function buildRecordsRows(records) {
        if (!records || records.length === 0) {
            return `
                <tr>
                    <td colspan="4">No flock batch records found.</td>
                </tr>
            `;
        }

        return records.map((record) => {
            const statusClass = String(record.condition).toLowerCase();

            return `
                <tr>
                    <td>${record.batch}</td>
                    <td>${record.date}</td>
                    <td>${record.population}</td>
                    <td>
                        <span class="record-status ${statusClass}">
                            ${record.condition}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function openRecordsPopup() {
        const currentHouse = houses[activeHouseIndex];
        if (!currentHouse || !recordsModal) return;

        if (recordsHouseTitle) {
            recordsHouseTitle.textContent = `${currentHouse.name} - Flock Batch Records`;
        }

        if (recordsTableBody) {
            recordsTableBody.innerHTML = buildRecordsRows(currentHouse.records || []);
        }

        recordsModal.classList.add('show');
    }

    function closeRecordsPopup() {
        if (recordsModal) {
            recordsModal.classList.remove('show');
        }
    }

    function buildInfoCards(cards) {
        return cards.map((card) => `
            <article class="info-card card-animate">
                <div class="info-icon ${card.accent}">
                    <span>${card.icon}</span>
                </div>
                <div class="info-text">
                    <div class="info-title">${card.title}</div>
                    <div class="info-subtitle">${card.subtitle}</div>
                </div>
            </article>
        `).join('');
    }

    function buildResourceRow(items, type) {
        return items.map((item, index) => `
            <div class="resource-item stat-animate">
                <div class="resource-bar-box">
                    <div class="resource-bar ${type === 'feed' ? 'feed-bar' : 'water-bar'}" style="width: ${item.value}%;"></div>
                </div>
                <div class="resource-value ${type === 'feed' ? 'feed-text' : 'water-text'}">${item.value}%</div>
                <div class="resource-label">${item.label}</div>
            </div>
            ${index < items.length - 1 ? '<div class="resource-line"></div>' : ''}
        `).join('');
    }

    function populatePenOptions(house) {
        if (!housePen) return;

        housePen.innerHTML = house.pens.map((pen, index) => `
            <option value="${index}">${pen.name}</option>
        `).join('');
    }

    function animateStats() {
        const animatedElements = document.querySelectorAll(
            '.env-card, .resource-section, #houseStatus, #houseBatch, #housePen, .toolbar-btn, #houseTemperature, #houseAmmonia, .info-card, .resource-item'
        );

        animatedElements.forEach((element, index) => {
            element.classList.remove('show-stat', 'show-card');
            void element.offsetWidth;
            element.style.animationDelay = `${index * 0.07}s`;

            if (
                element.classList.contains('env-card') ||
                element.classList.contains('resource-section') ||
                element.classList.contains('info-card')
            ) {
                element.classList.add('show-card');
            } else {
                element.classList.add('show-stat');
            }
        });
    }

    function renderPen(houseIndex, penIndex) {
        const house = houses[houseIndex];
        if (!house || !house.pens || !house.pens[penIndex]) return;

        const pen = house.pens[penIndex];

        if (houseStatus) houseStatus.textContent = house.status;
        if (houseBatch) houseBatch.textContent = house.batch;
        if (houseTemperature) houseTemperature.textContent = pen.temperature;
        if (houseAmmonia) houseAmmonia.textContent = pen.ammonia;

        if (infoGrid) infoGrid.innerHTML = buildInfoCards(pen.cards);
        if (feedRow) feedRow.innerHTML = buildResourceRow(pen.feeders, 'feed');
        if (waterRow) waterRow.innerHTML = buildResourceRow(pen.drinkers, 'water');

        if (houseStatus) {
            if (house.status.toLowerCase() === 'inactive') {
                houseStatus.classList.remove('chip-green');
                houseStatus.classList.add('chip-gray');
            } else {
                houseStatus.classList.remove('chip-gray');
                houseStatus.classList.add('chip-green');
            }
        }

        if (housePen) {
            housePen.value = penIndex;
        }

        animateStats();
    }

    function renderHouse(houseIndex) {
        const house = houses[houseIndex];
        if (!house) return;

        activeHouseIndex = houseIndex;
        activePenIndex = 0;

        populatePenOptions(house);
        renderPen(activeHouseIndex, activePenIndex);
    }

    function attachTabEvents() {
        tabs = document.querySelectorAll('.house-tab');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                tabs.forEach((item) => item.classList.remove('active'));
                tab.classList.add('active');

                const houseIndex = Number(tab.dataset.houseIndex);
                renderHouse(houseIndex);
            });
        });
    }

    function rebuildHouseTabs() {
        const addButtonHtml = `
            <button type="button" class="add-house-btn" id="openAddHouseModal">+</button>
        `;

        const tabsHtml = houses.map((house, index) => `
            <button
                type="button"
                class="house-tab ${index === activeHouseIndex ? 'active' : ''}"
                data-house-index="${index}"
            >
                ${house.name}
            </button>
        `).join('');

        housesTabsContainer.innerHTML = tabsHtml + addButtonHtml;

        attachTabEvents();

        const newAddHouseButton = document.getElementById('openAddHouseModal');
        if (newAddHouseButton) {
            newAddHouseButton.addEventListener('click', openAddModal);
        }
    }

    function createDefaultHouse(newHouseName, newStatus, newBatch, penCount) {
        const pens = [];

        for (let i = 1; i <= penCount; i++) {
            pens.push({
                name: `Pen ${i}`,
                temperature: '0 deg',
                ammonia: '0 ppm',
                cards: [
                    {
                        icon: '🏠',
                        title: 'Capacity: 0',
                        subtitle: 'Population: 0',
                        accent: 'red',
                    },
                    {
                        icon: '📅',
                        title: 'Start Date',
                        subtitle: 'Not set',
                        accent: 'blue',
                    },
                    {
                        icon: '💚',
                        title: 'Current Condition',
                        subtitle: 'Normal',
                        accent: 'green',
                    },
                    {
                        icon: '📊',
                        title: 'Eggs Hatched: 0',
                        subtitle: 'Mortality: 0',
                        accent: 'orange',
                    },
                ],
                feeders: [
                    { label: 'Feeder 1', value: 0 },
                    { label: 'Feeder 2', value: 0 },
                    { label: 'Feeder 3', value: 0 },
                ],
                drinkers: [
                    { label: 'Drinker 1', value: 0 },
                    { label: 'Drinker 2', value: 0 },
                    { label: 'Drinker 3', value: 0 },
                ],
            });
        }

        return {
            name: newHouseName,
            status: newStatus,
            batch: newBatch,
            records: [],
            pens,
        };
    }

    attachTabEvents();

    if (housePen) {
        housePen.addEventListener('change', (event) => {
            activePenIndex = Number(event.target.value);
            renderPen(activeHouseIndex, activePenIndex);
        });
    }

    if (addHouseButton) {
        addHouseButton.addEventListener('click', openAddModal);
    }

    if (closeAddHouseModal) {
        closeAddHouseModal.addEventListener('click', closeAddModal);
    }

    if (cancelAddHouseModal) {
        cancelAddHouseModal.addEventListener('click', closeAddModal);
    }

    if (addHouseModal) {
        addHouseModal.addEventListener('click', (event) => {
            if (event.target === addHouseModal) {
                closeAddModal();
            }
        });
    }

    if (addHouseForm) {
        addHouseForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const houseNameInput = document.getElementById('houseName');
            const houseStatusInput = document.getElementById('houseStatusInput');
            const houseBatchInput = document.getElementById('houseBatchInput');
            const penCountInput = document.getElementById('housePenCount');

            const houseName = houseNameInput?.value.trim();
            const houseStatusValue = houseStatusInput?.value || 'Active';
            const houseBatchValue = houseBatchInput?.value.trim() || 'Batch-New';
            const penCountValue = Number(penCountInput?.value || 1);

            if (!houseName) {
                alert('Please enter a house name.');
                return;
            }

            const newHouse = createDefaultHouse(
                houseName,
                houseStatusValue,
                houseBatchValue,
                penCountValue > 0 ? penCountValue : 1
            );

            houses.push(newHouse);
            activeHouseIndex = houses.length - 1;
            activePenIndex = 0;

            rebuildHouseTabs();
            renderHouse(activeHouseIndex);
            closeAddModal();
        });
    }

    if (editHouseButton) {
        editHouseButton.addEventListener('click', openEditModal);
    }

    if (closeEditHouseModal) {
        closeEditHouseModal.addEventListener('click', closeEditModal);
    }

    if (cancelEditHouseModal) {
        cancelEditHouseModal.addEventListener('click', closeEditModal);
    }

    if (editHouseModal) {
        editHouseModal.addEventListener('click', (event) => {
            if (event.target === editHouseModal) {
                closeEditModal();
            }
        });
    }

    if (editHouseForm) {
        editHouseForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const editHouseName = document.getElementById('editHouseName');
            const editHouseStatus = document.getElementById('editHouseStatus');
            const editHouseBatch = document.getElementById('editHouseBatch');

            const currentHouse = houses[activeHouseIndex];
            if (!currentHouse) return;

            currentHouse.name = editHouseName?.value.trim() || currentHouse.name;
            currentHouse.status = editHouseStatus?.value || currentHouse.status;
            currentHouse.batch = editHouseBatch?.value.trim() || currentHouse.batch;

            rebuildHouseTabs();
            renderHouse(activeHouseIndex);
            closeEditModal();
        });
    }

    if (openRecordsModal) {
        openRecordsModal.addEventListener('click', openRecordsPopup);
    }

    if (closeRecordsModal) {
        closeRecordsModal.addEventListener('click', closeRecordsPopup);
    }

    if (recordsModal) {
        recordsModal.addEventListener('click', (event) => {
            if (event.target === recordsModal) {
                closeRecordsPopup();
            }
        });
    }

    renderHouse(0);
});