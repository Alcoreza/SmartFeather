document.addEventListener("DOMContentLoaded", async () => {
    let tabs = document.querySelectorAll(".house-tab");
    let houses = [];

    // Get CSRF token from meta tag
    const getCsrfToken = () => {
        return document.querySelector('meta[name="csrf-token"]')?.content || "";
    };

    const houseStatus = document.getElementById("houseStatus");
    const houseBatch = document.getElementById("houseBatch");
    const housePen = document.getElementById("housePen");
    const houseTemperature = document.getElementById("houseTemperature");
    const houseAmmonia = document.getElementById("houseAmmonia");
    const infoGrid = document.getElementById("infoGrid");
    const feedRow = document.getElementById("feedRow");
    const waterRow = document.getElementById("waterRow");

    const housesTabsContainer = document.querySelector(".houses-tabs");

    const addHouseButton = document.getElementById("openAddHouseModal");
    const addHouseModal = document.getElementById("addHouseModal");
    const closeAddHouseModal = document.getElementById("closeAddHouseModal");
    const cancelAddHouseModal = document.getElementById("cancelAddHouseModal");
    const addHouseForm = document.getElementById("addHouseForm");

    const editHouseButton = document.getElementById("openEditHouseModal");
    const editHouseModal = document.getElementById("editHouseModal");
    const closeEditHouseModal = document.getElementById("closeEditHouseModal");
    const cancelEditHouseModal = document.getElementById(
        "cancelEditHouseModal",
    );
    const editHouseForm = document.getElementById("editHouseForm");

    let activeHouseIndex = 0;
    let activePenIndex = 0;

    function openAddModal() {
        if (addHouseModal) {
            addHouseModal.classList.add("show");
        }
    }

    function closeAddModal() {
        if (addHouseModal) {
            addHouseModal.classList.remove("show");
        }

        if (addHouseForm) {
            addHouseForm.reset();
        }
    }

    function openEditModal() {
        const currentHouse = houses[activeHouseIndex];
        if (!currentHouse || !editHouseModal) return;

        const editHouseName = document.getElementById("editHouseName");
        const editBatchId = document.getElementById("editBatchId");
        const editStartDate = document.getElementById("editStartDate");
        const editPen = document.getElementById("editPen");
        const editCapacity = document.getElementById("editCapacity");
        const editPopulation = document.getElementById("editPopulation");
        const editEggsHatched = document.getElementById("editEggsHatched");
        const editMortality = document.getElementById("editMortality");

        // Populate house fields
        if (editHouseName) editHouseName.value = currentHouse.name;
        if (editBatchId) editBatchId.value = currentHouse.batch;
        if (editStartDate) editStartDate.value = currentHouse.start_date || "";

        // Populate pen selector
        if (editPen) {
            editPen.innerHTML = currentHouse.pens
                .map(
                    (pen, index) => `
                <option value="${index}" ${index === activePenIndex ? "selected" : ""}>${pen.pen_name}</option>
            `,
                )
                .join("");
        }

        // Get current pen data
        const currentPen = currentHouse.pens[activePenIndex];
        if (currentPen) {
            if (editCapacity) editCapacity.value = currentPen.capacity || 0;
            if (editPopulation)
                editPopulation.value = currentPen.population || 0;
            if (editEggsHatched)
                editEggsHatched.value = currentPen.eggs_hatched || 0;
            if (editMortality) editMortality.value = currentPen.mortality || 0;
        }

        editHouseModal.classList.add("show");
    }

    function closeEditModal() {
        if (editHouseModal) {
            editHouseModal.classList.remove("show");
        }

        if (editHouseForm) {
            editHouseForm.reset();
        }
    }

    function buildInfoCards(cards) {
        return cards
            .map(
                (card) => `
            <article class="info-card card-animate">
                <div class="info-icon ${card.accent}">
                    <span>${card.icon}</span>
                </div>
                <div class="info-text">
                    <div class="info-title">${card.title}</div>
                    <div class="info-subtitle">${card.subtitle}</div>
                </div>
            </article>
        `,
            )
            .join("");
    }

    function buildResourceRow(items, type) {
        return items
            .map(
                (item, index) => `
            <div class="resource-item stat-animate">
                <div class="resource-bar-box">
                    <div class="resource-bar ${type === "feed" ? "feed-bar" : "water-bar"}" style="width: ${item.value}%;"></div>
                </div>
                <div class="resource-value ${type === "feed" ? "feed-text" : "water-text"}">${item.value}%</div>
                <div class="resource-label">${item.label}</div>
            </div>
            ${index < items.length - 1 ? '<div class="resource-line"></div>' : ""}
        `,
            )
            .join("");
    }

    function populatePenOptions(house) {
        if (!housePen) return;

        housePen.innerHTML = house.pens
            .map(
                (pen, index) => `
            <option value="${index}">${pen.pen_name}</option>
        `,
            )
            .join("");
    }

    async function fetchHouses() {
        try {
            const response = await fetch("/api/houses");
            if (!response.ok) throw new Error("Failed to fetch houses");
            const result = await response.json();

            houses = (result.data || []).map((house) => ({
                id: house.id,
                name: house.house_number,
                status: house.status || "Active",
                batch: house.batch_code || "Batch-New",
                start_date: house.start_date,
                pens: (house.pens || []).map((pen) => ({
                    id: pen.id,
                    name: pen.pen_name,
                    pen_name: pen.pen_name,
                    temperature: "0 deg",
                    ammonia: "0 ppm",
                    capacity: pen.capacity || 0,
                    population: pen.population || 0,
                    eggs_hatched: pen.eggs_hatched || 0,
                    mortality: pen.mortality || 0,
                    cards: [
                        {
                            icon: "🏠",
                            title: `Capacity: ${pen.capacity || 0}`,
                            subtitle: `Population: ${pen.population || 0}`,
                            accent: "red",
                        },
                        {
                            icon: "📅",
                            title: "Start Date",
                            subtitle: house.start_date || "Not set",
                            accent: "blue",
                        },
                        {
                            icon: "💚",
                            title: "Current Condition",
                            subtitle: "Normal",
                            accent: "green",
                        },
                        {
                            icon: "📊",
                            title: `Eggs Hatched: ${pen.eggs_hatched || 0}`,
                            subtitle: `Mortality: ${pen.mortality || 0}`,
                            accent: "orange",
                        },
                    ],
                    feeders: [
                        { label: "Feeder 1", value: 0 },
                        { label: "Feeder 2", value: 0 },
                        { label: "Feeder 3", value: 0 },
                    ],
                    drinkers: [
                        { label: "Drinker 1", value: 0 },
                        { label: "Drinker 2", value: 0 },
                        { label: "Drinker 3", value: 0 },
                    ],
                })),
                records: [],
            }));

            if (houses.length > 0) {
                activeHouseIndex = 0;
                rebuildHouseTabs();
                renderHouse(0);
            }
        } catch (error) {
            console.error("Error fetching houses:", error);
            alert("Error loading houses: " + error.message);
        }
    }

    function animateStats() {
        const animatedElements = document.querySelectorAll(
            ".env-card, .resource-section, #houseStatus, #houseBatch, #housePen, #houseTemperature, #houseAmmonia, .info-card, .resource-item",
        );

        animatedElements.forEach((element, index) => {
            element.classList.remove("show-stat", "show-card");
            void element.offsetWidth;
            element.style.animationDelay = `${index * 0.07}s`;

            if (
                element.classList.contains("env-card") ||
                element.classList.contains("resource-section") ||
                element.classList.contains("info-card")
            ) {
                element.classList.add("show-card");
            } else {
                element.classList.add("show-stat");
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
        if (feedRow) feedRow.innerHTML = buildResourceRow(pen.feeders, "feed");
        if (waterRow)
            waterRow.innerHTML = buildResourceRow(pen.drinkers, "water");

        if (houseStatus) {
            if (house.status.toLowerCase() === "inactive") {
                houseStatus.classList.remove("chip-green");
                houseStatus.classList.add("chip-gray");
            } else {
                houseStatus.classList.remove("chip-gray");
                houseStatus.classList.add("chip-green");
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
        tabs = document.querySelectorAll(".house-tab");

        tabs.forEach((tab) => {
            tab.addEventListener("click", () => {
                tabs.forEach((item) => item.classList.remove("active"));
                tab.classList.add("active");

                const houseIndex = Number(tab.dataset.houseIndex);
                renderHouse(houseIndex);
            });
        });
    }

    function rebuildHouseTabs() {
        const addButtonHtml = `
            <button type="button" class="add-house-btn" id="openAddHouseModal">+</button>
        `;

        const tabsHtml = houses
            .map(
                (house, index) => `
            <button
                type="button"
                class="house-tab ${index === activeHouseIndex ? "active" : ""}"
                data-house-index="${index}"
            >
                ${house.name}
            </button>
        `,
            )
            .join("");

        housesTabsContainer.innerHTML = tabsHtml + addButtonHtml;

        attachTabEvents();

        const newAddHouseButton = document.getElementById("openAddHouseModal");
        if (newAddHouseButton) {
            newAddHouseButton.addEventListener("click", openAddModal);
        }
    }

    attachTabEvents();

    if (housePen) {
        housePen.addEventListener("change", (event) => {
            activePenIndex = Number(event.target.value);
            renderPen(activeHouseIndex, activePenIndex);
        });
    }

    if (addHouseButton) {
        addHouseButton.addEventListener("click", openAddModal);
    }

    if (closeAddHouseModal) {
        closeAddHouseModal.addEventListener("click", closeAddModal);
    }

    if (cancelAddHouseModal) {
        cancelAddHouseModal.addEventListener("click", closeAddModal);
    }

    if (addHouseModal) {
        addHouseModal.addEventListener("click", (event) => {
            if (event.target === addHouseModal) {
                closeAddModal();
            }
        });
    }

    if (addHouseForm) {
        addHouseForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            const houseNameInput = document.getElementById("houseName");
            const houseStatusInput =
                document.getElementById("houseStatusInput");
            const houseBatchInput = document.getElementById("houseBatchInput");
            const penCountInput = document.getElementById("housePenCount");

            const houseName = houseNameInput?.value.trim();
            const houseStatusValue = houseStatusInput?.value || "active";
            const houseBatchValue =
                houseBatchInput?.value.trim() || "Batch-New";
            const penCountValue = Number(penCountInput?.value || 1);

            if (!houseName) {
                alert("Please enter a house name.");
                return;
            }

            try {
                const response = await fetch("/api/houses", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                    body: JSON.stringify({
                        house_number: houseName,
                        status: houseStatusValue,
                        batch_code: houseBatchValue,
                        number_of_pens: penCountValue > 0 ? penCountValue : 1,
                    }),
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || "Failed to create house");
                }

                const result = await response.json();
                alert("House created successfully!");

                addHouseForm.reset();
                closeAddModal();

                await fetchHouses();
            } catch (error) {
                console.error("Error creating house:", error);
                alert("Error creating house: " + error.message);
            }
        });
    }

    if (editHouseButton) {
        editHouseButton.addEventListener("click", openEditModal);
    }

    if (closeEditHouseModal) {
        closeEditHouseModal.addEventListener("click", closeEditModal);
    }

    if (cancelEditHouseModal) {
        cancelEditHouseModal.addEventListener("click", closeEditModal);
    }

    if (editHouseModal) {
        editHouseModal.addEventListener("click", (event) => {
            if (event.target === editHouseModal) {
                closeEditModal();
            }
        });
    }

    if (editHouseForm) {
        editHouseForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            const editHouseName = document.getElementById("editHouseName");
            const editBatchId = document.getElementById("editBatchId");
            const editStartDate = document.getElementById("editStartDate");
            const editPen = document.getElementById("editPen");
            const editCapacity = document.getElementById("editCapacity");
            const editPopulation = document.getElementById("editPopulation");
            const editEggsHatched = document.getElementById("editEggsHatched");
            const editMortality = document.getElementById("editMortality");

            const currentHouse = houses[activeHouseIndex];
            if (!currentHouse) return;

            const selectedPenIndex = editPen
                ? Number(editPen.value)
                : activePenIndex;
            const currentPen = currentHouse.pens[selectedPenIndex];
            if (!currentPen) return;

            try {
                // 1. Update house data
                const houseResponse = await fetch(
                    `/api/houses/${currentHouse.id}`,
                    {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": getCsrfToken(),
                        },
                        body: JSON.stringify({
                            house_number:
                                editHouseName?.value.trim() ||
                                currentHouse.name,
                            batch_code:
                                editBatchId?.value.trim() || currentHouse.batch,
                            start_date:
                                editStartDate?.value || currentHouse.start_date,
                        }),
                    },
                );

                if (!houseResponse.ok) {
                    const error = await houseResponse.json();
                    throw new Error(error.message || "Failed to update house");
                }

                // 2. Update pen data (capacity, population)
                const penResponse = await fetch(`/api/pens/${currentPen.id}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                    body: JSON.stringify({
                        capacity: parseInt(editCapacity?.value) || 0,
                        population: parseInt(editPopulation?.value) || 0,
                    }),
                });

                if (!penResponse.ok) {
                    const error = await penResponse.json();
                    throw new Error(error.message || "Failed to update pen");
                }

                // 3. Update pen production data
                const productionResponse = await fetch(
                    `/api/pens/${currentPen.id}/production`,
                    {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": getCsrfToken(),
                        },
                        body: JSON.stringify({
                            eggs_hatched: parseInt(editEggsHatched?.value) || 0,
                            mortality: parseInt(editMortality?.value) || 0,
                        }),
                    },
                );

                if (!productionResponse.ok) {
                    const error = await productionResponse.json();
                    throw new Error(
                        error.message || "Failed to update production data",
                    );
                }

                alert("House and pen data updated successfully!");
                activePenIndex = selectedPenIndex;
                closeEditModal();
                await fetchHouses();
            } catch (error) {
                console.error("Error updating data:", error);
                alert("Error: " + error.message);
            }
        });
    }

    await fetchHouses();
});
