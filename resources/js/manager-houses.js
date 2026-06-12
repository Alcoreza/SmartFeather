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
    const penCapacitySection = document.getElementById("penCapacitySection");
    const penCapacityFields = document.getElementById("penCapacityFields");
    const confirmAddHouseModal = document.getElementById("confirmAddHouseModal");
    const closeConfirmAddHouseModal = document.getElementById(
        "closeConfirmAddHouseModal",
    );
    const cancelConfirmAddHouseModal = document.getElementById(
        "cancelConfirmAddHouseModal",
    );
    const confirmAddHouseSave = document.getElementById("confirmAddHouseSave");
    const confirmAddHouseMessage = document.getElementById(
        "confirmAddHouseMessage",
    );

    const editHouseButton = document.getElementById("openEditHouseModal");
    const editHouseModal = document.getElementById("editHouseModal");
    const closeEditHouseModal = document.getElementById("closeEditHouseModal");
    const cancelEditHouseModal = document.getElementById(
        "cancelEditHouseModal",
    );
    const editHouseForm = document.getElementById("editHouseForm");
    const confirmEditHouseModal = document.getElementById("confirmEditHouseModal");
    const closeConfirmEditHouseModal = document.getElementById(
        "closeConfirmEditHouseModal",
    );
    const cancelConfirmEditHouseModal = document.getElementById(
        "cancelConfirmEditHouseModal",
    );
    const confirmEditHouseSave = document.getElementById("confirmEditHouseSave");
    const confirmEditHouseMessage = document.getElementById(
        "confirmEditHouseMessage",
    );

    const endBatchButton = document.getElementById("openEndBatchModal");
    const endBatchModal = document.getElementById("endBatchModal");
    const closeEndBatchModal = document.getElementById("closeEndBatchModal");
    const cancelEndBatchModal = document.getElementById("cancelEndBatchModal");
    const endBatchForm = document.getElementById("endBatchForm");
    const penSelectGroup = document.getElementById("penSelectGroup");
    const endAction = document.getElementById("endAction");
    const endPenSelect = document.getElementById("endPenSelect");
    const archiveHouseButton = document.getElementById("openArchiveHouseModal");
    const archiveHouseModal = document.getElementById("archiveHouseModal");
    const closeArchiveHouseModal = document.getElementById("closeArchiveHouseModal");
    const cancelArchiveHouseModal = document.getElementById("cancelArchiveHouseModal");
    const archiveHouseForm = document.getElementById("archiveHouseForm");
    const archiveHouseMessage = document.getElementById("archiveHouseMessage");

    console.log("Debug: Elements found - endBatchForm:", endBatchForm, "endAction:", endAction, "endPenSelect:", endPenSelect);

    let activeHouseIndex = 0;
    let activePenIndex = 0;
    let pendingAddHousePayload = null;
    let pendingEditHousePayload = null;
    const addHouseRequiredFields = [
        { id: "houseName", label: "House Number" },
        { id: "housePenCount", label: "Number of Pens" },
    ];
    let shouldTrackAddHouseRequiredHighlights = false;

    function isAddHouseRequiredFieldEmpty(field) {
        if (field.capacityField) {
            const value = document.getElementById(field.id)?.value;
            const numberValue = Number(value);

            return (
                value === "" ||
                numberValue < 0 ||
                !Number.isInteger(numberValue)
            );
        }

        if (field.id === "housePenCount") {
            const value = Number(document.getElementById(field.id)?.value || 0);
            return value <= 0 || !Number.isInteger(value);
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

        formError.textContent =
            "Please fill in the required fields with valid whole numbers.";
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
        renderPenCapacityFields(0);
    }

    function getMissingAddHouseRequiredFields() {
        const penSetupFields = Array.from(
            document.querySelectorAll(".pen-setup-input"),
        ).map((input) => ({
            id: input.id,
            label: input.dataset.fieldLabel || "Pen Setup",
            capacityField: true,
        }));

        return [...addHouseRequiredFields, ...penSetupFields].filter(
            isAddHouseRequiredFieldEmpty,
        );
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

    function getCurrentPenCapacities() {
        return Array.from(document.querySelectorAll(".pen-capacity-input")).map(
            (input) => input.value,
        );
    }

    function getCurrentPenFeederCounts() {
        return Array.from(document.querySelectorAll(".pen-feeder-count-input")).map(
            (input) => input.value,
        );
    }

    function getCurrentPenDrinkerCounts() {
        return Array.from(document.querySelectorAll(".pen-drinker-count-input")).map(
            (input) => input.value,
        );
    }

    function syncCapacityFieldHighlight(input) {
        if (!input) return;

        if (
            shouldTrackAddHouseRequiredHighlights &&
            ((input.value || "").trim() === "" ||
                Number(input.value) < 0 ||
                !Number.isInteger(Number(input.value)))
        ) {
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

    function renderPenCapacityFields(count) {
        if (!penCapacitySection || !penCapacityFields) return;

        const safeCount = Math.max(0, Math.min(Math.floor(Number(count) || 0), 100));
        const previousCapacities = getCurrentPenCapacities();
        const previousFeederCounts = getCurrentPenFeederCounts();
        const previousDrinkerCounts = getCurrentPenDrinkerCounts();

        penCapacitySection.hidden = safeCount <= 0;
        penCapacityFields.innerHTML = "";

        for (let index = 0; index < safeCount; index++) {
            const field = document.createElement("div");
            field.className = "pen-setup-card";

            const title = document.createElement("div");
            title.className = "pen-setup-title";
            title.textContent = `Pen ${index + 1}`;

            const fields = document.createElement("div");
            fields.className = "pen-setup-grid";

            const setupFields = [
                {
                    id: `penCapacity${index + 1}`,
                    label: "Capacity*",
                    className: "pen-capacity-input",
                    value: previousCapacities[index] ?? "",
                    fieldLabel: `Pen ${index + 1} Capacity`,
                },
                {
                    id: `penFeederCount${index + 1}`,
                    label: "Feeders*",
                    className: "pen-feeder-count-input",
                    value: previousFeederCounts[index] ?? "",
                    fieldLabel: `Pen ${index + 1} Feeders`,
                },
                {
                    id: `penDrinkerCount${index + 1}`,
                    label: "Drinkers*",
                    className: "pen-drinker-count-input",
                    value: previousDrinkerCounts[index] ?? "",
                    fieldLabel: `Pen ${index + 1} Drinkers`,
                },
            ];

            setupFields.forEach((setupField) => {
                const group = document.createElement("div");
                group.className = "modal-group";

                const label = document.createElement("label");
                label.setAttribute("for", setupField.id);
                label.textContent = setupField.label;

                const input = document.createElement("input");
                input.type = "number";
                input.id = setupField.id;
                input.className = `pen-setup-input ${setupField.className}`;
                input.min = "0";
                input.step = "1";
                input.inputMode = "numeric";
                input.value = setupField.value;
                input.dataset.fieldLabel = setupField.fieldLabel;

                input.addEventListener("input", () =>
                    syncCapacityFieldHighlight(input),
                );
                input.addEventListener("change", () =>
                    syncCapacityFieldHighlight(input),
                );

                group.append(label, input);
                fields.appendChild(group);
            });

            field.append(title, fields);
            penCapacityFields.appendChild(field);
        }
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

    function buildAddHousePayload() {
        const houseNameInput = document.getElementById("houseName");
        const houseStatusInput = document.getElementById("houseStatusInput");
        const penCountInput = document.getElementById("housePenCount");

        const houseName = houseNameInput?.value.trim();
        const houseStatusValue = houseStatusInput?.value || "Running";
        const penCountValue = Number(penCountInput?.value || 0);
        const penCapacities = Array.from(
            document.querySelectorAll(".pen-capacity-input"),
        ).map((input) => Number(input.value || 0));
        const penFeederCounts = Array.from(
            document.querySelectorAll(".pen-feeder-count-input"),
        ).map((input) => Number(input.value || 0));
        const penDrinkerCounts = Array.from(
            document.querySelectorAll(".pen-drinker-count-input"),
        ).map((input) => Number(input.value || 0));

        return {
            house_number: houseName,
            status: houseStatusValue,
            number_of_pens: penCountValue,
            pen_capacities: penCapacities,
            pen_feeder_counts: penFeederCounts,
            pen_drinker_counts: penDrinkerCounts,
        };
    }

    function openAddHouseConfirmModal(payload) {
        pendingAddHousePayload = payload;

        if (confirmAddHouseMessage) {
            confirmAddHouseMessage.textContent = `Create ${payload.house_number} with ${payload.number_of_pens} pen${payload.number_of_pens === 1 ? "" : "s"}?`;
        }

        confirmAddHouseModal?.classList.add("show");
    }

    function closeAddHouseConfirmModal() {
        confirmAddHouseModal?.classList.remove("show");
        pendingAddHousePayload = null;
    }

    async function createHouse(payload) {
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

        return response.json();
    }

    function buildEditHousePayload() {
        const editHouseName = document.getElementById("editHouseName");
        const editStartDate = document.getElementById("editStartDate");
        const editPen = document.getElementById("editPen");
        const editCapacity = document.getElementById("editCapacity");
        const editPopulation = document.getElementById("editPopulation");
        const editFeederCount = document.getElementById("editFeederCount");
        const editDrinkerCount = document.getElementById("editDrinkerCount");
        const editEggsHatched = document.getElementById("editEggsHatched");
        const editMortality = document.getElementById("editMortality");

        const currentHouse = houses[activeHouseIndex];
        if (!currentHouse) {
            return null;
        }

        const selectedPenIndex = editPen
            ? Number(editPen.value)
            : activePenIndex;
        const currentPen = currentHouse.pens[selectedPenIndex];
        if (!currentPen) {
            return null;
        }

        return {
            currentHouse,
            currentPen,
            selectedPenIndex,
            housePayload: {
                house_number:
                    editHouseName?.value.trim() ||
                    currentHouse.name,
                start_date:
                    editStartDate?.value || currentHouse.start_date,
            },
            penPayload: {
                capacity: parseInt(editCapacity?.value) || 0,
                population: parseInt(editPopulation?.value) || 0,
                feeder_count: parseInt(editFeederCount?.value) || 0,
                drinker_count: parseInt(editDrinkerCount?.value) || 0,
            },
            productionPayload: {
                eggs_hatched: parseInt(editEggsHatched?.value) || 0,
                mortality: parseInt(editMortality?.value) || 0,
            },
        };
    }

    function openEditHouseConfirmModal(payload) {
        pendingEditHousePayload = payload;

        if (confirmEditHouseMessage) {
            confirmEditHouseMessage.textContent = `Save changes to ${payload.currentHouse.name}, ${payload.currentPen.pen_name}?`;
        }

        confirmEditHouseModal?.classList.add("show");
    }

    function closeEditHouseConfirmModal() {
        confirmEditHouseModal?.classList.remove("show");
        pendingEditHousePayload = null;
    }

    async function updateHouseAndPen(payload) {
        const houseResponse = await fetch(
            `/api/houses/${payload.currentHouse.id}`,
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: JSON.stringify(payload.housePayload),
            },
        );

        if (!houseResponse.ok) {
            const error = await houseResponse.json();
            throw new Error(error.message || "Failed to update house");
        }

        const penResponse = await fetch(`/api/pens/${payload.currentPen.id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            body: JSON.stringify(payload.penPayload),
        });

        if (!penResponse.ok) {
            const error = await penResponse.json();
            throw new Error(error.message || "Failed to update pen");
        }

        const productionResponse = await fetch(
            `/api/pens/${payload.currentPen.id}/production`,
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": getCsrfToken(),
                },
                body: JSON.stringify(payload.productionPayload),
            },
        );

        if (!productionResponse.ok) {
            const error = await productionResponse.json();
            throw new Error(
                error.message || "Failed to update production data",
            );
        }
    }

    function populateEditPenFields(pen, house) {
        const editBatchId = document.getElementById("editBatchId");
        const editStartDate = document.getElementById("editStartDate");
        const editCapacity = document.getElementById("editCapacity");
        const editPopulation = document.getElementById("editPopulation");
        const editFeederCount = document.getElementById("editFeederCount");
        const editDrinkerCount = document.getElementById("editDrinkerCount");
        const editEggsHatched = document.getElementById("editEggsHatched");
        const editMortality = document.getElementById("editMortality");

        if (!pen) return;

        if (editBatchId) editBatchId.value = pen.batch || "";
        if (editStartDate)
            editStartDate.value =
                pen.batch_started_at || house?.start_date || "";
        if (editCapacity) editCapacity.value = pen.capacity || 0;
        if (editPopulation)
            editPopulation.value = pen.population || 0;
        if (editFeederCount) editFeederCount.value = pen.feeder_count || 0;
        if (editDrinkerCount) editDrinkerCount.value = pen.drinker_count || 0;
        if (editEggsHatched)
            editEggsHatched.value = pen.eggs_hatched || 0;
        if (editMortality) editMortality.value = pen.mortality || 0;
    }

    function openEditModal() {
        const currentHouse = houses[activeHouseIndex];
        if (!currentHouse || !editHouseModal) return;

        const editHouseName = document.getElementById("editHouseName");
        const editPen = document.getElementById("editPen");

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

        populateEditPenFields(currentHouse.pens[activePenIndex], currentHouse);

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

    function updateHouseActionButtonState() {
        const hasHouse = Boolean(houses[activeHouseIndex]);

        [editHouseButton, archiveHouseButton].forEach((button) => {
            if (!button) return;

            button.disabled = !hasHouse;
            button.setAttribute("aria-disabled", String(!hasHouse));
        });

        updateEndBatchButtonState();
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

    function openArchiveModal() {
        const currentHouse = houses[activeHouseIndex];
        if (!currentHouse || !archiveHouseModal) return;

        if (archiveHouseMessage) {
            archiveHouseMessage.textContent = `Archive ${currentHouse.name}? It will be hidden from active house lists, but its data will remain saved.`;
        }

        archiveHouseModal.classList.add("show");
    }

    function closeArchiveModal() {
        if (archiveHouseModal) {
            archiveHouseModal.classList.remove("show");
        }

        if (archiveHouseForm) {
            archiveHouseForm.reset();
        }
    }

    function buildInfoCards(cards) {
        return cards
            .map(
                (card) => `
            <article class="info-card card-animate">
                <div class="info-text">
                    <div class="info-title">${card.label}</div>
                    <div class="info-value">${card.value}</div>
                    <div class="info-subtitle">${card.detail}</div>
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

    function formatDisplayDate(value, includeTime = false) {
        if (!value) {
            return "";
        }

        const normalizedValue =
            typeof value === "string" && /^\d{4}-\d{2}-\d{2}$/.test(value)
                ? `${value}T00:00:00`
                : value;
        const date = new Date(normalizedValue);

        if (Number.isNaN(date.getTime())) {
            return String(value);
        }

        return new Intl.DateTimeFormat("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
            ...(includeTime
                ? {
                      hour: "numeric",
                      minute: "2-digit",
                  }
                : {}),
        }).format(date);
    }

    function getWeightSamplingLogDetail(log) {
        if (!log) {
            return "No weight sampling record";
        }

        if (log.date) {
            return `Recorded ${formatDisplayDate(log.date)}`;
        }

        if (log.created_at) {
            return `Recorded ${formatDisplayDate(log.created_at, true)}`;
        }

        return "No weight sampling date";
    }

    function buildNumberedResourceReadings(readings, key, configuredCount, label) {
        const resourceReadings = readings?.[key] || {};
        const readingNumbers = Object.keys(resourceReadings)
            .map((number) => Number(number))
            .filter((number) => Number.isInteger(number) && number > 0);
        const count = Math.max(Number(configuredCount) || 0, ...readingNumbers, 0);

        return Array.from({ length: count }, (_, index) => {
            const number = index + 1;
            const value = Number(resourceReadings?.[number]?.value ?? 0);

            return {
                label: `${label} ${number}`,
                value: Number.isFinite(value) ? value : 0,
            };
        });
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
                        const weightSamplingLog = pen.latest_weight_sampling_log || null;
                        const weightSamplingLogDetail =
                            getWeightSamplingLogDetail(weightSamplingLog);
                        const batchStartDate =
                            flockBatch?.started_at || pen.batch_started_at || null;
                        const batchStartDetail = batchStartDate
                            ? formatDisplayDate(batchStartDate, true)
                            : "Start date not set";

                        const feeders = buildNumberedResourceReadings(
                            sensorReadings,
                            "feeders",
                            pen.feeder_count,
                            "Feeder",
                        );
                        const drinkers = buildNumberedResourceReadings(
                            sensorReadings,
                            "drinkers",
                            pen.drinker_count,
                            "Drinker",
                        );

                        return {
                            id: pen.id,
                            name: pen.pen_name,
                            pen_name: pen.pen_name,
                            feeder_count: pen.feeder_count || 0,
                            drinker_count: pen.drinker_count || 0,
                            batchStatus:
                                weightSamplingLog?.status ||
                                "No weight sampling",
                            batchStatusDetail: weightSamplingLogDetail,
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
                                batchStartDate,
                            cards: [
                                {
                                    icon: "🏠",
                                    label: "Pen Capacity",
                                    value: pen.capacity || 0,
                                    detail: `${pen.population || 0} birds currently placed`,
                                    accent: "red",
                                },
                                {
                                    icon: "📅",
                                    label: "Batch ID",
                                    value: flockBatch?.batch_code || "No active batch",
                                    detail: batchStartDetail,
                                    accent: "blue",
                                },
                                {
                                    icon: "💚",
                                    label: "Batch Status",
                                    value:
                                        weightSamplingLog?.status ||
                                        "No weight sampling",
                                    detail: weightSamplingLogDetail,
                                    accent: "green",
                                },
                                {
                                    icon: "📊",
                                    label: "Hatch & Mortality",
                                    value: pen.eggs_hatched || 0,
                                    detail: `${pen.mortality || 0} mortality recorded`,
                                    accent: "orange",
                                },
                            ],
                            feeders,
                            drinkers,
                        };
                    })),
                    records: [],
                }))
                .sort((a, b) => Number(a.id) - Number(b.id));

            if (houses.length > 0) {
                activeHouseIndex = 0;
                rebuildHouseTabs();
                renderHouse(0);
            } else {
                activeHouseIndex = 0;
                activePenIndex = 0;
                rebuildHouseTabs();
                renderNoHouses();
            }
        } catch (error) {
            console.error("Error fetching houses:", error);
            alert("Error loading houses: " + error.message);
        }
    }

    function renderNoHouses() {
        if (houseStatus) {
            houseStatus.textContent = "No Houses";
            houseStatus.classList.remove("chip-green");
            houseStatus.classList.add("chip-gray");
        }
        if (houseBatch) houseBatch.textContent = "No Batch";
        if (housePen) {
            housePen.innerHTML = '<option value="">No pens</option>';
            housePen.value = "";
        }
        if (houseTemperature) houseTemperature.textContent = "--";
        if (houseAmmonia) houseAmmonia.textContent = "--";
        if (infoGrid) infoGrid.innerHTML = "";
        if (feedRow) feedRow.innerHTML = "";
        if (waterRow) waterRow.innerHTML = "";

        updateHouseActionButtonState();
    }

    function animateStats() {
        const animatedElements = document.querySelectorAll(
            ".env-card, .resource-section, #houseStatus, #houseBatch, #houseTemperature, #houseAmmonia, .info-card, .resource-item",
        );

        animatedElements.forEach((element, index) => {
            element.classList.remove("show-stat", "show-card");
            void element.offsetWidth;
            element.style.animationDelay = `${index * 0.035}s`;

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

        updateHouseActionButtonState();
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
            <button type="button" class="add-house-btn" id="openAddHouseModal" aria-label="Add house">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
            </button>
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

        if (!housesTabsContainer) return;

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

        const penCountInput = document.getElementById("housePenCount");
        penCountInput?.addEventListener("input", (event) =>
            renderPenCapacityFields(event.target.value),
        );
        penCountInput?.addEventListener("change", (event) =>
            renderPenCapacityFields(event.target.value),
        );

        addHouseForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (showAddHouseRequiredFieldsError()) {
                return;
            }

            openAddHouseConfirmModal(buildAddHousePayload());
        });
    }

    [closeConfirmAddHouseModal, cancelConfirmAddHouseModal].forEach((button) => {
        button?.addEventListener("click", closeAddHouseConfirmModal);
    });

    confirmAddHouseModal?.addEventListener("click", (event) => {
        if (event.target === confirmAddHouseModal) {
            closeAddHouseConfirmModal();
        }
    });

    confirmAddHouseSave?.addEventListener("click", async () => {
        if (!pendingAddHousePayload) return;

        const payload = pendingAddHousePayload;
        confirmAddHouseSave.disabled = true;

        try {
            const result = await createHouse(payload);

            alert(
                result.message ||
                    "House and pen setup saved successfully.",
            );

            closeAddHouseConfirmModal();
            resetAddHouseForm();
            clearAddHouseRequiredFieldHighlights();
            clearAddHouseFormError();
            closeAddModal();

            await fetchHouses();
        } catch (error) {
            console.error("Error creating house:", error);
            alert("Error creating house: " + error.message);
        } finally {
            confirmAddHouseSave.disabled = false;
        }
    });

    if (editHouseButton) {
        editHouseButton.addEventListener("click", openEditModal);
    }

    document.getElementById("editPen")?.addEventListener("change", (event) => {
        const currentHouse = houses[activeHouseIndex];
        const selectedPen = currentHouse?.pens?.[Number(event.target.value)];

        populateEditPenFields(selectedPen, currentHouse);
    });

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

            const payload = buildEditHousePayload();
            if (!payload) return;

            openEditHouseConfirmModal(payload);
        });
    }

    [closeConfirmEditHouseModal, cancelConfirmEditHouseModal].forEach((button) => {
        button?.addEventListener("click", closeEditHouseConfirmModal);
    });

    confirmEditHouseModal?.addEventListener("click", (event) => {
        if (event.target === confirmEditHouseModal) {
            closeEditHouseConfirmModal();
        }
    });

    confirmEditHouseSave?.addEventListener("click", async () => {
        if (!pendingEditHousePayload) return;

        const payload = pendingEditHousePayload;
        confirmEditHouseSave.disabled = true;

        try {
            await updateHouseAndPen(payload);

            activePenIndex = payload.selectedPenIndex;
            closeEditHouseConfirmModal();
            closeEditModal();
            await fetchHouses();
        } catch (error) {
            console.error("Error updating data:", error);
            alert("Error: " + error.message);
        } finally {
            confirmEditHouseSave.disabled = false;
        }
    });

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

    if (archiveHouseButton) {
        archiveHouseButton.addEventListener("click", openArchiveModal);
    }

    if (closeArchiveHouseModal) {
        closeArchiveHouseModal.addEventListener("click", closeArchiveModal);
    }

    if (cancelArchiveHouseModal) {
        cancelArchiveHouseModal.addEventListener("click", closeArchiveModal);
    }

    if (archiveHouseModal) {
        archiveHouseModal.addEventListener("click", (event) => {
            if (event.target === archiveHouseModal) {
                closeArchiveModal();
            }
        });
    }

    if (archiveHouseForm) {
        archiveHouseForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            const currentHouse = houses[activeHouseIndex];
            if (!currentHouse) return;

            try {
                const response = await fetch(`/api/houses/${currentHouse.id}/archive`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": getCsrfToken(),
                    },
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || "Failed to archive house");
                }

                alert(result.message || "House archived successfully.");
                closeArchiveModal();
                await fetchHouses();
            } catch (error) {
                console.error("Error archiving house:", error);
                alert("Error archiving house: " + error.message);
            }
        });
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
