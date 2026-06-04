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
    const temperatureGauge = houseTemperature?.closest(".semi-gauge");
    const ammoniaGauge = houseAmmonia?.closest(".semi-gauge");
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

    const endBatchButton = document.getElementById("openEndBatchModal");
    const endBatchModal = document.getElementById("endBatchModal");
    const closeEndBatchModal = document.getElementById("closeEndBatchModal");
    const cancelEndBatchModal = document.getElementById("cancelEndBatchModal");
    const endBatchForm = document.getElementById("endBatchForm");
    const penSelectGroup = document.getElementById("penSelectGroup");
    const endAction = document.getElementById("endAction");
    const endPenSelect = document.getElementById("endPenSelect");

    console.log("Debug: Elements found - endBatchForm:", endBatchForm, "endAction:", endAction, "endPenSelect:", endPenSelect);

    let activeHouseIndex = 0;
    let activePenIndex = 0;
    const addHouseRequiredFields = [
        { id: "houseName", label: "House Number" },
        { id: "housePenCount", label: "Number of Pens" },
    ];
    let shouldTrackAddHouseRequiredHighlights = false;

    function isAddHouseRequiredFieldEmpty(field) {
        if (field.id === "housePenCount") {
            return Number(document.getElementById(field.id)?.value || 0) <= 0;
        }

        return !(document.getElementById(field.id)?.value || "").trim();
    }

    function clearAddHouseRequiredFieldHighlight(input) {
        input?.closest(".modal-group")?.classList.remove("has-error");
    }

    function syncAddHouseRequiredFieldHighlight(field) {
        const input = document.getElementById(field.id);
        if (!input) return;

        if (shouldTrackAddHouseRequiredHighlights && isAddHouseRequiredFieldEmpty(field)) {
            input.closest(".modal-group")?.classList.add("has-error");
            return;
        }

        clearAddHouseRequiredFieldHighlight(input);

        const hasErrors = addHouseForm?.querySelector(".modal-group.has-error");
        if (!hasErrors) {
            shouldTrackAddHouseRequiredHighlights = false;
            clearAddHouseFormError();
        }
    }

    function clearAddHouseRequiredFieldHighlights() {
        shouldTrackAddHouseRequiredHighlights = false;

        document
            .querySelectorAll("#addHouseForm .modal-group.has-error")
            .forEach((field) => field.classList.remove("has-error"));
    }

    function showAddHouseFormError() {
        const formError = document.getElementById("addHouseFormError");
        if (!formError) return;

        formError.textContent = "Please fill in the required fields.";
        formError.classList.add("show");
        formError.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    function clearAddHouseFormError() {
        const formError = document.getElementById("addHouseFormError");

        formError?.classList.remove("show");
        if (formError) {
            formError.textContent = "";
        }
    }

    function resetAddHouseForm() {
        if (!addHouseForm) return;

        addHouseForm.reset();

        const penCountInput = document.getElementById("housePenCount");
        if (penCountInput) penCountInput.value = "0";
    }

    function getMissingAddHouseRequiredFields() {
        return addHouseRequiredFields.filter(isAddHouseRequiredFieldEmpty);
    }

    function showAddHouseRequiredFieldsError() {
        const missingFields = getMissingAddHouseRequiredFields();

        if (!missingFields.length) {
            clearAddHouseRequiredFieldHighlights();
            clearAddHouseFormError();
            return false;
        }

        clearAddHouseRequiredFieldHighlights();
        shouldTrackAddHouseRequiredHighlights = true;

        missingFields.forEach((field) => {
            document
                .getElementById(field.id)
                ?.closest(".modal-group")
                ?.classList.add("has-error");
        });

        showAddHouseFormError();
        setTimeout(() => document.getElementById(missingFields[0].id)?.focus(), 250);

        return true;
    }

    function openAddModal() {
        if (addHouseModal) {
            resetAddHouseForm();
            clearAddHouseRequiredFieldHighlights();
            clearAddHouseFormError();
            addHouseModal.classList.add("show");
        }
    }

    function closeAddModal() {
        if (addHouseModal) {
            addHouseModal.classList.remove("show");
        }

        if (addHouseForm) {
            resetAddHouseForm();
            clearAddHouseRequiredFieldHighlights();
            clearAddHouseFormError();
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
            if (editBatchId) editBatchId.value = currentPen.batch || "";
            if (editStartDate)
                editStartDate.value =
                    currentPen.batch_started_at || currentHouse.start_date || "";
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

    function updateEndBatchButtonState() {
        if (!endBatchButton) return;

        const currentHouse = houses[activeHouseIndex];
        const currentPen = currentHouse?.pens?.[activePenIndex];
        const hasActiveBatch = Boolean(currentPen?.batch?.toString().trim());

        endBatchButton.disabled = !hasActiveBatch;
        endBatchButton.setAttribute("aria-disabled", String(!hasActiveBatch));
        endBatchButton.title = hasActiveBatch
            ? "End the active batch"
            : "No active batch to end";
    }

    function openEndBatchModal() {
        const currentHouse = houses[activeHouseIndex];
        if (!currentHouse || !endBatchModal) return;

        if (endBatchButton?.disabled) {
            return;
        }

        // Populate pen select
        if (endPenSelect) {
            endPenSelect.innerHTML = '<option value="">Select a pen...</option>';
            sortPensById(currentHouse.pens).forEach((pen) => {
                const option = document.createElement("option");
                option.value = pen.id;
                option.textContent = pen.pen_name;
                endPenSelect.appendChild(option);
            });
        }

        endBatchModal.classList.add("show");
    }

    function closeEndModal() {
        if (endBatchModal) {
            endBatchModal.classList.remove("show");
        }

        if (endBatchForm) {
            endBatchForm.reset();
        }

        if (penSelectGroup) {
            penSelectGroup.style.display = 'none';
        }

        if (endAction) {
            endAction.value = '';
        }

        if (endPenSelect) {
            endPenSelect.innerHTML = '<option value="">Select a pen...</option>';
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

    function sortPensById(pens) {
        return [...(pens || [])].sort((a, b) => {
            const left = Number(a?.id ?? 0);
            const right = Number(b?.id ?? 0);

            return left - right;
        });
    }

    function getSensorReadingDisplay(readings, type, fallback) {
        return readings?.[type]?.formatted_value || fallback;
    }

    function getSensorReadingValue(readings, type) {
        const value = readings?.[type]?.value;
        const numberValue = Number(value);

        return Number.isFinite(numberValue) ? numberValue : null;
    }

    function getGaugeState(type, value) {
        if (value === null) {
            return "no-data";
        }

        if (type === "temperature") {
            if (value >= 35) return "danger";
            if (value >= 30) return "warning";
            return "safe";
        }

        if (value >= 25) return "danger";
        if (value >= 10) return "warning";
        return "safe";
    }

    function updateGauge(gauge, type, value) {
        if (!gauge) return;

        const maxValue = type === "temperature" ? 40 : 40;
        const safeValue = value ?? 0;
        const degrees = Math.max(0, Math.min(180, (safeValue / maxValue) * 180));

        gauge.style.setProperty("--gauge-value", `${degrees}deg`);
        gauge.classList.remove("safe", "warning", "danger", "no-data");
        gauge.classList.add(getGaugeState(type, value));
    }

    function populatePenOptions(house) {
        if (!housePen) return;

        housePen.innerHTML = sortPensById(house.pens)
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

            houses = (result.data || [])
                .map((house) => ({
                    id: house.id,
                    name: house.house_number,
                    status: house.status || "Running",
                    batch: "",
                    start_date: house.start_date,
                    pens: sortPensById((house.pens || []).map((pen) => {
                        const flockBatch = pen.running_batch || pen.current_batch || null;
                        const sensorReadings = pen.sensor_readings;

                        return {
                            id: pen.id,
                            name: pen.pen_name,
                            pen_name: pen.pen_name,
                            batch: flockBatch?.batch_code || null,
                            status: flockBatch?.status || "Inactive",
                            temperature: getSensorReadingDisplay(
                                sensorReadings,
                                "temperature",
                                "0 deg",
                            ),
                            ammonia: getSensorReadingDisplay(
                                sensorReadings,
                                "ammonia",
                                "0 ppm",
                            ),
                            temperatureValue: getSensorReadingValue(
                                sensorReadings,
                                "temperature",
                            ),
                            ammoniaValue: getSensorReadingValue(
                                sensorReadings,
                                "ammonia",
                            ),
                            capacity: pen.capacity || 0,
                            population: pen.population || 0,
                            eggs_hatched: pen.eggs_hatched || 0,
                            mortality: pen.mortality || 0,
                            batch_started_at:
                                flockBatch?.started_at || pen.batch_started_at || null,
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
                                    subtitle:
                                        flockBatch?.started_at ||
                                        pen.batch_started_at ||
                                        "Not set",
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
                        };
                    })),
                    records: [],
                }))
                .sort((a, b) => Number(a.id) - Number(b.id));

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

        if (houseStatus) houseStatus.textContent = pen.status;
        if (houseBatch) houseBatch.textContent = pen.batch || "No Batch";
        if (houseTemperature) houseTemperature.textContent = pen.temperature;
        if (houseAmmonia) houseAmmonia.textContent = pen.ammonia;
        updateGauge(temperatureGauge, "temperature", pen.temperatureValue);
        updateGauge(ammoniaGauge, "ammonia", pen.ammoniaValue);

        if (infoGrid) infoGrid.innerHTML = buildInfoCards(pen.cards);
        if (feedRow) feedRow.innerHTML = buildResourceRow(pen.feeders, "feed");
        if (waterRow)
            waterRow.innerHTML = buildResourceRow(pen.drinkers, "water");

        if (houseStatus) {
            if (pen.status.toLowerCase() === "running") {
                houseStatus.classList.remove("chip-gray");
                houseStatus.classList.add("chip-green");
            } else {
                houseStatus.classList.remove("chip-green");
                houseStatus.classList.add("chip-gray");
            }
        }

        if (housePen) {
            housePen.value = penIndex;
        }

        updateEndBatchButtonState();
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
                class="house-tab ${index === activeHouseIndex ? "active" : " No Batch"}"
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
        addHouseRequiredFields.forEach((field) => {
            const input = document.getElementById(field.id);

            input?.addEventListener("input", () => syncAddHouseRequiredFieldHighlight(field));
            input?.addEventListener("change", () => syncAddHouseRequiredFieldHighlight(field));
        });

        addHouseForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (showAddHouseRequiredFieldsError()) {
                return;
            }

            const houseNameInput = document.getElementById("houseName");
            const houseStatusInput =
                document.getElementById("houseStatusInput");
            const penCountInput = document.getElementById("housePenCount");

            const houseName = houseNameInput?.value.trim();
            const houseStatusValue = houseStatusInput?.value || "Running";
            const penCountValue = Number(penCountInput?.value || 0);

            try {
                const payload = {
                    house_number: houseName,
                    status: houseStatusValue,
                    number_of_pens: penCountValue,
                };

                const response = await fetch("/api/houses", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || "Failed to create house");
                }

                const result = await response.json();
                alert("House created successfully!");

                resetAddHouseForm();
                clearAddHouseRequiredFieldHighlights();
                clearAddHouseFormError();
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

    // End Batch Modal Events
    if (endBatchButton) {
        endBatchButton.addEventListener("click", openEndBatchModal);
    }

    if (closeEndBatchModal) {
        closeEndBatchModal.addEventListener("click", closeEndModal);
    }

    if (cancelEndBatchModal) {
        cancelEndBatchModal.addEventListener("click", closeEndModal);
    }

    if (endBatchModal) {
        endBatchModal.addEventListener("click", (event) => {
            if (event.target === endBatchModal) {
                closeEndModal();
            }
        });
    }

    // Action select change to show/hide pen select
    if (endAction) {
        endAction.addEventListener('change', (event) => {
            if (event.target.value === 'pen') {
                penSelectGroup.style.display = 'block';
            } else {
                penSelectGroup.style.display = 'none';
            }
        });
    }

    console.log("Debug: Attaching event listener to endBatchForm:", endBatchForm);
    if (endBatchForm) {
        endBatchForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            const endOption = endAction.value;
            const penId = endPenSelect ? endPenSelect.value : null;

            console.log('End batch form submitted');
            console.log('endOption:', endOption);
            console.log('penId:', penId);

            // Validate form inputs
            if (!endOption) {
                alert('Please select an action.');
                return;
            }

            if (endOption === 'pen' && (!penId || penId === '')) {
                alert('Please select a pen to end.');
                return;
            }

            const currentHouse = houses[activeHouseIndex];
            console.log('currentHouse:', currentHouse);

            if (!currentHouse) {
                console.error('No current house found');
                return;
            }

            let url, method = 'DELETE';
            if (endOption === 'house') {
                url = `/api/houses/${currentHouse.id}`;
                console.log('Ending all pens in house with URL:', url);
            } else if (endOption === 'pen') {
                if (!penId) {
                    alert("Please select a pen to end.");
                    return;
                }
                url = `/api/houses/pen/${penId}`;
                console.log('Ending pen with URL:', url);
            } else {
                alert("Please select an action.");
                return;
            }

            try {
                console.log('Making fetch request to:', url);
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": getCsrfToken(),
                        "Content-Type": "application/json",
                    },
                });

                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                if (!response.ok) {
                    const error = await response.json();
                    console.error('Error response:', error);
                    throw new Error(error.message || "Failed to end batch");
                }

                const result = await response.json();
                console.log('Success response:', result);
                alert(result.message || "Batch ended successfully!");

                closeEndModal();
                // Reload the page to refresh the data
                window.location.reload();
            } catch (error) {
                console.error('Fetch error:', error);
                alert("Error: " + error.message);
            }
        });
    }

    await fetchHouses();
});
